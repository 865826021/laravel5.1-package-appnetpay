

### Laravel 5.1 招行一网通APP支付 扩展使用教程

### 用法

```
composer require yuxiaoyang/appnetpay
```

或者在你的 `composer.json` 的 require 部分中添加:
```json
 "yuxiaoyang/appnetpay": "~1.0"
```

下载完毕之后,直接配置 `config/app.php` 的 `providers`:

```php
//Illuminate\Hashing\HashServiceProvider::class,

Yuxiaoyang\Appnetpay\AppnetpayProvider::class,
```
控制器中使用 `AppnetpayController.php` :


```php

<?php


use \Yuxiaoyang\Appnetpay\Appnetpay;
use Input;

class AppnetpayController extends Controller
{
    public $appnetpay;

    //获取支付报文json数据
    public function pay()
    {
        //$this->appnetpay = new \Yuxiaoyang\Appnetpay\Appnetpay();
        $this->appnetpay = new Appnetpay();
        $params['amount'] = "0.01";
        $params['orderNo'] = rand(1000000000,9999999999);
        $params['branchNo'] = "0315";
        $params['merchantNo'] = "000004";
        $params['sMerchantKey'] = "****************";//密钥 16位大写+小写+数字
        return $this->appnetpay->getMessage($params);
    }

    //验证回调json数据
    public function nofity(Request $request)
    {
        $jsonRequestData = Input::get('jsonRequestData');
        if(!$jsonRequestData){
	      echo '参数不能为空!';
	      exit;
        }
        $params = json_decode($jsonRequestData, true);
        $this->appnetpay = new Appnetpay();
        //公钥
	    $pub_key = '*************************************************************';
        $status = $this->appnetpay->verify($params,$pub_key);
        if($status){
            //修改数据库订单支付状态
        }
    }

    //获取公钥json数据
    public function publickey()
    {
        $this->appnetpay = new Appnetpay();
        //密钥 16位大写+小写+数字
	    $sMerchantKey = '************************';
        return $this->appnetpay->getPublicKey($sMerchantKey);
    }

}