<?php
/**
 *
 * Part of the QCubed PHP framework.
 *
 * @license MIT
 *
 */

namespace QCubed;

abstract class Route
{
    public static $request;
    private static $param;

    public static function matchURI($uri = null)
    {
        $uri = (!$uri) ? $_SERVER['REQUEST_URI'] : $uri;
        $uri = (!$uri) ? '/' : rtrim($uri, "\/");

        if (!empty(self::$request)) {
            foreach (self::$request as $request) {
                if (is_array($request) && isset($request['param'])) {
                    self::$param = $request['param'];
                    $ruleTemp = $request;
                    $ruleTemp['request'] = preg_replace_callback(
                        "/\<(?<key>[0-9a-z_]+)\>/",
                        [self::class, 'replace'],
                        str_replace(")", ")?", $request['request'])
                    );
                    if ($matched = self::reportRule($ruleTemp, $uri)) {
                        return $matched;
                    }
                }
            }
        }

        return [];
    }

    private static function replace($matches)
    {
        $key = $matches['key'];
        if (isset(self::$param[$key])) {
            return "(?<".$key.">".self::$param[$key].")";
        } else {
            return "(?<".$key.">"."([^/]+)".")";
        }
    }

    private static function reportRule($ini_array, $uri)
    {
        if (is_array($ini_array) && $uri) {
            if (preg_match("#^".$ini_array['request']."$#", $uri, $match)) {
                $matched = [];
                foreach ($match as $key => $value) {
                    if (!is_numeric($key)) {
                        $matched[$key] = $value;
                    }
                }
                return $matched;
            }
        }
        return null;
    }
}
