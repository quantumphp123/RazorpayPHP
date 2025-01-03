<?php
namespace App\Core;

class Middleware {
    private static $middlewares = [];

    public static function register($name, $callback) {
        self::$middlewares[$name] = $callback;
    }

    public static function apply($names, $callback) {
        foreach ((array)$names as $name) {
            if (isset(self::$middlewares[$name])) {
                $result = call_user_func(self::$middlewares[$name]);
                if ($result === false) {
                    return false;
                }
            }
        }
        return call_user_func($callback);
    }
}
