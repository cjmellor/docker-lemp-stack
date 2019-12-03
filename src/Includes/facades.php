<?php

use Illuminate\Container\Container;

class Facades
{
    /**
     * The key for the binding in the container.
     *
     * @return string
     */
    public static function containerKey()
    {
        return 'Saber\\' . basename(str_replace('\\', '/', get_called_class()));
    }

    /**
     * Call a non-static method on the facade.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $resolvedInstance = Container::getInstance()->make(static::containerKey());

        return call_user_func_array([$resolvedInstance, $method], $parameters);
    }
}

class Docker extends Facades
{ }
class Shell extends Facades
{ }
class File extends Facades
{ }
class Config extends Facades
{ }
class App extends Facades
{ }
class Secure extends Facades
{ }
