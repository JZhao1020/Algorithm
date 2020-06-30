<?php
// +----------------------------------------------------------------------
// | 漏桶限流
// +----------------------------------------------------------------------
// | 版权所有
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/JZhao1020/Algorithm
// +----------------------------------------------------------------------

namespace limiting\driver;


class LeakBucketAlgorithm implements Algorithm{
    private $redis;
    private $log;
    private $config = [
        'limit' => 10, // 总容量
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
        // TODO: Implement slidingGrant() method.
        // 1.初始化漏桶

        // 2.查询漏桶中的数据，做对比

        // 3.假如桶未满，则正常进桶、出桶

        // 4.桶满了，则结束
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