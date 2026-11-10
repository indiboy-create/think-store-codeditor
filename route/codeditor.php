<?php

declare(strict_types=1);

use think\facade\Route;
use Youvision\ThinkStoreCodeditor\TemplateController;

$settings   = (array) config('codeditor.route', []);
$rules      = (array) ($settings['rules'] ?? []);
$middleware = (array) ($settings['middleware'] ?? []);
$prefix     = trim((string) ($settings['prefix'] ?? 'codeditor'), '/');

$read   = trim((string) ($rules['read'] ?? 'read'), '/');
$update = trim((string) ($rules['update'] ?? 'update'), '/');
$create = trim((string) ($rules['create'] ?? 'create'), '/');

if ($read === '' || $update === '' || $create === '' || count(array_unique([$read, $update, $create])) !== 3) {
    return;
}

if ($prefix === '') {
    Route::get($read, TemplateController::class . '@read')->middleware($middleware);
    Route::post($update, TemplateController::class . '@update')->middleware($middleware);
    Route::post($create, TemplateController::class . '@create')->middleware($middleware);

    return;
}

Route::group($prefix, function () use ($read, $update, $create): void {
    Route::get($read, TemplateController::class . '@read');
    Route::post($update, TemplateController::class . '@update');
    Route::post($create, TemplateController::class . '@create');
})->middleware($middleware);