<?php

declare(strict_types=1);

use think\facade\Route;
use Youvision\ThinkStoreCodeditor\AssetController;
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

$routes = [$index, $read, $update, $create, $assets];

if (in_array('', $routes, true) || count(array_unique($routes)) !== count($routes)) {
    return;
}

$register = static function () use ($index, $read, $update, $create, $assets): void {
    Route::get($index, CodeditorController::class . '@index')->name('codeditor.index')->completeMatch();
    Route::get($read, CodeditorController::class . '@read')->name('codeditor.read')->completeMatch();
    Route::post($update, CodeditorController::class . '@update')->name('codeditor.update')->completeMatch();
    Route::post($create, CodeditorController::class . '@create')->name('codeditor.create')->completeMatch();

    // 包内静态资源直接由路由输出，无需发布到宿主 public
    Route::get($assets . '/<path>', [AssetController::class, 'file'])
        ->pattern(['path' => '.*'])
        ->name('codeditor.asset');
};

$group = Route::group($prefix, $register);
$group->middleware($middleware);