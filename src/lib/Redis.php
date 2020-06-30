<?php
// +----------------------------------------------------------------------
// | redis基础操作
// +----------------------------------------------------------------------
// | 版权所有
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/JZhao1020/Algorithm
// +----------------------------------------------------------------------


namespace limiting\lib;


class Redis{
    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 0,
        'expire'     => 0,
        'persistent' => false,
        'prefix'     => '',
        'serialize'  => true,
    ];
    private $handler;

    /**
     * 缓存读取次数
     * @var integer
     */
    protected $readTimes = 0;

    /**
     * 缓存写入次数
     * @var integer
     */
    protected $writeTimes = 0;

    /**
     * 缓存标签
     * @var string
     */
    protected $tag;

    /**
     * 序列化方法
     * @var array
     */
    protected static $serialize = ['serialize', 'unserialize', 'think_serialize:', 16];

    /**
     * 架构函数
     * @access public
     * @param  array $options 缓存参数
     */
    public function __construct($options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }

        if (extension_loaded('redis')) {
            $this->handler = new \Redis;

            if ($this->options['persistent']) {
                $this->handler->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);
            } else {
                $this->handler->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
            }

            if ('' != $this->options['password']) {
                $this->handler->auth($this->options['password']);
            }

            if (0 != $this->options['select']) {
                $this->handler->select($this->options['select']);
            }
        } else {
            throw new \BadFunctionCallException('not support: redis');
        }
    }

    /**
     * [databaseSelect 表选择]
     * @ckhero
     * @DateTime 2016-08-18
     * @param string $name [表名字]
     * @return [type] [description]
     */
    public function database($name='default'){
        switch($name){
            case 'limiting':
                $database = 1;
                break;
            default:
                $database = 0;
                break;
        }
        return $this->handler->select($database);
    }

    /**
     * 获取实际的缓存标识
     * @access protected
     * @param  string $name 缓存名
     * @return string
     */
    protected function getCacheKey($name)
    {
        return $this->options['prefix'] . $name;
    }

    /**
     * 获取有效期
     * @access protected
     * @param  integer|\DateTime $expire 有效期
     * @return integer
     */
    protected function getExpireTime($expire)
    {
        if ($expire instanceof \DateTime) {
            $expire = $expire->getTimestamp() - time();
        }

        return $expire;
    }

    /**
     * 序列化数据
     * @access protected
     * @param  mixed $data
     * @return string
     */
    protected function serialize($data)
    {
        if (is_scalar($data) || !$this->options['serialize']) {
            return $data;
        }

        $serialize = self::$serialize[0];

        return self::$serialize[2] . $serialize($data);
    }

    /**
     * 反序列化数据
     * @access protected
     * @param  string $data
     * @return mixed
     */
    protected function unserialize($data)
    {
        if ($this->options['serialize'] && 0 === strpos($data, self::$serialize[2])) {
            $unserialize = self::$serialize[1];

            return $unserialize(substr($data, self::$serialize[3]));
        } else {
            return $data;
        }
    }

    protected function getTagKey($tag)
    {
        return 'tag_' . md5($tag);
    }

    /**
     * 更新标签
     * @access protected
     * @param  string $name 缓存标识
     * @return void
     */
    protected function setTagItem($name)
    {
        if ($this->tag) {
            $key       = $this->getTagkey($this->tag);
            $this->tag = null;

            if ($this->has($key)) {
                $value   = explode(',', $this->get($key));
                $value[] = $name;

                if (count($value) > 1000) {
                    array_shift($value);
                }

                $value = implode(',', array_unique($value));
            } else {
                $value = $name;
            }

            $this->set($key, $value, 0);
        }
    }

    /**
     * 判断缓存
     * @access public
     * @param  string $name 缓存变量名
     * @return bool
     */
    public function has($name)
    {
        return $this->handler->exists($this->getCacheKey($name));
    }

    /**
     * 读取缓存
     * @access public
     * @param  string $name 缓存变量名
     * @param  mixed  $default 默认值
     * @return mixed
     */
    public function get($name, $default = false)
    {
        $this->readTimes++;

        $value = $this->handler->get($this->getCacheKey($name));

        if (is_null($value) || false === $value) {
            return $default;
        }

        return $this->unserialize($value);
    }

    /**
     * 写入缓存
     * @access public
     * @param  string            $name 缓存变量名
     * @param  mixed             $value  存储数据
     * @param  integer|\DateTime $expire  有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null)
    {
        $this->writeTimes++;

        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }

        if ($this->tag && !$this->has($name)) {
            $first = true;
        }

        $key    = $this->getCacheKey($name);
        $expire = $this->getExpireTime($expire);

        $value = $this->serialize($value);

        if ($expire) {
            $result = $this->handler->setex($key, $expire, $value);
        } else {
            $result = $this->handler->set($key, $value);
        }

        isset($first) && $this->setTagItem($key);

        return $result;
    }

    public function rpop($key){
        $key = $this->getCacheKey($key);
        return $this->handler->rpop($key);
    }

    public function del($key){
        $key = $this->getCacheKey($key);
        return $this->handler->del($key);
    }

    /********************** 列表 ****************************/
    public function llen($key){
        $key = $this->getCacheKey($key);
        return $this->handler->lLen($key);
    }

    public function lindex($key, $index = 0){
        $key = $this->getCacheKey($key);
        return $this->handler->lIndex($key, $index);
    }

    public function lpop($key){
        $key = $this->getCacheKey($key);
        return $this->handler->lPop($key);
    }

    public function rpush($key, $value){
        $key = $this->getCacheKey($key);
        return $this->handler->rPush($key, $value);
    }

    public function lpush($key, ...$value){
        $key = $this->getCacheKey($key);
        return $this->handler->lPush($key, ...$value);
    }

    /********************** 哈希 ****************************/
    public function hLen($key){
        $key = $this->getCacheKey($key);
        return $this->handler->hLen($key);
    }

    public function hGet($key, $hashkey){
        $key = $this->getCacheKey($key);
        return $this->handler->hGet($key, $hashkey);
    }

    public function hSet($key, $hashkey, $value){
        $key = $this->getCacheKey($key);
        return $this->handler->hSet($key, $hashkey, $value);
    }
}