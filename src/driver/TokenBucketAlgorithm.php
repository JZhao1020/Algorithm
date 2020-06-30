<?php
// +----------------------------------------------------------------------
// | 令牌桶限流
// +----------------------------------------------------------------------
// | 版权所有
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/JZhao1020/Algorithm
// +----------------------------------------------------------------------

namespace limiting\driver;


class TokenBucketAlgorithm implements Algorithm{
    private $redis;
    private $log;
    private $config = [
        'limit' => 10, // 请求限制量
        'interval' => 2000, // 请求单位时间 毫秒
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

            $timeKey = $key. ':time';
            $check = $this->redis->hLen($timeKey);
            if (! $check) {
                // 初始化token桶数据
                return $this->init($this->redis, $timeKey, $key, $limit - 1);
            }
            $now = $this->getMicroSecond();
            $timestamp = $this->redis->hGet($timeKey, 'timestamp');
            if ($now < $timestamp + $interval) {
                // 在时间内，正常获取token，存在则成功
                return $this->getToken($this->redis, $key);
            } else {
                // 不在时间内，初始化token桶数据
                return $this->init($this->redis, $timeKey, $key, $limit);
            }
        } catch (\Exception $exception) {
            $this->log->put('TokenBucketAlgorithm->slidingGrant()方法捕获异常'.'|'.
                $exception->getFile().'|'.$exception->getCode().'|'.$exception->getMessage());
            return false;
        }
    }

    /**
     * 初始化
     *
     * @param $redis
     * @param $timeKey
     * @param $url
     * @param $limit
     * @return bool
     */
    private function init($redis, $timeKey, $url, $limit){
        // 初始化时间
        $this->initGrant($redis, $timeKey);
        // 初始化令牌桶
        $this->initTokenBucket($redis, $url, $limit);
        return true;
    }
    /**
     * 初始化指定路由的限流
     *
     * @param $redis
     * @param $url
     * @return bool
     */
    private function initGrant($redis, $url){
        $redis->hSet($url, 'timestamp', $this->getMicroSecond());
        return true;
    }
    /**
     * 初始化令牌桶 一次性放入足够的令牌
     *
     * @param $redis
     * @param $url
     * @param $limit
     * @return void
     */
    private function initTokenBucket($redis, $url, $limit){
        $redis->del($url);
        $this->addToken($redis, $url, $limit);
    }
    /**
     * 获取令牌
     *
     * @param $redis
     * @param $url
     * @return bool
     */
    private function getToken($redis, $url){
        $res = $redis->rpop($url);
        return $res ? true : false;
    }

    /**
     * 根据一定时间来发放令牌，本来是想实现毫秒级的定时发送指定数量令牌
     * 想想太复杂，遂改成指定秒数发放足额令牌形式
     * 比如限定是1秒100请求，超过1秒了就补全缺的令牌使可用数达到100即可
     *
     * @param $redis
     * @param $url
     * @param $limit
     * @return boolean
     */
    private function addToken($redis, $url, $limit){
        $size = (int)$redis->llen($url);
        $fillNum = $limit - $size;
        if ($fillNum > 0) {
            $token = array_fill(0, $fillNum, 1);
            $redis->lpush($url, ...$token);
        }
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