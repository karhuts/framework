# Karthus

![avatar](./static/logo.jpg)

A simple Framework For Swoole

## 安装说明

- 使用BlueCity私有`composer`,更新 `composer.json`

```json
{
  "repositories": [{
    "type": "composer",
    "url": "https://composer.blued.cn"
  }]
}
```

- 安装

```bash
composer require min/karthus
```


## 使用说明

```php
<?php
//载入vendor/autoload.php 必不可少
include_once(__DIR__ .'/vendor/autoload.php');

//应用名称
const APP_NAME = "Karthus";
//启动的worker数量
const WORKER_NUM = 2;
//日志存放路径
const LOGGER_PATH = './'. APP_NAME;
const LOGGER_DIR  = LOGGER_PATH .'/logs';

use Karthus\Service;

//初始化自动载入，非composer\vendor下的文件
//请遵循namespace进行文件引入
Service\Autoload::init(__DIR__);

$service = new Service\Karthus('http://0.0.0.0:8000');   //设置服务的IP和端口
$service
    ->setRouter(Config\R::$Routers) //载入路由
    ->setLogFile('./http.log') //设置HTTP日志路径
    ->setLogLevel(Service\Karthus::LEVER_DEBUG) //设置日志等级
    ->setProcessName(APP_NAME) //服务名
    ->setCompression(true) //启用HTTP压缩输出
    ->setPidFile() //设置PID文件
    ->setWorkerNum(WORKER_NUM) //设置进程数
    ->responseJSON(true) //是否开启JSON输出，默认开启
    ->run();

```

## 路由说明


```php 
<?php
namespace Config;
use Karthus\Config\Router;
use Apps\Users;

class R extends Router{

    public static $Routers = array(
        'get:/users/:number'     => array(
            'class'     => Users::class,
            'action'    => 'execute',
            'map_var'   => [
                1   => 'uid',
            ],
        ),
    );

}

```

路由处于 `Config/R.php` 中 


## 其他请参考 `Apps/*.php` 中的案例
