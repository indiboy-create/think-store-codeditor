(function () {
    'use strict';

    var config = window.CODEDITOR_CONFIG || {};
    var base = (config.api || '').replace(/\/+$/, '');
    var assets = (config.assets || (base + '/assets')).replace(/\/+$/, '');
    var readUrl = config.read || (base + '/api/template/read');
    var updateUrl = config.update || (base + '/api/template/update');
    var createUrl = config.create || (base + '/api/template/create');
    var aceBase = assets + '/ace';

    var tableEl = document.getElementById('tpl-table');
    var countEl = document.getElementById('tpl-count');
    var searchEl = document.getElementById('tpl-search');
    var currentTitle = document.getElementById('current-title');
    var statusMsg = document.getElementById('status-msg');
    var statusMeta = document.getElementById('status-meta');
    var charCount = document.getElementById('char-count');
    var dirtyDot = document.getElementById('dirty-dot');
    var historyBtn = document.getElementById('btn-history');

    var state = {
        items: [],
        currentId: null,
        dirty: false,
        saving: false,
        creating: false,
        silent: false,
        table: null
    };

    var editor = null;
    var layer = null;
    var form = null;
    var table = null;

    layui.use(['table', 'layer', 'form'], function () {
        table = layui.table;
        layer = layui.layer;
        form = layui.form;
        boot();
    });

    function boot() {
        initEditor();
        bindEvents();
        loadList();

        window.addEventListener('beforeunload', function (event) {
            if (!state.dirty) {
                return;
            }

            event.preventDefault();
            event.returnValue = '';
        });

        window.addEventListener('codeditor:history-restored', function (event) {
            var detail = event.detail || {};

            if (state.currentId !== detail.templateId) {
                return;
            }

            setEditorValue(detail.content || '');
            markDirty(false);
            setStatus('已还原到历史版本');
            loadList();
        });

        window.addEventListener('resize', function () {
            if (editor) {
                editor.resize();
            }
        });
    }

    function initEditor() {
        if (!window.ace) {
            setStatus('Ace 编辑器加载失败，请检查 assets/ace 目录');
            return;
        }

        ace.config.set('basePath', aceBase);
        ace.config.set('modePath', aceBase);
        ace.config.set('themePath', aceBase);
        ace.config.set('workerPath', aceBase);

        editor = ace.edit('editor');
        editor.setTheme('ace/theme/monokai');
        editor.session.setMode('ace/mode/php');
        editor.session.setUseWrapMode(true);
        editor.setOptions({
            fontSize: 14,
            tabSize: 4,
            useSoftTabs: true,
            showPrintMargin: false,
            highlightActiveLine: true,
            displayIndentGuides: true
        });

        editor.session.on('change', function () {
            if (state.silent) {
                return;
            }

            markDirty(true);
            updateStats();
        });

        editor.selection.on('changeCursor', updateStats);

        editor.commands.addCommand({
            name: 'saveTemplate',
            bindKey: { win: 'Ctrl-S', mac: 'Command-S' },
            exec: function () {
                saveTemplate('');
            }
        });

        updateStats();
    }

    function bindEvents() {
        document.getElementById('btn-refresh').addEventListener('click', loadList);
        document.getElementById('btn-save').addEventListener('click', promptSave);
        document.getElementById('btn-create').addEventListener('click', openCreate);
        historyBtn.addEventListener('click', openHistory);
        searchEl.addEventListener('input', renderTable);

        table.on('row(tpl-table)', function (obj) {
            selectTemplate(obj.data.id);
        });
    }

    function request(url, options) {
        options = options || {};

        var headers = options.headers || {};
        headers.Accept = 'application/json';

        if (options.body && !headers['Content-Type']) {
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
        setStatus('正在加载模板列表...');

        request(readUrl).then(function (result) {
            if (result.code !== 0) {
                throw new Error(result.msg || '加载模板列表失败');
            }

            state.items = (result.data && result.data.items) || [];
            renderTable();
            setStatus('模板列表已加载，共 ' + state.items.length + ' 条');
        }).catch(function (error) {
            setStatus(error.message);
            showError(error.message);
        });
    }

    function filteredItems() {
        var keyword = (searchEl.value || '').trim().toLowerCase();

        if (!keyword) {
            return state.items;
        }

        return state.items.filter(function (item) {
            return String(item.id).indexOf(keyword) !== -1
                || String(item.title || '').toLowerCase().indexOf(keyword) !== -1
                || String(item.cid || '').indexOf(keyword) !== -1;
        });
    }

    function renderTable() {
        var items = filteredItems();

        countEl.textContent = String(state.items.length);

        if (!state.table) {
            state.table = table.render({
                elem: '#tpl-table',
                data: items,
                skin: 'line',
                size: 'sm',
                page: { limit: 20, limits: [20, 50, 100] },
                limit: 20,
                text: {
                    none: state.items.length === 0 ? '暂无模板记录' : '没有匹配的模板'
                },
                cols: [[
                    {
                        field: 'id',
                        title: 'ID',
                        width: 72,
                        sort: true,
                        templet: function (item) {
                            return '<span class="codeditor-row-id" data-id="' + item.id + '">#' + item.id + '</span>';
                        }
                    },
                    {
                        field: 'title',
                        title: '标题',
                        minWidth: 200,
                        templet: function (item) {
                            return '<span class="codeditor-row-text">' + escapeHtml(item.title || '未命名模板') + '</span>';
                        }
                    },
                    {
                        field: 'cid',
                        title: '分类',
                        width: 80,
                        align: 'center'
                    }
                ]],
                done: highlightRow
            });

            return;
        }

        state.table.reload({
            data: items,
            page: { curr: 1 },
            text: {
                none: state.items.length === 0 ? '暂无模板记录' : '没有匹配的模板'
            },
            done: highlightRow
        });
    }

    function highlightRow() {
        var rows = document.querySelectorAll('.codeditor-table .layui-table-body tr');

        Array.prototype.forEach.call(rows, function (row) {
            var marker = row.querySelector('.codeditor-row-id[data-id="' + state.currentId + '"]');
            row.classList.toggle('is-active', Boolean(marker));
        });
    }

    function selectTemplate(id) {
        if (state.currentId === id) {
            return;
        }

        if (state.dirty && !window.confirm('当前模板尚未保存，确定切换吗？')) {
            return;
        }

        setStatus('正在读取模板 #' + id + '...');

        request(readUrl + '?id=' + encodeURIComponent(id)).then(function (result) {
            if (result.code !== 0) {
                throw new Error(result.msg || '读取模板失败');
            }

            var data = result.data || {};
            state.currentId = data.id;
            currentTitle.textContent = data.title ? (data.title + ' · #' + data.id) : ('模板 #' + data.id);
            historyBtn.disabled = false;
            setEditorValue(data.content || '');
            highlightRow();
            setStatus('模板 #' + data.id + ' 已加载');

            if (editor) {
                editor.focus();
            }
        }).catch(function (error) {
            setStatus(error.message);
            showError(error.message);
        });
    }

    function promptSave() {
        if (state.currentId === null) {
            layer.msg('请先选择左侧模板', { icon: 0 });
            return;
        }

        layer.prompt({
            title: '修改说明（可选）',
            formType: 0
        }, function (value, index) {
            layer.close(index);
            saveTemplate(value || '');
        });
    }

    function saveTemplate(intro) {
        if (state.currentId === null) {
            layer.msg('请先选择左侧模板', { icon: 0 });
            return;
        }

        if (state.saving) {
            return;
        }

        state.saving = true;
        setStatus('正在保存模板 #' + state.currentId + '...');

        request(updateUrl, {
            method: 'POST',
            body: JSON.stringify({
                id: state.currentId,
                content: editor ? editor.getValue() : '',
                intro: intro || ''
            })
        }).then(function (result) {
            if (result.code !== 0) {
                throw new Error(result.msg || '保存模板失败');
            }

            markDirty(false);
            setStatus('模板 #' + state.currentId + ' 已保存');
            layer.msg(result.data && result.data.changed === false ? '内容未变化' : '保存成功', { icon: 1, time: 1200 });
            loadList();
        }).catch(function (error) {
            setStatus(error.message);
            showError(error.message);
        }).then(function () {
            state.saving = false;
        });
    }

    function openCreate() {
        var defaultContent = editor ? editor.getValue() : '';
        var template = document.getElementById('create-form-tpl').innerHTML;

        layer.open({
            type: 1,
            title: '新增模板',
            area: ['760px', '600px'],
            content: template,
            btn: ['确定', '取消'],
            success: function (layero) {
                layero.find('textarea[name="content"]').val(defaultContent);
                form.render(null, 'codeditor-create');
                layero.find('input[name="file"]').focus();
            },
            yes: function (index, layero) {
                if (state.creating) {
                    return;
                }

                var content = layero.find('textarea[name="content"]').val();
                var intro = (layero.find('input[name="intro"]').val() || '').trim();
                var file = (layero.find('input[name="file"]').val() || '').trim();

                state.creating = true;
                setStatus('正在新增模板...');

                request(createUrl, {
                    method: 'POST',
                    body: JSON.stringify({ content: content, file: file, intro: intro })
                }).then(function (result) {
                    if (result.code !== 0) {
                        throw new Error(result.msg || '新增模板失败');
                    }

                    layer.close(index);

                    var message = '新增成功，模板 id=' + result.data.id;
                    if (result.data.file) {
                        message += '，文件 ' + result.data.file;
                    }

                    layer.msg(message, { icon: 1, time: 2000 });
                    markDirty(false);
                    state.currentId = null;
                    loadList();
                    selectTemplate(result.data.id);
                }).catch(function (error) {
                    setStatus(error.message);
                    showError(error.message);
                }).then(function () {
                    state.creating = false;
                });
            }
        });
    }

    function openHistory() {
        if (state.currentId === null) {
            layer.msg('请先选择左侧模板', { icon: 0 });
            return;
        }

        if (!window.CODEDITOR_HISTORY) {
            layer.msg('历史模块加载失败', { icon: 2 });
            return;
        }

        window.CODEDITOR_HISTORY.open({
            id: state.currentId,
            title: currentTitle.textContent
        });
    }

    function setEditorValue(value) {
        if (!editor) {
            return;
        }

        state.silent = true;
        editor.setValue(value, -1);
        state.silent = false;
        markDirty(false);
        updateStats();
    }

    function markDirty(dirty) {
        state.dirty = dirty;
        dirtyDot.className = 'codeditor-dot' + (dirty ? ' is-dirty' : '');
    }

    function updateStats() {
        if (!editor) {
            return;
        }

        var position = editor.getCursorPosition();
        var content = editor.getValue();

        statusMeta.textContent = '行 ' + (position.row + 1) + '，列 ' + (position.column + 1);
        charCount.textContent = content.length + ' 字符';
    }

    function setStatus(message) {
        statusMsg.textContent = message;
    }

    function showError(message) {
        if (layer) {
            layer.msg(message, { icon: 2, time: 3000 });
        }
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
})();