<?php

function getUserStatus($key = null){
    $data = [
        '0' => '待售',
        '1' => '待激活',
        '2' => '已激活',
        '3' => '已冻结',
    ];

    return is_null($key) ? $data : $data[$key];
}

function getAdvertisementStatus($key = null){
    $data = [
        '0' => '下架',
        '1' => '上架',
    ];

    return is_null($key) ? $data : $data[$key];
}

/**
 * 生成随机编码
 * @param $length
 * @return string
 */
function makeInvitationCode($length) {
//    $chars = 'ABCDEFGHJKLMNPQRSTUVWXY0123456789';
    $chars = '0123456789012345678901234567890123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[mt_rand(0, strlen($chars) - 1)];
    }

    return $password;
}

/**
 * 概率函数
 * @param $proArr
 * @return int|string
 */
function get_rand($proArr) {
    $result = '';
    //概率数组的总概率精度
    $proSum = array_sum($proArr);
    //概率数组循环
    foreach ($proArr as $key => $proCur) {
        $randNum = mt_rand(1, $proSum);             //抽取随机数
        if ($randNum <= $proCur) {
            $result = $key;                         //得出结果
            break;
        } else {
            $proSum -= $proCur;
        }
    }
    unset ($proArr);

    return $result;
}

/**
 * 生成纯数字的随机数
 * @param $len
 * @return bool|string
 */
function randStr($len) {
    $chars = str_repeat('0123456789', $len);
    $chars = str_shuffle($chars);
    $str = substr($chars, 0, $len);

    return $str;
}

/**
 * 生成订单号
 * @return string
 */
function makeOrderNo() {

    return (string) (time() . mt_rand(100000, 999999));
}

/**
 * @param $prefix
 * @return string
 */
function makeRunningWaterNo($prefix = 'REFUND-') {

    return $prefix.(string) (time() . mt_rand(100000, 999999));
}

/**
 * 获取当前时间
 * @return string
 */
function getDateTime() {

    return \Carbon\Carbon::now()->toDateTimeString();
}

/**
 * 隐藏手机号中间五位
 * @param $str
 * @return mixed
 */
function hideMobile($str) {

    return substr_replace($str, '*****', 3, 5);
}

/**
 * 获取毫秒时间戳
 * @return float
 */
function getMillisecond() {
    list($t1, $t2) = explode(' ', microtime());

    return (float) sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
}

/**
 * 对象转数组
 * @param $array
 * @return array
 */
function object_array($array) {
    if (is_object($array)) {
        $array = (array) $array;
    }
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            $array[$key] = object_array($value);
        }
    }

    return $array;
}

function str_last($str){

    return str_split($str)[count(str_split($str)) - 1];
}

/**
 * 生成随机小数
 * @param int $min
 * @param int $max
 * @return float|int
 */
function randFloat($min=0, $max=1){
    $res = $min + mt_rand()/mt_getrandmax() * ($max-$min);

    return (float)(array_random(['+', '-']).$res);
}

/**
 * 获取图片类型
 * @param $image
 * @return bool|string
 */
function getImageType($image){
    switch (exif_imagetype($image)){
        case 1: $imageType = '.gif'; break;
        case 2: $imageType = '.jpg'; break;
        case 3: $imageType = '.png'; break;
        case 4: $imageType = '.swf'; break;
        case 5: $imageType = '.psd'; break;
        case 6: $imageType = '.bmp'; break;
        default: $imageType = false; break;
    }
    return $imageType;
}

/**
 * 获取裁剪图片的宽高和放大倍率
 * @param file $image
 * @param integer $width 裁剪画布的宽
 * @param integer $height 裁剪画布的高
 * @return array $width, $height, $multiple
 */
function getAvatarSize($image,$width,$height){
    $imageSize = getimagesize($image);
    if($imageSize[0] <= $width && $imageSize[1] <= $width){
        $avatarWidth = $imageSize[0];
        $avatarHeight = $imageSize[1];
        $multiple = 1;
    }else{
        if($imageSize[0] > $imageSize[1]){	//宽>高
            $multiple = $imageSize[0] < $width*2?1:2;
            $proportion = $imageSize[0]/$width;
            $avatarWidth = $width*$multiple;
            $avatarHeight = (int)($imageSize[1]/$proportion)*$multiple;
        }else{	//宽<=高
            $multiple = $imageSize[1] < $height*2?1:2;
            $proportion = $imageSize[1]/$height;
            $avatarWidth = (int)($imageSize[0]/$proportion)*$multiple;
            $avatarHeight = $height*$multiple;
        }
    }
    return ['width'=>$avatarWidth, 'height'=>$avatarHeight, 'multiple'=>$multiple];
}

/**
 * php截取指定两个字符之间字符串，默认字符集为utf-8
 * @param string $begin  开始字符串
 * @param string $end    结束字符串
 * @param string $str    需要截取的字符串
 * @return string
 */
function cut($begin,$end,$str){
    $b = mb_strpos($str,$begin) + mb_strlen($begin);
    $e = mb_strripos($str,$end) - $b;

    return mb_substr($str,$b,$e);
}