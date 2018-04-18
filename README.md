# laravel 多请求合并处理代理


## 环境

此项目支持PHP5.6，更改其中 `??` 运算符即可

## 使用

 `app/Exceptions/Handler.php 文件 重写此方法`

  ```
     /**
       * Render an exception into an HTTP response.
       *
       * @param \Illuminate\Http\Request $request
       * @param Exception $exception
       * @return Exception|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
       */
      public function render($request, Exception $exception)
      {
          // api路由错误直接返回
          if ($request->is('api*')) {

              // 程序错误直接退出
              if ($exception instanceof FatalErrorException) {

                  return response()->json([
                      'code'      => $exception->getCode()    ?? 1000000,
                      'message'   => $exception->getMessage() ?? 'Exception Unknown.',
                  ]);
              }

              return  $exception;
          }

          return parent::render($request, $exception);
      }
  ```
  
## 请求
  
  `{"foo.a":{"params1":[1,2],"params2":"123"},"foo.b":{"params":"asd"}}`

##  路由
  
  ```
    Route::post('/', 'FooController@index');
    Route::post('/a', 'FooController@a')->name('foo.a');
    Route::post('/b', 'FooController@b')->name('foo.b');
  ```
  
