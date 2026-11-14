<?php

declare(strict_types=1);

use think\facade\Route;
use Youvision\ThinkStoreCodeditor\AssetController;
use Youvision\ThinkStoreCodeditor\CategoryController;
use Youvision\ThinkStoreCodeditor\CodeditorController;

$settings   = (array) config('store_codeditor.route', []);
$rules      = (array) ($settings['rules'] ?? []);
$middleware = (array) ($settings['middleware'] ?? []);
$prefix     = trim((string) ($settings['prefix'] ?? 'store'), '/');
$assets     = trim((string) ($settings['assets'] ?? 'assets'), '/');

$index   = trim((string) ($rules['index'] ?? 'think-store-codeditor/index'), '/');
$read    = trim((string) ($rules['read'] ?? 'think-store-codeditor/read'), '/');
$update  = trim((string) ($rules['update'] ?? 'think-store-codeditor/update'), '/');
$create  = trim((string) ($rules['create'] ?? 'think-store-codeditor/create'), '/');

$category       = trim((string) ($rules['category'] ?? 'think-store-codeditor/category'), '/');
$categoryRead   = trim((string) ($rules['category_read'] ?? 'think-store-codeditor/category/read'), '/');
$categoryCreate = trim((string) ($rules['category_create'] ?? 'think-store-codeditor/category/create'), '/');
$categoryUpdate = trim((string) ($rules['category_update'] ?? 'think-store-codeditor/category/update'), '/');
$categoryDelete = trim((string) ($rules['category_delete'] ?? 'think-store-codeditor/category/delete'), '/');

$routes = [
    $index,
    $read,
    $update,
    $create,
    $assets,
    $category,
    $categoryRead,
    $categoryCreate,
    $categoryUpdate,
    $categoryDelete,
];

if (in_array('', $routes, true) || count(array_unique($routes)) !== count($routes)) {
    return;
}

$register = static function () use (
    $index,
    $read,
    $update,
    $create,
    $assets,
    $category,
    $categoryRead,
    $categoryCreate,
    $categoryUpdate,
    $categoryDelete
): void {
    Route::get($index, CodeditorController::class . '@index')->name('codeditor.index')->completeMatch();
    Route::get($read, CodeditorController::class . '@read')->name('codeditor.read')->completeMatch();
    Route::post($update, CodeditorController::class . '@update')->name('codeditor.update')->completeMatch();
    Route::post($create, CodeditorController::class . '@create')->name('codeditor.create')->completeMatch();

    Route::get($category, CategoryController::class . '@index')->name('codeditor.category.index')->completeMatch();
    Route::get($categoryRead, CategoryController::class . '@read')->name('codeditor.category.read')->completeMatch();
    Route::post($categoryCreate, CategoryController::class . '@create')->name('codeditor.category.create')->completeMatch();
    Route::post($categoryUpdate, CategoryController::class . '@update')->name('codeditor.category.update')->completeMatch();
    Route::post($categoryDelete, CategoryController::class . '@delete')->name('codeditor.category.delete')->completeMatch();

    // 包内静态资源直接由路由输出，无需发布到宿主 public
    Route::get($assets . '/<path>', [AssetController::class, 'file'])
        ->pattern(['path' => '.*'])
        ->name('codeditor.asset');
};

$group = Route::group($prefix, $register);
$group->middleware($middleware);