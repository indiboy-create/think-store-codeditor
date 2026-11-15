(function () {
    'use strict';

    var config = window.CODEDITOR_IFRAME_TEMPLATE || {};
    var base = (config.api || '').replace(/\/+$/, '');
    var readUrl = config.read || (base + '/api/template/read');
    var createUrl = config.create || (base + '/api/template/create');
    var updateUrl = config.update || (base + '/api/template/update');
    var categoryUrl = config.category || (base + '/api/category/read');

    var table = null;
    var layer = null;
    var form = null;
    var ins = null;
    var items = [];
    var categories = [];
    var categoryMap = {};

    layui.use(['table', 'layer', 'form'], function () {
        table = layui.table;
        layer = layui.layer;
        form = layui.form;
        boot();
    });

    function boot() {
        bindEvents();
        loadCategories();
        loadTemplates();
    }

    function bindEvents() {
        byId('iframe-template-create').addEventListener('click', openCreate);
        byId('iframe-template-refresh').addEventListener('click', loadTemplates);
        byId('iframe-template-search').addEventListener('input', renderTable);
        byId('iframe-template-cid').addEventListener('change', renderTable);

        table.on('tool(iframe-template-table)', function (obj) {
            if (obj.event === 'edit') {
                openEdit(obj.data);
            } else if (obj.event === 'view') {
                openView(obj.data);
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

    function loadCategories() {
        request(categoryUrl).then(function (result) {
            if (result.code !== 0) {
                throw new Error(result.msg || '加载分类失败');
            }

            categories = (result.data && result.data.items) || [];
            categoryMap = {};

            categories.forEach(function (item) {
                categoryMap[item.id] = item.title || item.name || ('#' + item.id);
            });

            fillCategorySelect();

            if (items.length > 0) {
                renderTable();
            }
        }).catch(function (error) {
            layer.msg(error.message, { icon: 2, time: 3000 });
        });
    }

    function fillCategorySelect() {
        var select = byId('iframe-template-cid');
        var html = '<option value="">全部分类</option>';

        categories.forEach(function (item) {
            html += '<option value="' + escapeHtml(item.id) + '">'
                + escapeHtml(item.title || item.name || ('#' + item.id))
                + '</option>';
        });

        select.innerHTML = html;
    }

    function loadTemplates() {
        request(readUrl).then(function (result) {
            if (result.code !== 0) {
                throw new Error(result.msg || '加载模板失败');
            }

            items = (result.data && result.data.items) || [];
            byId('iframe-template-count').textContent = String(items.length);
            renderTable();
        }).catch(function (error) {
            layer.msg(error.message, { icon: 2, time: 3000 });
        });
    }

    function filteredItems() {
        var keyword = (byId('iframe-template-search').value || '').trim().toLowerCase();
        var cid = byId('iframe-template-cid').value;

        return items.filter(function (item) {
            if (cid !== '' && String(item.cid) !== cid) {
                return false;
            }

            if (!keyword) {
                return true;
            }

            return String(item.id).indexOf(keyword) !== -1
                || String(item.title || '').toLowerCase().indexOf(keyword) !== -1;
        });
    }

    function renderTable() {
        var rows = filteredItems();
        var emptyText = items.length === 0 ? '暂无模板，点击右上角新增' : '没有匹配的模板';

        if (!ins) {
            ins = table.render({
                elem: '#iframe-template-table',
                id: 'iframe-template-table',
                data: rows,
                skin: 'line',
                size: 'sm',
                page: { limit: 20, limits: [20, 50, 100] },
                limit: 20,
                height: 'full-190',
                text: { none: emptyText },
                cols: [[
                    { field: 'id', title: 'ID', width: 80, sort: true },
                    {
                        field: 'title',
                        title: '标题',
                        minWidth: 220,
                        templet: function (item) {
                            return escapeHtml(item.title || '未命名模板');
                        }
                    },
                    {
                        field: 'cid',
                        title: '分类',
                        width: 180,
                        templet: function (item) {
                            return escapeHtml(categoryMap[item.cid] || ('#' + item.cid));
                        }
                    },
                    { title: '操作', width: 140, align: 'center', toolbar: '#iframe-template-tools' }
                ]]
            });

            return;
        }

        ins.reload({
            data: rows,
            page: { curr: 1 },
            text: { none: emptyText }
        });
    }

    function openCreate() {
        openForm(null, false);
    }

    function openEdit(row) {
        request(readUrl + '?id=' + encodeURIComponent(row.id)).then(function (result) {
            if (result.code !== 0) {
                throw new Error(result.msg || '读取模板失败');
            }

            var data = result.data || {};

            openForm({
                id: row.id,
                title: data.title || row.title || '',
                cid: row.cid,
                content: data.content || ''
            }, true);
        }).catch(function (error) {
            layer.msg(error.message, { icon: 2, time: 3000 });
        });
    }

    function openView(row) {
        request(readUrl + '?id=' + encodeURIComponent(row.id)).then(function (result) {
            if (result.code !== 0) {
                throw new Error(result.msg || '读取模板失败');
            }

            var data = result.data || {};

            layer.open({
                type: 1,
                title: '查看模板 #' + row.id,
                area: ['760px', '560px'],
                content: '<pre class="iframe-preview">'
                    + escapeHtml(data.content || '')
                    + '</pre>'
            });
        }).catch(function (error) {
            layer.msg(error.message, { icon: 2, time: 3000 });
        });
    }

    function openForm(row, isEdit) {
        var template = byId('iframe-template-form').innerHTML;

        layer.open({
            type: 1,
            title: isEdit ? '编辑模板 #' + row.id : '新增模板',
            area: ['760px', '560px'],
            content: template,
            btn: ['保存', '取消'],
            success: function (layero) {
                layero.find('[data-role="meta"]').toggle(isEdit);
                layero.find('[data-role="file"]').toggle(!isEdit);

                form.val('iframe-template-form', {
                    id: row ? row.id : '',
                    title: row ? row.title : '',
                    cid_text: row ? (categoryMap[row.cid] || ('#' + row.cid)) : '',
                    content: row ? row.content : defaultTemplate(),
                    intro: '',
                    file: ''
                });

                form.render(null, 'iframe-template-form');
            },
            yes: function (index, layero) {
                var data = form.val('iframe-template-form');
                var payload = {
                    content: data.content || '',
                    intro: data.intro || ''
                };
                var url = createUrl;

                if (isEdit) {
                    payload.id = row.id;
                    url = updateUrl;
                } else {
                    payload.file = (layero.find('input[name="file"]').val() || '').trim();
                }

                save(url, payload, index);
            }
        });
    }

    function save(url, payload, index) {
        request(url, {
            method: 'POST',
            body: JSON.stringify(payload)
        }).then(function (result) {
            if (result.code !== 0) {
                throw new Error(result.msg || '保存模板失败');
            }

            layer.close(index);

            var message = '保存成功';
            if (result.data && result.data.changed === false) {
                message = '内容未变化';
            }

            layer.msg(message, { icon: 1, time: 1200 });
            loadTemplates();
        }).catch(function (error) {
            layer.msg(error.message, { icon: 2, time: 3000 });
        });
    }

    function defaultTemplate() {
        return '<!DOCTYPE html>\n'
            + '<html lang="zh-CN">\n'
            + '<head>\n'
            + '    <meta charset="utf-8">\n'
            + '    <title>{$title}</title>\n'
            + '</head>\n'
            + '<body>\n\n'
            + '</body>\n'
            + '</html>';
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function byId(id) {
        return document.getElementById(id);
    }
})();