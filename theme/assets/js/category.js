(function () {
    'use strict';

    var config = window.CODEDITOR_CATEGORY || {};
    var base = (config.api || '').replace(/\/+$/, '');
    var readUrl = config.read || (base + '/api/category/read');
    var createUrl = config.create || (base + '/api/category/create');
    var updateUrl = config.update || (base + '/api/category/update');
    var deleteUrl = config.delete || (base + '/api/category/delete');

    var layer = null;
    var table = null;
    var form = null;
    var tableIns = null;
    var items = [];

    layui.use(['table', 'layer', 'form'], function () {
        table = layui.table;
        layer = layui.layer;
        form = layui.form;
        boot();
    });

    function boot() {
        bindEvents();
        loadList();

        window.addEventListener('resize', function () {
            if (tableIns) {
                tableIns.resize();
            }
        });
    }

    function bindEvents() {
        document.getElementById('btn-category-create').addEventListener('click', function () {
            openForm(null);
        });

        document.getElementById('btn-category-refresh').addEventListener('click', loadList);
        document.getElementById('category-search').addEventListener('input', renderTable);

        table.on('tool(category-table)', function (obj) {
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
            renderTable();
        }).catch(function (error) {
            layer.msg(error.message, { icon: 2, time: 3000 });
        });
    }

    function filteredItems() {
        var keyword = (document.getElementById('category-search').value || '').trim().toLowerCase();

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

        if (!tableIns) {
            tableIns = table.render({
                elem: '#category-table',
                data: data,
                skin: 'line',
                size: 'sm',
                page: { limit: 20, limits: [20, 50, 100] },
                limit: 20,
                height: 'full-190',
                text: { none: emptyText },
                cols: [[
                    { field: 'id', title: 'ID', width: 70, sort: true },
                    { field: 'title', title: '分类标题', minWidth: 180 },
                    { field: 'name', title: '分类名称', width: 140 },
                    { field: 'sort', title: '排序', width: 80, sort: true, align: 'center' },
                    {
                        field: 'status',
                        title: '状态',
                        width: 90,
                        align: 'center',
                        templet: function (item) {
                            return item.status == 1
                                ? '<span class="codeditor-tag is-on">启用</span>'
                                : '<span class="codeditor-tag is-off">禁用</span>';
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
                    { title: '操作', width: 140, align: 'center', toolbar: '#category-toolbar' }
                ]]
            });

            return;
        }

        tableIns.reload({
            data: data,
            page: { curr: 1 },
            text: { none: emptyText }
        });
    }

    function openForm(row) {
        var template = document.getElementById('category-form-tpl').innerHTML;

        layer.open({
            type: 1,
            title: row ? '编辑分类' : '新增分类',
            area: ['760px', '600px'],
            content: template,
            btn: ['保存', '取消'],
            success: function () {
                form.val('category-form', {
                    id: row ? row.id : '',
                    title: row ? (row.title || '') : '',
                    titlepic: row ? (row.titlepic || '') : '',
                    name: row ? (row.name || '') : '',
                    sort: row ? (row.sort || 0) : 0,
                    status: row ? String(row.status == 1 ? 1 : 0) : '1',
                    intro: row ? (row.intro || '') : '',
                    is_last: row ? String(row.is_last == 1 ? 1 : 0) : '0',
                    admin_id: row ? (row.admin_id || 0) : 0,
                    site_id: row ? (row.site_id || 0) : 0
                });
                form.render(null, 'category-form');
            },
            yes: function (index) {
                var data = form.val('category-form');
                var payload = {
                    title: data.title || '',
                    titlepic: data.titlepic || '',
                    name: data.name || '',
                    sort: data.sort || 0,
                    status: data.status || 0,
                    intro: data.intro || '',
                    is_last: data.is_last || 0,
                    admin_id: data.admin_id || 0,
                    site_id: data.site_id || 0
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

        layer.confirm('确定删除分类「' + name + '」吗？', { icon: 3, title: '删除分类' }, function (index) {
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
})();