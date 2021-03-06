<?php

namespace Weboccult\EatcardCompanion\Enums;

use ReflectionClass;

/**
 * Class BaseEnum.
 */
abstract class BaseEnum
{
    private static $constCacheArray = null;

    /**
     * @throws \ReflectionException
     *
     * @return mixed
     */
    public static function getConstants()
    {
        if (self::$constCacheArray == null) {
            self::$constCacheArray = [];
        }
        $calledClass = get_called_class();
        if (! array_key_exists($calledClass, self::$constCacheArray)) {
            $reflect = new ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }

        return self::$constCacheArray[$calledClass];
    }

    /**
     * @throws \ReflectionException
     *
     * @return mixed
     */
    public static function getConstantValues()
    {
        return array_values(self::getConstants());
    }

    /**
     * @param $name
     * @param bool $strict
     *
     * @throws \ReflectionException
     *
     * @return bool
     */
    public static function isValidName($name, $strict = false)
    {
        $constants = self::getConstants();
        if ($strict) {
            return array_key_exists($name, $constants);
        }
        $keys = array_map('strtolower', array_keys($constants));

        return in_array(strtolower($name), $keys);
    }

    /**
     * @param $value
     * @param bool $strict
     *
     * @throws \ReflectionException
     *
     * @return bool
     */
    public static function isValidValue($value, $strict = true)
    {
        $values = array_values(self::getConstants());

        return in_array($value, $values, $strict);
    }
}
