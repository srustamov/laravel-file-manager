<?php


namespace Srustamov\FileManager;


class Translation
{
    /**
     * @codeCoverageIgnore
     * @param $key
     * @param array $data
     * @return array|string|null
     */
    public static function get($key, array $data = [])
    {
        return __('fileManager::messages.' . $key, $data);
    }


    /**
     * @codeCoverageIgnore
     * @param $if
     * @param $if_key
     * @param null $else_key
     * @param array $data
     * @param array|null $else_data
     * @return array|string|null
     */
    public static function getIf($if, $if_key, $else_key = null, array $data = [], array $else_data = null)
    {
        if ($if) {
            return self::get($if_key, is_array($else_key) ? $else_key : $data);
        }

        return self::get($else_key, is_array($else_key) ? $else_key : ($else_data ?? $data));
    }
}
