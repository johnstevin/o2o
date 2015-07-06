<?php
namespace Addons\Push\Driver;

use JPush\JPushClient;
use JPush\Model as M;

require __ROOT__ . 'vendor/autoload.php';

class JPush
{
    public static $client;//推送SDK实例
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
     * @return self
     */
    public static function getInstance()
    {
        return self::$instance instanceof self ? self::$instance : self::$instance = new self(C('JPUSH_APP_KEY'), C('JPUSH_MASTER_SECRET'));
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
    public function pushByUid($uid, $message_content, $message_title = null, $message_type = null, $notification_content = null, $notification_title = null, $extras = [], $category = null)
    {
        $client = self::$client->push()
            ->setPlatform(M\all)
            ->setAudience(M\alias((array)(string)$uid));
        if (!empty($notification_content)) {
            $client->setNotification(M\notification($notification_content),
                M\android($notification_content, $notification_title, null, $extras),
                M\ios($notification_content, null, '+1', null, $extras, $category));
        }
        return $client->setMessage(M\message($message_content, $message_title, $message_type, $extras))
            ->printJSON()->send();
    }

    /**
     * 根据平台推送消息
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param string|array $platform
     * @param string $message_content 消息内容
     * @param string $message_title 消息标题
     * @param string $notification_content 通知内容
     * @param string $notification_title 通知标题
     * @return bool
     */
    public function pushByPlatform($platform, $message_content, $message_title, $notification_content, $notification_title)
    {
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
        return self::$client->updateDeviceTagAlias($registrationId, $alias, $addTags, $removeTags);
    }
}