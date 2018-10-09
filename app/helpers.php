<?php

function getUserStatus($key = null){
    $data = [
        '0' => '待售',
        '1' => '待激活',
        '2' => '已激活',
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