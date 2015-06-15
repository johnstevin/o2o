<?php
namespace Addons\Sms\Common;
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 15-6-2
 * Time: 下午2:28
 */
/**
 * 发送验证码
 * @author WangJiang
 * @param array $targets 目标手机数组
 * @param string $code 验证码
 * @return bool 成功或失败
 * 调用样例
 * ``` php
 * include(__ROOT__.'Addons/Sms/Common/functions.php');
 * send_code(['15158087185'],'1234565');
 * ```
 */
function send_code(array $targets,$code){
    $config=include(__ROOT__.'Addons/Sms/config.php');
    $class=$config['SMS_SERVER']['class'];
    $sender=new $class($config['SMS_SERVER']['params']);
    $sender->sendMessage($targets,$code);
}