<?php
// +----------------------------------------------------------------------
// | 日志记录
// +----------------------------------------------------------------------
// | 版权所有
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/JZhao1020/Algorithm
// +----------------------------------------------------------------------


namespace limiting\lib;


class Log{
    private static $cachepath = '';
    /**
     * 检查日志目录
     * @return bool
     */
    static protected function check()
    {
        empty(self::$cachepath) && self::$cachepath = dirname(__DIR__) . DIRECTORY_SEPARATOR .'log'. DIRECTORY_SEPARATOR;
        self::$cachepath = rtrim(self::$cachepath, '/\\') . DIRECTORY_SEPARATOR;
        if (!is_dir(self::$cachepath) && !mkdir(self::$cachepath, 0755, true)) {
            return false;
        }
        return true;
    }

    /**
     * 输出内容到日志
     * @param string $line
     * @param string $filename
     * @return mixed
     */
    static public function put($line, $filename = ''){
        empty($filename) && $filename = date('Ymd') . '.log';
        return self::check() && file_put_contents(self::$cachepath . $filename, '[' . date('Y/m/d H:i:s') . "] {$line}\n", FILE_APPEND);
    }
}