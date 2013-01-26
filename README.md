Silex-View
==========

[Silex-View][sv] is an implementation of class based views similar to [django class based views][django]
and [flask pluggable views][flask] for the php microframework [silex].

In silex you attach closures to a route. The following is a simple example.

```php
$app->get('/blog/show/{id}', function (Application $app, Request $request, $id) {
    ...
});
```

Silex does the injection of the `$app` and `$request` variables base on type hinting. When 
the route matches the closure is called, and the `$app` and `$request` is bound to 
your silex application and the currend request. Routing variables like the `$id` 
parameter can be added to the function definition as well.

This is a nice and quick way to build small application. But in my opinion putting
your controller logic in a closure leads to tighly coupled code which is difficult
to test.

The [silex documentation][sd] shows how to put your controllers in classes:

```php
$app->get('/', 'Igorw\Foo::bar');

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

namespace Igorw
{
    class Foo
    {
        public function bar(Request $request, Application $app)
        {
            ...
        }
    }
}
```

This approach is much better. Now you can [test][] your controller class with
mocked `$request` and `$application` objects. As a bonus your routing definitions
are small and clean and your conrtrollers are separated from your routing code
and can be reused.

There are two things I don't like:

* You pass your controllter class as a string to your routing function. Maybe
  this is the php way of doing things, but it does not feel right to me. On top
  it annoyes me that [phpstrom][] - my ide of choice - does not recognice it,
  so you can't click on it or use the goto definition shortcut.

* You are not able to pass arguments to the constructor of your controller.
  This is a bigger obstacle for me.


[Silex-View][sv] has as simple `BaseView` class which you can inherint from:

```php
use SilexView\BaseView

class MyView extends BaseView
{
    private $greeting;
    function __construct($greeting){
        $this->greeting = $greeting;
    } 

    function get($request, $app){
        return $this->greeting.' '.$request->get('name');
    }  
}
```

and use it in your routing definition:

```php
$app->get('/hello/{name}', MyView::asView('hello'));
```

`BaseView::asView()` is a static method which returns a closure that will be
called when the route matches:

```php
class BaseView
{
    public static function asView()
    {
        $classname= get_called_class();
        $args = func_get_args();
        return function(\Symfony\Component\HttpFoundation\Request $request, 
                        \Silex\Application $app) use ($classname, $args){
            $cls = new \ReflectionClass($classname);
            $instance = $cls->newInstanceArgs($args);
            return $instance->dispatch($request, $app);
        };
...
```

All arguments passed to the `asView` function will be forwarded to
the constructor of your inherited controller class. Inspired by the [django class based views][django]
the `BaseView` class dispaches the request based on the htttp method of the request. So a `GET` 
request will be passed to the `get(..)` method and a `POST` request to the `post(...)` method
of your controller class. With this convention it is very easy and clean to build REST Controllers.

```php
class BaseView
{
    ...
    protected $http_method_names = array('get', 'post', 'put', 'delete', 'head', 'options', 'trace');
    public function dispatch($request, $app)
    {
        $method = strtolower($request->getMethod());
        //if no head method is defined use get
        if ("head" === $method && ! method_exists($this, "head"))
            $method = "get";
        if (! (in_array($method, $this->http_method_names) && 
               method_exists($this, $method))
            return $this->httpMethodNotAllowed($method);
        return $this->$method($request, $app);
    }
```

The `TemplateView` Class is a shortcut for `GET` request which should
be rendered by a template. All you have to do is create a subclass
and implement the `getContextData()` function which should return
an array of arguments which are needed in your twig template

```php
class MyTemplate extends TemplateView
{
    function getContextData($request, $app)
    {
        return array('name' => "Joe");
    }
} 
```

The implementation is as follows:

```php
class TemplateView extends BaseView
{

    /*
     * Get the Template Name for the view
     * default implementation is to use the class name without namespace
     */
    function getTemplateName(){
        $cls = explode('\\', get_class($this));
        return end($cls).'.twig';

    }

    function get($request, $app)
    {
        return $app["twig"]->render($this->getTemplateName(), 
                                    $this->getContextData($request, $app));
    }

    function getContextData($request, $app)
    {
    }
}
```


[sv]: https://github.com/hoffmann/Silex-View
[silex]: http://silex.sensiolabs.org/
[sd]: http://silex.sensiolabs.org/doc/usage.html#controllers-in-classes
[test]: http://silex.sensiolabs.org/doc/testing.html
[phpstrom]: www.jetbrains.com/phpstorm/
[django]: https://docs.djangoproject.com/en/1.5/topics/class-based-views/
[flask]: http://flask.pocoo.org/docs/views/ 
