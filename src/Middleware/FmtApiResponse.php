<?php

namespace Zhuud\Proxy\Middleware;

use Illuminate\Http\JsonResponse;
use Closure;


/**
 * Class FmtApiResponse
 * @package Zhuud\Proxy\Middleware
 */
class FmtApiResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (!$request->is('api*')) {

            return  $response;
        }

        // 路由没有代理的情况
        if ($response instanceof \Exception) {

            return $this->returnJson([
                'code'      => empty($code = $response->getCode())   ? config('error.default.code')  : $code,
                'message'   => empty($msg = $response->getMessage())    ? config('error.default.msg')   : $msg,
            ]);
        }

        $data = json_decode($response->content(), true);
        return  $this->returnJson([
            'code'      => 0,
            'message'   => 'OK',
            'data'      => is_null($data) ? $response->content()    : $data,
        ]);
    }

    /**
     * return json response
     * 
     * @param array $data
     * @return JsonResponse
     */
    private function returnJson(array $data)
    {
        return  new JsonResponse($data);
    }
}
