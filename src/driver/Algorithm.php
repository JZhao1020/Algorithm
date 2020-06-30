<?php
namespace limiting\driver;

/**
 * 限流算法接口
 * Interface Algorithm
 * @package limiting
 */
interface Algorithm{
    /**
     * 校验入口
     * @param $key
     * @return bool
     */
    function slidingGrant($key);
}