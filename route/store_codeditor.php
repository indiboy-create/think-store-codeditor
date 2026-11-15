<?php

declare(strict_types=1);

use think\facade\Route;
use Youvision\ThinkStoreCodeditor\AssetController;
use Youvision\ThinkStoreCodeditor\CategoryController;
use Youvision\ThinkStoreCodeditor\CodeditorController;
use Youvision\ThinkStoreCodeditor\TemplateHistoryController;

/*
 * 模板编辑器模块路由。
 *
 * 由 Youvision\ThinkStoreCodeditor\Service 在 boot 阶段加载，
 * 宿主项目引入本包后无需再复制路由文件。
 *
 * 前缀、命名空间与中间件来自 config/store_codeditor.php：
 * - store_codeditor.route.enabled    是否注册内置路由
 * - store_codeditor.route.prefix     路由前缀，默认 store
 * - store_codeditor.route.segment    包内命名空间，默认 think-store-codeditor
 * - store_codeditor.route.middleware 应用到整组路由的中间件
 */
$prefix     = trim((string) config('store_codeditor.route.prefix', 'store'), '/');
$segment    = trim((string) config('store_codeditor.route.segment', 'think-store-codeditor'), '/');
$middleware = (array) config('store_codeditor.route.middleware', []);

$register = static function () use ($segment): void {
    $base = $segment === '' ? '' : $segment . '/';

    Route::get($base . 'index', [CodeditorController::class, 'index'])->name('store_codeditor.index')->completeMatch();
    Route::get($base . 'template', [CodeditorController::class, 'template'])->name('store_codeditor.template')->completeMatch();
    Route::get($base . 'category', [CategoryController::class, 'index'])->name('store_codeditor.category')->completeMatch();

    // iframe 版简易管理页，复用现有 JSON 接口
    Route::get($base . 'iframe/template', [CodeditorController::class, 'iframe'])
        ->name('store_codeditor.iframe.template')
        ->completeMatch();
    Route::get($base . 'iframe/category', [CategoryController::class, 'iframe'])
        ->name('store_codeditor.iframe.category')
        ->completeMatch();


    // JSON 接口统一挂在 api 段下，与页面路由区分
    Route::get($base . 'api/template/read', [CodeditorController::class, 'read'])
        ->name('store_codeditor.template.read')
        ->completeMatch();
    Route::post($base . 'api/template/update', [CodeditorController::class, 'update'])
        ->name('store_codeditor.template.update')
        ->completeMatch();
    Route::post($base . 'api/template/create', [CodeditorController::class, 'create'])
        ->name('store_codeditor.template.create')
        ->completeMatch();

    Route::get($base . 'api/template/history', [TemplateHistoryController::class, 'lists'])
        ->name('store_codeditor.template.history')
        ->completeMatch();
    Route::get($base . 'api/template/history/detail', [TemplateHistoryController::class, 'detail'])
        ->name('store_codeditor.template.history.detail')
        ->completeMatch();
    Route::post($base . 'api/template/history/restore', [TemplateHistoryController::class, 'restore'])
        ->name('store_codeditor.template.history.restore')
        ->completeMatch();

    Route::get($base . 'api/category/read', [CategoryController::class, 'read'])
        ->name('store_codeditor.category.read')
        ->completeMatch();
    Route::post($base . 'api/category/create', [CategoryController::class, 'create'])
        ->name('store_codeditor.category.create')
        ->completeMatch();
    Route::post($base . 'api/category/update', [CategoryController::class, 'update'])
        ->name('store_codeditor.category.update')
        ->completeMatch();
    Route::post($base . 'api/category/delete', [CategoryController::class, 'delete'])
        ->name('store_codeditor.category.delete')
        ->completeMatch();

    // 静态资源直接由路由输出，无需发布到 public
    Route::get($base . 'assets/<path>', [AssetController::class, 'file'])
        ->pattern(['path' => '.*'])
        ->name('store_codeditor.asset');
};

$group = Route::group($prefix, $register);
$group->middleware($middleware);