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
                'code'      => $response->getCode()     ?? 1000000,
                'message'   => $response->getMessage()  ?? 'Exception Unknown.',
            ]);
        }

        return  $this->returnJson([
            'code'      => 0,
            'message'   => 'OK',
            'data'      => is_null(json_decode($response->content(), true)) ?? $response->content(),
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
