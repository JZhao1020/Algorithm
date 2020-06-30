# algorithm
接口限流

## 开源地址
https://github.com/JZhao1020/algorithm

##1.安装
```
composer require hao/algorithm
```

##2.实例化
```
$config = [
	'limit' => 10, // 请求限制量
	'interval' => 2000, // 请求单位时间 毫秒
];
$Counter = new \limiting\Algorithm($config);
```

##2.1 计数器限流
```
$url = 'limiting:counter:'. url();
$Counter->gateway('Counter')->slidingGrant($url);
```

##2.2 令牌限流
```
$url = 'limiting:token_bucket:'. url();
$Counter->gateway('TokenBucket')->slidingGrant($url);
```