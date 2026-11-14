<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 内置路由
    |--------------------------------------------------------------------------
    | 控制页面、接口与静态资源路由是否随服务自动注册。
    | 最终地址为 /{prefix}/{rule}。
    */
    'route' => [
        // 是否启用内置路由；关闭后可在应用路由文件中自行定义
        'enabled' => true,

        // 路由前缀，留空表示不添加前缀，例如 store、api/store
        'prefix' => 'store',

        // 应用到整组路由上的中间件，默认启用包内 Auth
        // 需要接入主项目鉴权时可替换为 [\app\middleware\Auth::class]
        'middleware' => [
            \Youvision\ThinkStoreCodeditor\middleware\Auth::class,
        ],

        // 页面、接口与资源路由规则，均相对于 prefix
        'rules' => [
            // 编辑器页面，GET 请求
            'index' => 'think-store-codeditor/index',

            // 读取接口，GET 请求；带 id 读取单条，不带 id 返回模板列表
            'read' => 'think-store-codeditor/read',

            // 更新接口，POST 请求；按 id 更新模板内容
            'update' => 'think-store-codeditor/update',

            // 新增接口，POST 请求；新增模板记录，可选同时创建模板文件
            'create' => 'think-store-codeditor/create',
        ],

        // 包内静态资源规则前缀，最终地址为 /{prefix}/{assets}/<path>
        'assets' => 'assets',
    ],

    /*
    |--------------------------------------------------------------------------
    | 访问认证
    |--------------------------------------------------------------------------
    | 页面、接口与静态资源路由默认经过包内 Auth 中间件。
    | 主项目已有登录系统时配置 session_key；否则使用 HTTP Basic Auth。
    */
    'auth' => [
        // 是否启用访问认证；false 时 Auth 中间件直接放行
        'enabled' => true,

        // 主项目登录态 Session 键，值为真时放行，例如 admin_id
        'session_key' => '',

        // HTTP Basic Auth 用户名
        'username' => 'admin',

        // HTTP Basic Auth 密码；留空时未命中 Session 的请求返回 503
        'password' => '',

        // Basic Auth 提示域
        'realm' => 'Think Store Codeditor',
    ],

    /*
    |--------------------------------------------------------------------------
    | 模板数据表
    |--------------------------------------------------------------------------
    | 读取和写入的数据源，字段名可自由映射，默认读取 template 表的
    | id 与 newstext 字段。表名不含数据库前缀，由框架自动拼接。
    */
    'database' => [
        // 数据库连接名，留空使用主项目默认连接（database.default）
        'connection' => '',

        // 模板数据表名，不含表前缀
        'table' => 'template',

        // 模板主键字段名
        'id_field' => 'id',

        // 模板主输出路径
        'path_field' => 'path',

        // 模板内容字段名，编辑器内容保存到该字段
        'content_field' => 'newstext',

        // 模板列表接口最多返回的记录数，避免大表拖慢页面
        'list_limit' => 200,

        // 模板列表摘要的最大字符数，0 表示不截断；只影响列表展示，不影响正文
        'excerpt_length' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | 可编辑目录与模板文件
    |--------------------------------------------------------------------------
    | 读取与更新接口只操作数据库，不读写磁盘。仅在新增模板时，若请求
    | 传入 file 参数，才会在受限目录内创建对应的 HTML/PHP 模板文件。
    | 所有文件路径都会做白名单校验，禁止目录穿越与任意扩展名写入。
    */
    'file' => [
        // 是否允许新增模板时创建文件；关闭后新增接口只写入数据库
        'enabled' => true,

        // 模板根目录：相对主项目根目录，或填写绝对路径
        // 例如主项目使用 view 目录时改为 view
        'root' => 'template',

        // 可编辑目录白名单，相对 root，file 必须落在其中之一
        // 例如只允许编辑 template/default 时填写 ['default']
        // 留空数组表示不允许创建任何模板文件
        'allow_dirs' => ['default'],

        // 允许创建的文件扩展名，统一转为小写比较
        'ext' => ['html'],

        // 目标文件已存在时是否覆盖；false 时新增接口会直接报错
        'overwrite' => false,

        // 目标文件的父目录不存在时是否自动创建
        'create_dir' => true,
    ],
];