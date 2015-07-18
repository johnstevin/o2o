<?php
namespace Addons\Push\Driver;

use JPush\JPushClient;
use JPush\Model as M;

require realpath(dirname(APP_PATH)) . '/vendor/autoload.php';

/**
 * 极光推送
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @package Addons\Push\Driver
 */
class JPush
{
    /**
     * @var \JPush\JPushClient
     */
    public static $client;//推送SDK实例
    /**
     * @var self
     */
    public static $instance;//当前类实例

    /**
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param string $appKey app key
     * @param string $masterSecret master secret
     */
    private function __construct($appKey, $masterSecret)
    {
        self::$client = new JPushClient($appKey, $masterSecret);
    }

    /**
     * 获取实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param string $appid 应用ID
     * @return self
     */
    public static function getInstance($appid)
    {
        return (isset(self::$instance[$appid]) && self::$instance[$appid] instanceof self) ? self::$instance[$appid] : self::$instance[$appid] = new self(C("JPUSH_{$appid}_APP_KEY"), C("JPUSH_{$appid}_MASTER_SECRET"));
    }

    /**
     * 根据用户ID推送消息
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $uid 用户ID
     * @param string $message_content 消息内容
     * @param string|null $message_title 消息标题
     * @param string|null $message_type 消息类型
     * @param string|null $notification_content 通知内容
     * @param string|null $notification_title 通知标题
     * @param string|null $category 通知分类（IOS8+可用）
     * @param array $extras 附加参数
     * @return bool
     */
    public function pushByUid($uid, $message_content = null, $message_title = null, $message_type = null, $notification_content = null, $notification_title = null, $extras = [], $category = null)
    {
        if (is_int($uid)) $uid = (string)$uid;
        if (!is_array($uid)) $uid = (array)$uid;
        $ids = [];
        if (is_array($uid)) {
            foreach ($uid as $id) {
                $id = (string)intval($id);
                if ($id != 0) {
                    $ids[] = $id;
                }
            }
        }
        if (empty($ids)) return false;
        if (empty($message_content)) $message_content = $notification_content;
        $ids = array_unique($ids);
        $client = self::$client->push()
            ->setPlatform(M\all)
            ->setAudience(M\alias($ids));
//        if (!empty($notification_content)) {
        $client->setNotification(M\notification($notification_content,
            M\android($notification_content, $notification_title, null, $extras),
            M\ios($notification_content, 'default', '+1', null, $extras, $category)));
//        }
        if (APP_DEBUG) {
            $client->setOptions(M\options(null, 604800, null, false));
        }
        return $client->setMessage(M\message($message_content, $message_title, $message_type, $extras))
//            ->printJSON()//测试的时候开启可以打印出要发送的数据
            ->send();
    }

    /**
     * 根据平台推送消息
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param string|array $platform 推送到哪些设备，可传【all】或具体的设备名称【ios】、【android】和【winphone】,也可传多个设备
     * @param string $message_content 消息内容
     * @param string $message_title 消息标题
     * @param string $notification_content 通知内容
     * @param string $notification_title 通知标题
     * @param array $extras 附加内容
     * @param string $category 分类，只有苹果可用
     * @return bool
     */
    public function pushByPlatform($platform, $notification_content, $extras = [], $notification_title = null, $message_content = '', $message_title = null, $category = null)
    {
        //TODO 这个接口暂未测试
        if (empty($platform)) E('必须指定推送设备');
        if (is_string($platform) && strpos($platform, ',')) $platform = explode(',', $platform);
        if (empty($message_content)) $message_content = $notification_content;
        $client = self::$client->push()
            ->setPlatform($platform)
            ->setAudience(M\all)
            ->setNotification(M\notification($notification_content,
                M\android($notification_content, $notification_title, null, $extras),
                M\ios($notification_content, 'default', '+1', null, $extras, $category)));
        if (APP_DEBUG) {
            $client->setOptions(M\options(null, 604800, null, false));
        }
        return $client->setMessage(M\message($message_content, $message_title, null, $extras))
//            ->printJSON()//测试的时候可以开启调试
            ->send();
    }

    /**
     * 更新设备的标签或别名
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param string $registrationId 登记ID
     * @param null|string $alias 别名
     * @param null|array $addTags 要添加的标签
     * @param null|array $removeTags 要删除的标签
     * @return \JPush\Model\DeviceResponse
     */
    public function updateDeviceTagAlias($registrationId, $alias = null, $addTags = null, $removeTags = null)
    {
        return self::$client->updateDeviceTagAlias($registrationId, (string)$alias, $addTags, $removeTags);
    }

    /**
     * 根据设备注册ID移除这个设备的所有别名
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param string $registrationId 设备注册ID
     * @return \JPush\Model\DeviceResponse
     */
    public function removeDeviceAlias($registrationId)
    {
        return self::$client->removeDeviceAlias($registrationId);
    }

    /**
     * 根据设备注册ID删除设备所有的标签
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param string $registrationId 设备注册ID
     * @return \JPush\Model\DeviceResponse
     */
    public function removeDeviceTag($registrationId)
    {
        return self::$client->removeDeviceTag($registrationId);
    }

    /**
     * 删除别名（当删除一个用户的时候使用）
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param string $alias 别名
     * @return \JPush\Model\DeviceResponse
     */
    public function deleteAlias($alias)
    {
        return self::$client->deleteAlias((string)$alias);
    }
}