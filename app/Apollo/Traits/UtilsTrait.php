<?php

namespace App\Apollo\Traits;

trait UtilsTrait
{

    /**
     * From Array, return array structure (useful for PHPUnit 'assertJsonStructure()')
     *
     * @param  type  $var: Array of fields
     * @return array
     */
    public static function getArrayStructure($var)
    {
        $ret = null;
        if (is_array($var)) {
            foreach ($var as $k => $v) {
                if (is_array($var[$k])) {
                    if (is_null($ret)) {
                        $ret = [];
                    }
                    if (is_numeric($k)) {
                        $ret['*'] = self::getArrayStructure($v);
                    } else {
                        $ret[$k] = self::getArrayStructure($v);
                    }
                } else {
                    $ret[] = $k;
                }
            }
        } else {
            // Only if the first time is not an array.
            $ret = $var;
        }

        return $ret;
    }

    /**
     * Check if '$ip' match the given '$range'
     *
     * @param  type  $ip: Network IP to check with 'range'
     * @param  type  $range: Network range; something like '93.63.207.206/8', '10.0.0.0/8'
     * @return bool
     */
    public static function cidr_match($ip, $range)
    {
        if (strpos($range, '/') !== false) {
            list($subnet, $bits) = explode('/', $range);
        } else {
            $subnet = $range;
            $bits = 32;
        }

        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask; // nb: in case the supplied subnet wasn't correctly aligned

        return ($ip & $mask) == $subnet;
    }

    /**
     * Remove the array elements starting with 'fk_'.
     * Example:
     *  input:
     *   $array = ['name', 'surname', 'fk_address'];
     *  output:
     *   $array = ['name', 'surname'];
     *
     * @param  type  $array: Array of fields
     * @return array
     */
    public static function removeForeignKeyFields($array)
    {
        $t = [];
        if (is_array($array)) {
            foreach ($array as $value) {
                if (!str_starts_with($value, 'fk_')) {
                    $t[] = $value;
                }
            }
        }

        return $t;
    }

    /**
     * Remove the array elements where the array keys start with $substr.
     * Example:
     *  input:
     *   $array = ['a' => 'dog', 'b' => 'cat', 'fk_c' => 'bird'];
     *  command:
     *   removeKeyElementFromArrayUsingSubstr($array, $substr = 'fk_')
     *  output:
     *   $array = ['a' => 'dog', 'b' => 'cat'];
     *
     * @param  type  $array: Array of fields
     * @return array
     */
    public static function removeKeyElementFromArrayUsingSubstr($array, $substr)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (strpos($key, $substr) === 0) {
                    unset($array[$key]);
                }
            }
        }

        return $array;
    }
}
