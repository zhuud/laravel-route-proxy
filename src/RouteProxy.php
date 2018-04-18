<?php

namespace Zhuud\Proxy;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;
use Illuminate\Routing\Pipeline;


/**
 * Class RouteProxy
 * @package Zhuud\Proxy
 */
class RouteProxy
{
    /**
     * this
     */
    private static  $instance;

    /**
     * default max calls
     */
    private $maxCalls = 10;

    /**
     * the result of return
     */
    private $result;


    /**
     * Create a Proxy instance from a string.
     *
     * @return static
     */
    public function parse()
    {
        if (!self::$instance instanceof static) {

            self::$instance = new static();
        }

        return  self::$instance;
    }

    /**
     * RouteProxy constructor.
     */
    private function __construct() {}

    /**
     * No cloning method
     */
    private function __clone() {}

    /**
     * Set the maximum number of routes requested at a time
     *
     * @param $maxCalls
     * @return $this
     */
    public function setMaxCalls($maxCalls)
    {
        $this->maxCalls = $maxCalls;

        return  $this;
    }

    /**
     * verify the number of calls
     *
     * @param Request $request
     */
    public function verMaxCalls(Request $request)
    {
        $listAction = array_keys($request->all());

        if ($this->maxCalls < count($listAction)) {

            throw new \LengthException('Too many calls given', 1000001);
        }
    }

    /**
     * dispatch route request
     *
     * @param Request $request
     * @param \Illuminate\Routing\RouteCollection $listRoute
     * @return $this
     */
    public function dispatch(Request $request, RouteCollection $listRoute)
    {
        $listRequest = $request->all();

        array_walk($listRequest, function ($params, $routeName) use ($request, $listRoute) {

            if ($curRoute = $listRoute->getByName($routeName)) {

                // 设置当前路由解析
                $request->setRouteResolver(function() use($curRoute) {
                    return $curRoute;
                });

                // 生成分发request
                $curRequest = $this->genCurRequest($request, $params);

                //$this->container->instance(Request::class, $curRequest);
                //$this->container->instance(Route::class, $curRoute);

                // 获取当前request的中间件
                $listMiddleware = $this->getCurMiddleware($curRoute);

                // 走中间件、逻辑
                $content = $this->throughMiddleware($curRequest, $curRoute, $listMiddleware);

                // 处理结果
                $this->handleResult($content, $routeName);

            } else {

                $this->result[$routeName] = [
                    'code'      => 1000002,
                    'message'   => 'Action invalid.',
                ];
            }
        });

        return  $this;
    }

    /**
     * Get a collection of all results
     *
     * @return mixed
     */
    public function getResult()
    {
        return  $this->result;
    }

    /**
     * Generate the current request
     *
     * @param Request $request
     * @param $params
     * @return Request $request
     */
    private function genCurRequest(Request $request, $params)
    {
        $curRequest = $request->duplicate();

        $curRequest->json()->replace($params);

        return  $curRequest;
    }

    /**
     * Get the current Middleware
     *
     * @param Route $curRoute
     * @return array
     */
    private function getCurMiddleware(Route $curRoute)
    {
        return  collect(app('router')->getMiddleware())
            ->only($curRoute->middleware())
            ->toArray();
    }

    /**
     * through the Middleware
     *
     * @param Request $curRequest
     * @param Route $curRoute
     * @param $listMiddleware
     * @return mixed
     */
    private function throughMiddleware(Request $curRequest, Route $curRoute, $listMiddleware)
    {
        return  (new Pipeline(App::class))
            ->send($curRequest)
            ->through(App::shouldSkipMiddleware() ? [] : $listMiddleware)
            ->then(function ($curRequest) use ($curRoute) {

                $response = $curRoute->bind($curRequest)->run();

                return  app('router')->prepareResponse($curRequest, $response);
            });
    }

    /**
     * handle the result
     *
     * @param \Illuminate\Http\Response $content
     * @param $routeName
     */
    private function handleResult($content, $routeName)
    {
        // 代理路由过程中抛错的情况
        if ($content instanceof \Exception) {

            $this->result[$routeName] = [
                'code'    => $content->getCode()    ? $content->getCode()       : 1000003,
                'message' => $content->getMessage() ? $content->getMessage()    : 'Exception unknown.',
            ];
        // 正常情况
        } else {

            $this->result[$routeName] = [
                'code'    => 0,
                'message' => 'OK',
                'data'    => json_decode($content->content(), true),
            ];
        }
    }

}

