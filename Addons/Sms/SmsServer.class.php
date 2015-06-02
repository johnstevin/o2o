<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 15-6-2
 * Time: 下午2:41
 */
namespace Addons\Sms;

/**
 * Interface SmsServer 短信驱动接口
 * @package Addons\Sms
 * @author WangJiang
 */
interface SmsServer {
    public function sendMessage(array $targets,$message);
}