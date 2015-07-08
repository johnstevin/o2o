<?php
namespace Addons\Push;
use Addons\Push\Driver\JPush;

/**
 * JPush 极光推送
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @package Addons\JPush
 */
class Push
{
    public static $client;//推送SDK实例
    public static $instance;//当前类实例
    ## 推送消息JDK的类型
    const JPUSH = 'JPush';//极光推送

    /**
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param string $clientType 推送驱动的类型
     */
    private function __construct($clientType,$appid)
    {
        switch ($clientType) {
            case self::JPUSH:
                self::$client = JPush::getInstance($appid);
                break;
            default:
                self::$client = JPush::getInstance($appid);
        }
    }

    /**
     * 获取实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param string $clientType 推送驱动的类型
     * @return \Addons\Push\Driver\JPush
     */
    public static function getInstance($clientType = self::JPUSH,$appid)
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self($clientType,$appid);
        }
        return self::$client;
    }
}