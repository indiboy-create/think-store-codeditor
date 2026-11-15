(function () {
    'use strict';

    var config = window.CODEDITOR_CONFIG || {};
    var base = (config.api || '').replace(/\/+$/, '');
    var assets = (config.assets || (base + '/assets')).replace(/\/+$/, '');
    var historyUrl = config.history || (base + '/api/template/history');
    var detailUrl = config.historyDetail || (base + '/api/template/history/detail');
    var restoreUrl = config.historyRestore || (base + '/api/template/history/restore');
    var aceBase = assets + '/ace';

    var layer = null;
    var table = null;
    var tableIns = null;
    var panelIndex = null;
    var viewEditor = null;
    var currentTemplateId = null;
    var currentTemplateTitle = '';

    layui.use(['table', 'layer'], function () {
        layer = layui.layer;
        table = layui.table;

        table.on('tool(history-table)', function (obj) {
            if (obj.event === 'view') {
                viewHistory(obj.data);
            } else if (obj.event === 'restore') {
                restoreHistory(obj.data);
            }
        });
    });

    window.CODEDITOR_HISTORY = {
        open: openPanel
    };

    function openPanel(context) {
        context = context || {};
        currentTemplateId = context.id || null;
        currentTemplateTitle = context.title || '';

        if (!currentTemplateId) {
            layer.msg('请先选择模板', { icon: 0 });
            return;
        }

        if (panelIndex !== null) {
            layer.close(panelIndex);
            panelIndex = null;
        }

        tableIns = null;

        var template = document.getElementById('history-panel-tpl').innerHTML;

        panelIndex = layer.open({
            type: 1,
            title: '编辑历史',
            area: ['860px', '100%'],
            offset: 'r',
            content: template,
            success: function (layero) {
                layero.find('#history-template-title').text(currentTemplateTitle);
                layero.find('#btn-history-refresh').on('click', loadList);
                loadList();
            }
        });
    }

    function request(url, options) {
        options = options || {};

        var headers = options.headers || {};
        headers.Accept = 'application/json';

        if (options.body) {
            headers['Content-Type'] = 'application/json';
        }

        return fetch(url, {
            method: options.method || 'GET',
            headers: headers,
            body: options.body,
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json().catch(function () {
                throw new Error('接口返回格式错误（HTTP ' + response.status + '）');
            });
        });
    }

    function loadList() {
        request(historyUrl + '?template_id=' + encodeURIComponent(currentTemplateId)).then(function (result) {
            if (result.code !== 0) {
                throw new Error(result.msg || '加载历史失败');
            }

            renderTable((result.data && result.data.items) || []);
        }).catch(function (error) {
            layer.msg(error.message, { icon: 2, time: 3000 });
        });
    }

    function renderTable(items) {
        var options = {
            elem: '#history-table',
            data: items,
            skin: 'line',
            size: 'sm',
            page: false,
            height: 'full-190',
            text: { none: '暂无编辑历史' },
            cols: [[
                { field: 'id', title: 'ID', width: 70 },
                { field: 'title', title: '模板标题', minWidth: 150 },
                { field: 'intro', title: '修改说明', minWidth: 180 },
                {
                    field: 'newstime',
                    title: '快照时间',
                    width: 170,
                    templet: function (item) {
                        return formatTime(item.newstime);
                    }
                },
                { field: 'admin_id', title: '管理员', width: 90, align: 'center' },
                { title: '操作', width: 140, align: 'center', toolbar: '#history-toolbar' }
            ]]
        };

        if (!tableIns) {
            tableIns = table.render(options);
        } else {
            tableIns.reload(options);
        }
    }

    function viewHistory(row) {
        request(detailUrl + '?id=' + encodeURIComponent(row.id) + '&template_id=' + encodeURIComponent(currentTemplateId)).then(function (result) {
            if (result.code !== 0) {
                throw new Error(result.msg || '读取历史失败');
            }

            var data = result.data || {};

            layer.open({
                type: 1,
                title: '历史 #' + data.id + ' · ' + (data.intro || '无修改说明'),
                area: ['860px', '620px'],
                content: '<div id="history-view-editor" class="codeditor-history__editor"></div>',
                success: function () {
                    if (!window.ace) {
                        return;
                    }

                    ace.config.set('basePath', aceBase);
                    ace.config.set('modePath', aceBase);
                    ace.config.set('themePath', aceBase);

                    viewEditor = ace.edit('history-view-editor');
                    viewEditor.setTheme('ace/theme/monokai');
                    viewEditor.session.setMode('ace/mode/php');
                    viewEditor.setReadOnly(true);
                    viewEditor.setValue(data.newstext || '', -1);
                },
                end: function () {
                    if (viewEditor) {
                        viewEditor.destroy();
                        viewEditor = null;
                    }
                }
            });
        }).catch(function (error) {
            layer.msg(error.message, { icon: 2, time: 3000 });
        });
    }

    function restoreHistory(row) {
        layer.confirm(
            '确定将模板还原到历史 #' + row.id + ' 吗？<br>' + (row.intro || ''),
            { icon: 3, title: '还原历史' },
            function (index) {
                request(restoreUrl, {
                    method: 'POST',
                    body: JSON.stringify({ id: row.id })
                }).then(function (result) {
                    if (result.code !== 0) {
                        throw new Error(result.msg || '还原失败');
                    }

                    layer.close(index);
                    layer.msg('还原成功', { icon: 1, time: 1200 });

                    window.dispatchEvent(new CustomEvent('codeditor:history-restored', {
                        detail: {
                            templateId: result.data.template_id,
                            content: result.data.content
                        }
                    }));

                    loadList();
                }).catch(function (error) {
                    layer.msg(error.message, { icon: 2, time: 3000 });
                });
            }
        );
    }

    function formatTime(value) {
        var timestamp = parseInt(value, 10);

        if (!timestamp) {
            return '-';
        }

        var date = new Date(timestamp * 1000);
        var pad = function (number) {
            return number < 10 ? '0' + number : String(number);
        };

        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate())
            + ' ' + pad(date.getHours()) + ':' + pad(date.getMinutes());
    }
})();