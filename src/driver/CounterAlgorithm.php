<?php
// +----------------------------------------------------------------------
// | 计数器限流
// +----------------------------------------------------------------------
// | 版权所有
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/JZhao1020/Algorithm
// +----------------------------------------------------------------------
namespace limiting\driver;

use limiting\driver\Algorithm;

class CounterAlgorithm implements Algorithm{
    private $redis;
    private $log;
    private $config = [
        'limit' => 2, // 请求限制量
        'interval' => 500, // 请求单位时间 毫秒
    ];

    public function __construct($redis, $log, $config = []){
        $this->redis = $redis;
        $this->log = $log;

        if($config)
            $this->config = $config;
    }

    /**
     * 校验入口
     * @param $key
     * @return bool
     */
    function slidingGrant($key){
        try {
            $limit = $this->config['limit'];
            $interval = (int)$this->config['interval'];

            $size = (int)$this->redis->llen($key);
            if (! $size) {
                return $this->insertSlidingWindow($this->redis, $key, $this->getMicroSecond());
            }
            $now = $this->getMicroSecond();
            $startTime = (float)$this->redis->lindex($key, 0);
            $check = $size;
            while ($check > 0) {
                if ($now >= $startTime + $interval) {
                    $startTime = (int)$this->redis->lpop($key);
                    $check--;
                    continue;
                } else {
                    break;
                }
            }
            if ($now <= $startTime + $interval) {
                if ($size < $limit) {
                    return $this->insertSlidingWindow($this->redis, $key, $now);
                } else {
                    return false;
                }
            } else {
                return $this->insertSlidingWindow($this->redis, $key, $now);
            }
        } catch (\Exception $exception) {
            $this->log->put('CounterAlgorithm->slidingGrant()方法捕获异常'.'|'.
                $exception->getFile().'|'.$exception->getCode().'|'.$exception->getMessage());
            return false;
        }
    }

    /**
     * 初始化滑动窗口
     *
     * @param $redis
     * @param $url
     * @param $timestamp
     * @return boolean
     */
    private function insertSlidingWindow($redis, $url, $timestamp){
        $redis->rPush($url, $timestamp);
        return true;
    }

    /**
     * 重新计算时间戳 毫秒
     *
     * @return float
     */
    private function getMicroSecond(){
        list($microsecond, $second) = explode(' ', microtime());
        $microsecond = (float)sprintf('%.0f', (floatval($microsecond) + floatval($second)) * 1000);
        return $microsecond;
    }
}