# Think Store Codeditor

本目录是 `youvision/think-store-codeditor` 的静态资源实例，页面基于 layui 与 Ace Editor 构建。

- `index.html`：模板代码编辑器，HTML + PHP 混合高亮，对接读取、更新、新增三个接口
- `view/index.html`：HTML + PHP 混合模板片段示例
- `theme/ace/`：Ace Editor 完整本地库，保持原样，不新增或修改文件
- `theme/layui/`：layui 框架目录，保持原样，不新增或修改文件
- `theme/css/codeditor.css`：项目自定义样式

部署后访问 `/store/store-codeditor/index.html`。

页面通过 `<div class="codeditor-shell" data-api="/codeditor">` 指定接口前缀，修改 `config/codeditor.php` 的 `route.prefix` 后请同步调整该属性。

修改资源后执行 `composer dump-autoload` 重新发布。