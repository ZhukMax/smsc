<?php

namespace Zhukmax\Smsc\Tests;

use ReflectionClass;
use ReflectionException;

trait Helper
{
    protected function accessProtected($obj, $prop) {
        try {
            $reflection = new ReflectionClass($obj);
            $property = $reflection->getProperty($prop);
            $property->setAccessible(true);

            return $property->getValue($obj);
        } catch (ReflectionException $e) {
            return null;
        }
    }
}
