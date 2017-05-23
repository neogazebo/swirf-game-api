<?php


if (!function_exists('array2object')) {
    function array2object($array)
    {
        if (!is_array($array)) {
            return $array;
        }
        $object = new stdClass();
        if (is_array($array) && count($array) > 0) {
            foreach ($array as $name => $value) {
                $name = strtolower(trim($name));
                if (is_array($value)) {
                    $object->$name = array2object($value);
                } else {
                    $object->$name = $value;
                }
            }
            return $object;
        } else {
            return false;
        }
    }
}

if (!(function_exists('object2array'))) {
    function object2array($object)
    {
        return json_decode(json_encode($object), true, 512 , JSON_BIGINT_AS_STRING);
    }
}

if (!(function_exists('text_decode'))) {
    function text_decode($text)
    {
        return mb_convert_encoding($text, 'UTF-8');
    }
}

if (!(function_exists('is_nil'))) {
    function is_nil($object)
    {
        if (is_array($object))
        {
            if (count($object) == 0)
            {
                return true;
            }
            return false;
        }
        if (is_null($object)) {
            return true;
        }
        if (strlen(trim($object)) == 0) {
            return true;
        }

        return false;
    }
}

if (!function_exists('beginWith')) {
    function beginWith($haystack, $needle)
    {
        return (strncmp($haystack, $needle, strlen($needle)) === 0) ? true : false;
    }
}

if (!function_exists('endsWith')) {
    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);

        return ($length == 0) ? true : (substr($haystack, -$length) === $needle);
    }
}

if (!function_exists('equals')) {
    function equals($str1, $str2)
    {
        return (strcmp($str1, $str2) === 0) ? true : false;
    }
}

if (!function_exists('valueArrayToValidType')) {
    function valueArrayToValidType($array)
    {
        $temp = array();
        foreach ($array as $arr => $arr2) {
            $temp2 = array();
            if (is_array($arr2) || is_object($arr2)) {
                foreach ($arr2 as $k => $v) {
                    if (is_array($v) || is_object($v)) {
                        $temp2[$k] = valueArrayToValidType($v);
                    } else {
                        $temp2[$k] = assignValue($k, $v);
                    }
                }
                $temp[$arr] = $temp2;
            } else {
                $temp[$arr] = assignValue($arr, $arr2);
            }
        }
        return $temp;
    }
}

if (!function_exists('assignValue'))
{
    function assignValue($key, $value) {

        $raw = [
            'phone',
            'fax',
            'acc_msisdn',
            'msisdn',
            'sn',
            'number',
            'name',
            'description',
            'city',
            'email',
            'comment',
            'google_id',
            'acc_google_id',
            'address',
            'password'
        ];

        // decode value
        if ( ! isBoolean($value)) {
            $value = trim(urldecode($value));
        }

        $container = ((is_float($value) || isFloat($value)) ?
            (float)$value :
            ((is_numeric($value)) ?
                (beginWith($value, '+') ?
                    $value :
                    (int) $value) :
                (((isBoolean($value)) ?
                    ((isFalse($value)) ?
                        false : true) :
                    ((is_null($value) ?
                        null :
                        $value
                    )
                    )
                )
                )
            )
        );

        if (in_array($key, $raw)) {
            $container = $value;
        }

        return $container;
    }
}

if (!function_exists('isFloat'))
{
    function isFloat($num) {
        $isFloat = (is_float($num) || is_numeric($num) && ((float) $num != (int) $num));
        return $isFloat;
    }
}

if (!function_exists('contains'))
{
    function contains($str1, $str2) {
        return ((strpos($str1, $str2) !== false) ? true : false);
    }
}

if (!function_exists('isBoolean')) {
    function isBoolean($string)
    {
        if (is_null($string)) {
            return false;
        }
        if ($string === TRUE || $string === FALSE)
            return true;
        if ($string === true || $string === false)
            return true;
        $string = strtolower($string);

        return in_array($string, array('TRUE', 'FALSE', 'True', 'False', 'true', 'false', '1', '0', 'yes', 'no'), true);
    }
}

if (!function_exists('isFalse')) {
    function isFalse($string)
    {
        if (is_null($string)) {
            return false;
        }
        if ($string === FALSE || $string === false)
            return true;
        $string = strtolower($string);

        return in_array($string, array('FALSE', 'False', 'false', '0', 'no'), true);
    }
}

if (!function_exists('isTrue')) {
    function isTrue($string)
    {
        if (is_null($string)) {
            return false;
        }
        if ($string === TRUE || $string === true)
            return true;
        $string = strtolower($string);

        return in_array($string, array('TRUE', 'True', 'true', '1', 'yes'), true);
    }
}

if (!function_exists('getIPAddress')) {
    function getIPAddress()
    {
        $client = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote = @$_SERVER['REMOTE_ADDR'];
        if (filter_var($client, FILTER_VALIDATE_IP)) {
            return $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            return $forward;
        } else {
            return $remote;
        }
    }
}

if (!function_exists('getCategories')) {
    function getCategories($id)
    {
        $id = $id - 1;
        $maps = array(
            array(1, 7, 8, 9, 10, 58, 122, 125),
            array(24, 121),
            array(4, 23, 34, 36, 37),
            array(13, 16, 43, 59, 120),
            array(18, 38, 61),
            array(15, 20, 41, 62, 63, 64, 119),
            array(3, 14, 25, 26, 27, 31, 32, 33),
            array(5, 39, 50, 60),
            array(6, 12, 17, 19, 21),
            array(28, 30),
            array(35, 42),
            array(2, 11, 22, 44, 123, 124, 126),
        );
        if ($id > (count($maps) - 1)) {
            return;
        } else {
            return $maps[$id];
        }
    }
}

if (!function_exists('notNullable')) {
    function notNullable($string)
    {
        return is_null($string) ? '' : trim($string);
    }
}

if (!function_exists('mappingCategory')) {
    function mappingCategory($id)
    {
        $id = (int) $id;
        $categories = array('Food', 'Fashion', 'Entertainment', 'Tech & Gadgets', 'Events', 'Home & Living', 'Health & Beauty', 'Travel', 'Shopping', 'Sports', 'Film & Music', 'Business');
        $maps = array(
            array(1, 7, 8, 9, 10, 58, 122,125),
            array(24, 121),
            array(4, 23, 34, 36, 37),
            array(13, 16, 43, 59, 120),
            array(18, 38, 61),
            array(15, 20, 41, 62, 63, 64, 119),
            array(3, 14, 25, 26, 27, 31, 32, 33),
            array(5, 39, 50, 60),
            array(6, 12, 17, 19, 21),
            array(28, 30),
            array(35, 42),
            array(2, 11, 22, 44, 123, 124, 126),
        );
        $i = 0;
        foreach ($maps as $map) {
            if (in_array($id, $map)) {
                break;
            } else {
                ++$i;
            }
        }
        if ($i == 12) {
            $i = 11;
        }
        $result = array('id' => ($i + 1), 'name' => $categories[$i]);

        return array2object($result);
    }

    if (!function_exists('get_timezone_offset'))
    {
        function get_timezone_offset($timezone) {
            $origin_dtz = new DateTimeZone($timezone);
            $remote_dtz = new DateTimeZone('UTC');
            $origin_dt = new DateTime("now", $origin_dtz);
            $remote_dt = new DateTime("now", $remote_dtz);
            $offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
            return $offset;
        }
    }

    // https://en.wikipedia.org/wiki/Largest_remainder_method
    if (!function_exists('calculate_percentage'))
    {
        function calculate_percentage($array, $total_percentage = 0, $total_value = 0){
            foreach($array as $a) $total_value += $a['amount'];
            foreach($array as &$a) $total_percentage += floor($a['percentage'] = $a['amount'] / $total_value * 100);
            uasort($array, 'sort_by_remainder');
            foreach ($array as &$v) $v['percentage'] = ($total_percentage++ < 100) ? ceil($v['percentage']) : floor($v['percentage']);
            ksort($array);
            return $array;
        }
    }

    if (!function_exists('sort_by_remainder'))
    {
        function sort_by_remainder($a, $b){
            return $a['percentage']-floor($a['percentage']) < $b['percentage']-floor($b['percentage']);
        }
    }

    if (!function_exists('prepare_json_decode'))
    {
        function prepare_json_decode(string $json) : string {
            $json = str_replace("\\","\\\\", $json);

            return $json;
        }
    }

    if (!function_exists('map_es_store_value'))
    {
        /**
         * @param array $v
         * @return \stdClass
         */
        function map_es_store_value(array $v) : \stdClass
        {
            $store = $v['_source'];
            $store['com_latitude'] = $store['location']['lat'];
            $store['com_longitude'] = $store['location']['lon'];
            $store['distance'] = $v['sort'][1];

            unset($store['location']);

            return (object) $store;
        }
    }
}
