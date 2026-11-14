<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor;

use think\Response;

/**
 * 包内静态资源路由。
 *
 * 直接读取 theme/assets 目录并输出，不需要发布到宿主 public；
 * 所有路径都经过 realpath 校验，拒绝目录穿越。
 */
class AssetController
{
    private const MIME = [
        'css'   => 'text/css; charset=utf-8',
        'js'    => 'application/javascript; charset=utf-8',
        'json'  => 'application/json; charset=utf-8',
        'map'   => 'application/json; charset=utf-8',
        'svg'   => 'image/svg+xml',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'eot'   => 'application/vnd.ms-fontobject',
        'html'  => 'text/html; charset=utf-8',
    ];

    public function file(string $path): Response
    {
        $file = self::resolve($path);

        if ($file === null) {
            return response('Not Found', 404);
        }

        return response((string) file_get_contents($file), 200, [
            'Content-Type'  => self::mime($file),
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * 解析 theme/assets 内的相对路径，越界或文件不存在时返回 null。
     */
    public static function resolve(string $path): ?string
    {
        $base = realpath(dirname(__DIR__) . '/theme/assets');

        if ($base === false) {
            return null;
        }

        $path = str_replace('\\', '/', trim($path));

        if ($path === '' || strpos($path, '..') !== false) {
            return null;
        }

        $file = realpath($base . '/' . $path);

        if ($file === false || !is_file($file)) {
            return null;
        }

        $prefix = $base . DIRECTORY_SEPARATOR;

        return strncmp($file, $prefix, strlen($prefix)) === 0 ? $file : null;
    }

    public static function mime(string $file): string
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        return self::MIME[$extension] ?? 'application/octet-stream';
    }
}