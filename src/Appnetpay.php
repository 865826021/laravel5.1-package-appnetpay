<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/15
 * Time: 16:27
 */

namespace Yuxiaoyang\Appnetpay;

class Appnetpay {

    /*
     * 生成报文数据
     * 将订单参数数组传入getMessage
     */
    public function getMessage($params)
    {
        if(!isset($params) && !is_array($params)){
            $data = array(
                "status"=>"0",
                "msg"=>"false",
                "result"=>"参数错误"
            );
            return json_encode($data);
        }
        $dateTime = date('YmdHis',time());
        $date = date('Ymd',time());
        $agrNo = "1234567890".rand(1000,10000);
        $amount = $params['amount'];
        $branchNo = $params['branchNo'];//"0315"
        $merchantNo = $params['merchantNo'];//"000004"
        $merchantSerialNo = $dateTime.rand(100,10000);
        $orderNo = $params['orderNo'];//10位招行订单号
        $payNoticeUrl = "http://www.merchant.com/path/payNotice.do";
        $returnUrl = "http://www.merchant.com/path/return.do";
        $signNoticeUrl = "http://www.merchant.com/path/signNotice.do";
        //拼接支付密钥
        $sMerchantKey = $params['sMerchantKey'];//"****************" 16位大写+小写+数字
        //$strToSign = "branchNo=0755&dateTime=".$dateTime."&merchantNo=002346&txCode=FBPK";
        $strToSign = "agrNo=".$agrNo."&amount=".$amount."&branchNo=".$branchNo."&date=".$date."&dateTime=".$dateTime."&merchantNo=".$merchantNo."&merchantSerialNo=".$merchantSerialNo."&orderNo=".$orderNo."&payNoticeUrl=".$payNoticeUrl."&returnUrl=".$returnUrl."&signNoticeUrl=".$signNoticeUrl."";
        $strToSign .= '&'.$sMerchantKey;
        //return $strToSign;
        //SHA-256签名
        $baSrc = mb_convert_encoding($strToSign,"UTF-8");
        $baResult = hash('sha256', $baSrc);
        //转为16进制字符串
        $sign = bin2hex($baResult);
        $sign = strtoupper($baResult);
        $message = array(
            "version"=>"1.0",
            "charset"=>"UTF-8",
            "sign"=>$sign,
            "signType"=>"SHA-256",
            "reqData"=>array(
                "dateTime"=>$dateTime,
                "branchNo"=>$branchNo,
                "merchantNo"=>$merchantNo,
                "date"=>$date,
                "orderNo"=>$orderNo,
                "amount"=>$amount,
                "payNoticeUrl"=>$payNoticeUrl,
                "returnUrl"=>$returnUrl,
                "agrNo"=>$agrNo,
                "merchantSerialNo"=>$merchantSerialNo,
                "signNoticeUrl"=>$signNoticeUrl
            )
        );
        $data = array(
            "status"=>"1",
            "msg"=>"success",
            "result"=>$message
        );
        return json_encode($data);
    }

    /*
     * 回调验签
     * 将接收到回调json转换成数组传入notify
     * $params = json_decode($jsonRequestData, true);
     */
    public function verify($params,$pub_key){
        //验签php示例：$params['noticeData']['branchNo'];
        //公钥
        $pub_key = $pub_key;
        //待验证签名字符串
        $toSign_str = 'amount='.$params['noticeData']['amount'].'&bankDate='.$params['noticeData']['bankDate'].'&bankSerialNo='.$params['noticeData']['bankSerialNo'].'&branchNo='.$params['noticeData']['branchNo'].'&cardType='.$params['noticeData']['cardType'].'&date='.$params['noticeData']['date'].'&dateTime='.$params['noticeData']['dateTime'].'&discountAmount='.$params['noticeData']['discountAmount'].'&discountFlag='.$params['noticeData']['discountFlag'].'&httpMethod='.$params['noticeData']['httpMethod'].'&merchantNo='.$params['noticeData']['merchantNo'].'&merchantPara='.$params['noticeData']['merchantPara'].'&noticeSerialNo='.$params['noticeData']['noticeSerialNo'].'&noticeType='.$params['noticeData']['noticeType'].'&noticeUrl='.$params['noticeData']['noticeUrl'].'&orderNo='.$params['noticeData']['orderNo'].'';
        //return $toSign_str;
        //签名结果(strSign)
        $sig_dat = $params['sign'];
        //处理证书
        $pem = chunk_split($pub_key, 64, "\n");
        $pem = "-----BEGIN PUBLIC KEY-----\n" . $pem . "-----END PUBLIC KEY-----\n";
        $pkid = openssl_pkey_get_public($pem);
        if (empty($pkid)) {
            die('获取 pkey 失败');
        }
        //验证
        $ok = openssl_verify($toSign_str, base64_decode($sig_dat), $pkid, OPENSSL_ALGO_SHA1);
        return $ok;
    }

    /*
     * 获取生产环境公钥
     * 将密钥传入GetPublicKey  16位大写+小写+数字
     */
    public function getPublicKey($sMerchantKey)
    {
        //拼接支付密钥
        $dateTime = date('YmdHis',time());
        //echo $dateTime;exit;    //20170207050816
        $sMerchantKey = $sMerchantKey;
        $strToSign = "branchNo=0315&dateTime=".$dateTime."&merchantNo=000344&txCode=FBPK";
        //$strToSign = "branchNo=0315&dateTime=20170207054806&merchantNo=000004&txCode=FBPK";
        $strToSign .= '&'.$sMerchantKey;
        //SHA-256签名
        $baSrc = mb_convert_encoding($strToSign,"UTF-8");
        $baResult = hash('sha256', $baSrc);
        //转为16进制字符串
        $sign = bin2hex($baResult);
        $sign = strtoupper($baResult);
        $params = array(
            "version"=>"1.0",
            "charset"=>"UTF-8",
            "sign"=>$sign,
            "signType"=>"SHA-256",
            "reqData"=>array("dateTime"=>$dateTime,
                "txCode"=>"FBPK",
                "branchNo"=>"0315",
                "merchantNo"=>"000344"
            )
        );
        $params = json_encode($params);
        $params = array("jsonRequestData"=>$params);
        $url = "https://b2b.cmbchina.com/CmbBank_B2B/UI/NetPay/DoBusiness.ashx";
        return $this->curl($url,$params,1,1);
    }

    /**
     * CURLf方法
     * @param $url 请求的URL
     * @param bool $params 参数
     * @param int $ispost post或者get
     * @param int $https
     * @return bool
     */
    function curl($url, $params = false, $ispost = 0, $https = 0)
    {
        $httpInfo = array();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        }
        if ($ispost) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {

            if ($params) {
                if (is_array($params)){
                    $params =  http_build_query($params);
                }
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }

        $response = curl_exec($ch);


        if ($response === FALSE) {
            //echo "cURL Error: " . curl_error($ch);
            return false;
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
        curl_close($ch);
        return $response;
        /*$response = json_decode($response,true);
        echo "<pre>";
        print_r($response);
        echo "</pre>";*/
    }

}