(function () {
    'use strict';

    var config = window.CODEDITOR_IFRAME_CATEGORY || {};
    var base = (config.api || '').replace(/\/+$/, '');
    var readUrl = config.read || (base + '/api/category/read');
    var createUrl = config.create || (base + '/api/category/create');
    var updateUrl = config.update || (base + '/api/category/update');
    var deleteUrl = config.delete || (base + '/api/category/delete');

    var treeTable = null;
    var layer = null;
    var form = null;
    var inst = null;
    var items = [];
    var pidField = '';
    var expanded = false;

    layui.use(['treeTable', 'layer', 'form'], function () {
        treeTable = layui.treeTable;
        layer = layui.layer;
        form = layui.form;
        boot();
    });

    function boot() {
        bindEvents();
        loadList();
    }

    function bindEvents() {
        byId('iframe-category-create').addEventListener('click', function () {
            openForm(null);
        });

        byId('iframe-category-refresh').addEventListener('click', loadList);
        byId('iframe-category-search').addEventListener('input', renderTable);

        byId('iframe-category-expand').addEventListener('click', function () {
            expanded = !expanded;
            this.textContent = expanded ? '折叠全部' : '展开全部';
            expandAll(expanded);
        });

        treeTable.on('tool(iframe-category-table)', function (obj) {
            if (obj.event === 'edit') {
                openForm(obj.data);
            } else if (obj.event === 'delete') {
                removeCategory(obj.data);
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
        request(readUrl).then(function (result) {
            if (result.code !== 0) {
                throw new Error(result.msg || '加载分类失败');
            }

            items = (result.data && result.data.items) || [];
            pidField = detectPid(items);
            byId('iframe-category-count').textContent = String(items.length);
            byId('iframe-category-expand').style.display = pidField ? '' : 'none';
            renderTable();
        }).catch(function (error) {
            layer.msg(error.message, { icon: 2, time: 3000 });
        });
    }

    function detectPid(rows) {
        var candidates = ['pid', 'parent_id', 'parentId'];

        for (var i = 0; i < rows.length; i++) {
            for (var j = 0; j < candidates.length; j++) {
                if (Object.prototype.hasOwnProperty.call(rows[i], candidates[j])) {
                    return candidates[j];
                }
            }
        }

        return '';
    }

    function expandAll(state) {
        if (pidField && typeof treeTable.expandAll === 'function') {
            treeTable.expandAll('iframe-category-table', state);
        }
    }

    function filteredItems() {
        var keyword = (byId('iframe-category-search').value || '').trim().toLowerCase();

        if (!keyword) {
            return items;
        }

        return items.filter(function (item) {
            return String(item.id).indexOf(keyword) !== -1
                || String(item.title || '').toLowerCase().indexOf(keyword) !== -1
                || String(item.name || '').toLowerCase().indexOf(keyword) !== -1;
        });
    }

    function renderTable() {
        var data = filteredItems();
        var emptyText = items.length === 0 ? '暂无分类，点击右上角新增' : '没有匹配的分类';

        if (!inst) {
            var option = {
                elem: '#iframe-category-table',
                id: 'iframe-category-table',
                data: data,
                skin: 'line',
                height: 'full-190',
                text: { none: emptyText },
                cols: [[
                    { field: 'id', title: 'ID', width: 70, sort: true, fixed: 'left' },
                    {
                        field: 'title',
                        title: '分类标题',
                        minWidth: 200,
                        fixed: 'left',
                        templet: function (item) {
                            return escapeHtml(item.title || '未命名分类');
                        }
                    },
                    { field: 'name', title: '分类名称', width: 150 },
                    { field: 'sort', title: '排序', width: 80, sort: true, align: 'center' },
                    {
                        field: 'status',
                        title: '状态',
                        width: 90,
                        align: 'center',
                        templet: function (item) {
                            return item.status == 1
                                ? '<span class="layui-badge layui-bg-green">启用</span>'
                                : '<span class="layui-badge layui-bg-gray">禁用</span>';
                        }
                    },
                    {
                        field: 'is_last',
                        title: '最后一级',
                        width: 100,
                        align: 'center',
                        templet: function (item) {
                            return item.is_last == 1 ? '是' : '否';
                        }
                    },
                    {
                        field: 'create_time',
                        title: '创建时间',
                        width: 170,
                        templet: function (item) {
                            return formatTime(item.create_time);
                        }
                    },
                    { title: '操作', width: 140, align: 'center', fixed: 'right', toolbar: '#iframe-category-tools' }
                ]]
            };

            if (pidField) {
                option.tree = { customName: { pid: pidField } };
            }

            inst = treeTable.render(option);
            return;
        }

        treeTable.reload('iframe-category-table', { data: data, text: { none: emptyText } });
    }

    function openForm(row) {
        var template = byId('iframe-category-form').innerHTML;

        layer.open({
            type: 1,
            title: row ? '编辑分类' : '新增分类',
            area: ['720px', '560px'],
            content: template,
            btn: ['保存', '取消'],
            success: function () {
                form.val('iframe-category-form', {
                    id: row ? row.id : '',
                    title: row ? (row.title || '') : '',
                    titlepic: row ? (row.titlepic || '') : '',
                    name: row ? (row.name || '') : '',
                    sort: row ? (row.sort || 0) : 0,
                    status: row ? String(row.status == 1 ? 1 : 0) : '1',
                    intro: row ? (row.intro || '') : '',
                    is_last: row ? String(row.is_last == 1 ? 1 : 0) : '0'
                });
                form.render(null, 'iframe-category-form');
            },
            yes: function (index) {
                var data = form.val('iframe-category-form');
                var payload = {
                    title: data.title || '',
                    titlepic: data.titlepic || '',
                    name: data.name || '',
                    sort: data.sort || 0,
                    status: data.status || 0,
                    intro: data.intro || '',
                    is_last: data.is_last || 0
                };

                if (row) {
                    payload.id = row.id;
                }

                save(row ? updateUrl : createUrl, payload, index);
            }
        });
    }

    function save(url, payload, index) {
        request(url, {
            method: 'POST',
            body: JSON.stringify(payload)
        }).then(function (result) {
            if (result.code !== 0) {
                throw new Error(result.msg || '保存分类失败');
            }

            layer.close(index);
            layer.msg('保存成功', { icon: 1, time: 1200 });
            loadList();
        }).catch(function (error) {
            layer.msg(error.message, { icon: 2, time: 3000 });
        });
    }

    function removeCategory(row) {
        var name = row.title || row.name || ('#' + row.id);

        layer.confirm('确定删除分类「' + escapeHtml(name) + '」吗？', { icon: 3, title: '删除分类' }, function (index) {
            request(deleteUrl, {
                method: 'POST',
                body: JSON.stringify({ id: row.id })
            }).then(function (result) {
                if (result.code !== 0) {
                    throw new Error(result.msg || '删除分类失败');
                }

                layer.close(index);
                layer.msg('删除成功', { icon: 1, time: 1200 });
                loadList();
            }).catch(function (error) {
                layer.msg(error.message, { icon: 2, time: 3000 });
            });
        });
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