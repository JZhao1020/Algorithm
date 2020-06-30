<?php


namespace limiting;

use \limiting\lib\Redis;
use \limiting\lib\Log;

class Algorithm{
    private $config;

    private $gateways;

    /**
     * Pay constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * 指定操作网关
     * @param string $gateway
     * @return GatewayInterface
     */
    public function gateway($gateway = 'counter')
    {
        return $this->gateways = $this->createGateway($gateway);
    }

    /**
     * 创建操作网关
     * @param string $gateway
     * @return mixed
     */
    protected function createGateway($gateway)
    {
        if (!file_exists(__DIR__ . '/driver/'. ucfirst($gateway) . 'Algorithm.php')) {
            throw new \Exception("Gateway [$gateway] is not supported.");
        }
        $gateway = __NAMESPACE__ . '\\driver\\' . ucfirst($gateway) . 'Algorithm';

        $redis = new Redis();
        $log = new Log();
        return new $gateway($redis, $log, $this->config);
    }
}