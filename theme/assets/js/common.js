(function () {
  'use strict';

  if (window.layui && typeof window.layui.use === 'function') {
    window.layui.use(['element'], function () {});
  }

  var header = document.querySelector('.codeditor-header');
  var toggle = header && header.querySelector('.codeditor-nav-toggle');
  var nav = header && header.querySelector('.codeditor-nav');

  if (!header || !toggle || !nav) {
    return;
  }

  var icon = toggle.querySelector('.layui-icon');

  function setOpen(open) {
    nav.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.setAttribute('aria-label', open ? '收起导航' : '打开导航');

    if (icon) {
      icon.classList.toggle('layui-icon-spread-left', !open);
      icon.classList.toggle('layui-icon-close', open);
    }
  }

  toggle.addEventListener('click', function (event) {
    event.stopPropagation();
    setOpen(!nav.classList.contains('is-open'));
  });

  nav.addEventListener('click', function (event) {
    if (event.target.closest('a')) {
      setOpen(false);
    }
  });

  document.addEventListener('click', function (event) {
    if (!header.contains(event.target)) {
      setOpen(false);
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      setOpen(false);
    }
  });

  window.addEventListener('resize', function () {
    if (window.matchMedia('(min-width: 821px)').matches) {
      setOpen(false);
    }
  });
})();