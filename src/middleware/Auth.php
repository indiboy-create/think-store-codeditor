<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor\middleware;

use Closure;
use think\Request;
use think\Response;
use Throwable;

/**
 * 包内访问认证中间件。
 *
 * 优先校验主项目 Session 登录态；未命中时回退到 HTTP Basic Auth。
 * 未配置密码时拒绝访问，避免默认弱口令。
 */
class Auth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!(bool) config('store_codeditor.auth.enabled', true)) {
            return $next($request);
        }

        if ($this->authenticatedBySession($request)) {
            return $next($request);
        }

        [$username, $password] = $this->credentials($request);

        $expectedUser = (string) config('store_codeditor.auth.username', 'admin');
        $expectedPass = (string) config('store_codeditor.auth.password', '');

        if ($expectedPass === '') {
            return $this->deny(
                $request,
                '未配置访问密码，请在 config/store_codeditor.php 的 auth.password 或 auth.session_key 中配置认证',
                503,
                false
            );
        }

        if (hash_equals($expectedUser, $username) && hash_equals($expectedPass, $password)) {
            return $next($request);
        }

        return $this->deny($request, '需要登录后访问', 401, true);
    }

    /**
     * 主项目已有登录系统时，按配置的 Session 键判断登录态。
     */
    protected function authenticatedBySession(Request $request): bool
    {
        $key = trim((string) config('store_codeditor.auth.session_key', ''));

        if ($key === '') {
            return false;
        }

        try {
            return (bool) session($key);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * 读取 Basic Auth 凭据，兼容 PHP_AUTH_* 与 Authorization 头。
     *
     * @return array{0:string,1:string}
     */
    protected function credentials(Request $request): array
    {
        $username = (string) $request->server('PHP_AUTH_USER', '');
        $password = (string) $request->server('PHP_AUTH_PW', '');

        if ($username !== '') {
            return [$username, $password];
        }

        $header = (string) $request->header('authorization', '');

        if (stripos($header, 'basic ') !== 0) {
            return ['', ''];
        }

        $decoded = base64_decode(substr($header, 6), true);

        if ($decoded === false || !str_contains($decoded, ':')) {
            return ['', ''];
        }

        [$username, $password] = explode(':', $decoded, 2);

        return [$username, $password];
    }

    /**
     * 构造拒绝响应：接口返回 JSON，页面返回纯文本。
     */
    protected function deny(Request $request, string $message, int $status, bool $challenge): Response
    {
        $headers = [];

        if ($challenge) {
            $realm = str_replace(['\\', '"'], '', (string) config('store_codeditor.auth.realm', 'Think Store Codeditor'));
            $headers['WWW-Authenticate'] = 'Basic realm="' . $realm . '", charset="UTF-8"';
        }

        if ($this->wantsJson($request)) {
            return json([
                'code' => 1,
                'msg'  => $message,
                'data' => null,
            ], $status, $headers);
        }

        return response($message, $status, $headers);
    }

    protected function wantsJson(Request $request): bool
    {
        $accept = strtolower((string) $request->header('accept', ''));

        return str_contains($accept, 'application/json') || $request->isAjax();
    }
}