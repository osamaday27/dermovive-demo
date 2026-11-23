<?php

if (! function_exists('t')) {
    /** Translate a key from lang/{locale}/messages.php */
    function t(string $key, array $replace = []): string
    {
        return __('messages.'.$key, $replace);
    }
}
if (! function_exists('m')) {
    /** Display-only money format (never used for calculation). */
    function m($v): string
    {
        return number_format((float) $v, 2);
    }
}
if (! function_exists('q')) {
    /** Display-only quantity format. */
    function q($v): string
    {
        $s = rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');
        return $s === '' ? '0' : $s;
    }
}
