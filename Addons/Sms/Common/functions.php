<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 15-6-2
 * Time: 下午2:28
 */

/**
 * 发送验证码
 * @param array $targets 目标手机数组
 * @param string $code 验证码
 * @return bool 成功或失败
 */
function send_code(array $targets,$code){
    $class=C('SMS_SERVER.class');
    $sender=new $class(C('SMS_SERVER.params'));
    $sender->sendMessage($targets,$code);
}