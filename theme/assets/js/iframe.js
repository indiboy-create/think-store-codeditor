(function () {
  'use strict';

  var dataNode = document.getElementById('codeditor-iframe-pages');
  var preview = document.getElementById('codeditor-iframe-preview');
  var current = document.getElementById('codeditor-iframe-current');
  var openLink = document.getElementById('codeditor-iframe-open');
  var count = document.getElementById('codeditor-iframe-count');
  var pages = [];

  try {
    pages = JSON.parse(dataNode ? String(dataNode.textContent || '[]') : '[]');
  } catch (error) {
    pages = [];
  }

  if (!Array.isArray(pages)) {
    pages = [];
  }

  if (count) {
    count.textContent = pages.length + ' 个';
  }

  function escapeHtml(value) {
    return String(value === undefined || value === null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function setPreview(page) {
    if (!page || !page.path || !preview) {
      return;
    }

    preview.src = page.path;

    if (current) {
      current.textContent = page.path;
    }

    if (openLink) {
      openLink.href = page.path;
    }

    var previewBox = preview.closest ? preview.closest('.codeditor-iframe__preview') : null;

    if (previewBox && previewBox.scrollIntoView) {
      previewBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  if (!window.layui || typeof window.layui.use !== 'function') {
    return;
  }

  window.layui.use(['table'], function () {
    var table = window.layui.table;

    table.render({
      elem: '#codeditor-iframe-table',
      id: 'codeditor-iframe-table',
      data: pages,
      page: false,
      even: true,
      text: { none: '暂无可内嵌页面' },
      cols: [[
        { type: 'numbers', title: '序号', width: 60 },
        {
          field: 'name',
          title: '页面名称',
          width: 170,
          templet: function (row) {
            return '<strong>' + escapeHtml(row.name) + '</strong>';
          }
        },
        {
          field: 'path',
          title: '页面地址',
          minWidth: 260,
          templet: function (row) {
            return '<code class="codeditor-iframe__path">' + escapeHtml(row.path) + '</code>';
          }
        },
        { field: 'description', title: '说明', minWidth: 230 },
        {
          title: '操作',
          width: 180,
          align: 'center',
          templet: function (row) {
            return '<a class="layui-btn layui-btn-xs" lay-event="view">预览</a>'
              + '<a class="layui-btn layui-btn-primary layui-btn-xs" href="'
              + escapeHtml(row.path) + '" target="_blank" rel="noopener">新窗口</a>';
          }
        }
      ]]
    });

    table.on('tool(codeditor-iframe-table)', function (obj) {
      if (obj.event === 'view') {
        setPreview(obj.data);
      }
    });
  });
})();