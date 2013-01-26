<?php

namespace SilexView;

class BaseView
{
    protected $http_method_names = array('get', 'post', 'put', 'delete', 'head', 'options', 'trace');


    public static function asView()
    {
        $classname= get_called_class();
        $args = func_get_args();
        return function(\Symfony\Component\HttpFoundation\Request $request, \Silex\Application $app) use ($classname, $args){
            $cls = new \ReflectionClass($classname);
            $instance = $cls->newInstanceArgs($args);
            return $instance->dispatch($request, $app);
        };
    }

    public function dispatch($request)
    {
        $method = strtolower($request->getMethod());
        //if no head method is defined use get
        if ("head" === $method && ! method_exists($this, "head"))
            $method = "get";
        if (! in_array($method, $this->http_method_names) || ! method_exists($this, $method))
            return $this->httpMethodNotAllowed($method);
        return $this->$method($request, $app);
    }

    public function httpMethodNotAllowed($method)
    {
         $method = htmlspecialchars($method);
         throw new Exception('http method '.$method.' not allowed');
    }
}
