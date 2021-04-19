<?php

namespace Zhukmax\Smsc\Tests;

use ReflectionClass;
use ReflectionException;

trait Helper
{
    public function accessProtectedProperty($object, $prop)
    {
        try {
            $reflection = new ReflectionClass($object);
            $property = $reflection->getProperty($prop);
            $property->setAccessible(true);

            return $property->getValue($object);
        } catch (ReflectionException $e) {
            return null;
        }
    }

    public static function callProtectedMethod($object, $method, array $args = [])
    {
        try {
            $class = new ReflectionClass(get_class($object));
            $method = $class->getMethod($method);
            $method->setAccessible(true);

            return $method->invokeArgs($object, $args);
        } catch (ReflectionException $e) {
            return null;
        }
    }
}
