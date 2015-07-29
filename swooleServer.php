<?php
require 'vendor/autoload.php';

use JPush\Model as M;

/**
 * SWOOLE SERVER
 * @author Fufeng Nie <niefufeng@gmail.com>
 */
class server
{
    protected $server;
    protected static $jpush = [];

    #极光推送配置
    const JPUSH_CLIENT_KEY = '9c987ff4739ce826e20e7920';
    const JPUSH_CLIENT_SECRET = '018dd4b4c6b576ab7d98118c';
    const JPUSH_STORE_KEY = '8548f5acaccf5ece222be8c0';
    const JPUSH_STORE_SECRET = 'cc6141c26da194563766896c';

    public function __construct()
    {
        ## 以下是一些配置
        define('DEVELOPMENT', true);//是否开发环境(极光推送的IOS推送会用到)
        $swooleConfig = [
            'worker_num' => 4,//要开启的worker进程数量,建议设为CPU的1~4倍
            'daemonize' => true,//是否开启守护模式
            'max_request' => 10000,//最大的请求量,达到这个量之后,worker会在执行完这些请求后关闭(同时会重新开启一个worker)
            'dispatch_mode' => 3,//数据包分发策略,1为按顺序分配给每个worker;2为同一个连接都分给同一个worker;3为优先分配给空闲的worker;4为根据IP分配;5为根据UID分配
            'enable_unsafe_event' => true,//如果dispatch_mode设为1 or 3,建议开启本选项
            'discard_timeout_request' => false,//设为false表示无论连接是否关闭,已经请求的worker都会执行才罢休
            'task_worker_num' => 8,//要启动多少异步任务进程
            'log_file' => 'Runtime/Logs/Swoole/error.log',//swoole错误日志
        ];

        $this->server = new swoole_server('127.0.0.1', 9501);
        $this->server->set($swooleConfig);

        ## 注册一些回调事件
        $this->server->on('Start', [$this, 'onStart']);
        $this->server->on('Connect', [$this, 'onConnect']);
        $this->server->on('Receive', [$this, 'onReceive']);
        $this->server->on('Close', [$this, 'onClose']);
        $this->server->on('Task', [$this, 'onTask']);
        $this->server->on('Finish', [$this, 'onFinish']);

        self::$jpush['CLIENT'] = new \JPush\JPushClient(self::JPUSH_CLIENT_KEY, self::JPUSH_CLIENT_SECRET);
        self::$jpush['STORE'] = new \JPush\JPushClient(self::JPUSH_STORE_KEY, self::JPUSH_STORE_SECRET);

        if (!is_dir(__DIR__ . '/Runtime/Logs/Swoole')) {
            mkdir(__DIR__ . '/Runtime/Logs/Swoole', 0775, true);
        }
        set_exception_handler([$this, 'exceptionHandler']);
        $this->server->start();
    }

    /**
     * 用户记录日志
     * @param $log
     * @return bool
     */
    public function log($log)
    {
        $file = fopen(__DIR__ . '/Runtime/Logs/Swoole/' . date('Y-m-d') . '.log', 'a');
        fwrite($file, date('Y-m-d H:i:s') . "\n{$log}\n\n");
        fclose($file);
    }

    /**
     * @param $e
     * @return bool
     */
    public function exceptionHandler(Exception $e)
    {
        if ($e instanceof \JPush\Exception\APIConnectionException) {
            return $this->log('推送连接异常:' . $e->getMessage() . "\n错误文件:{$e->getFile()}\n错误行号:{$e->getLine()}");
        }
        if ($e instanceof \JPush\Exception\APIRequestException) {
            return $this->log('推送请求异常:' . $e->getMessage() . "\n错误文件:{$e->getFile()}\n错误行号:{$e->getLine()}");
        }
        return $this->log('其它异常:' . $e->getMessage() . "\n错误文件:{$e->getFile()}\n错误行号:{$e->getLine()}");
    }

    /**
     * 获取极光推送的客户端,写这个方法是为了IDE提示
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param string $app APP名称,可选[STORE]和[CLIENT]
     * @return \JPush\JPushClient;
     */
    public function getJpush($app)
    {
        return self::$jpush[$app];
    }

    /**
     * swoole 服务启动成功的回调
     * @param \swoole_server $server
     */
    public function onStart(swoole_server $server)
    {
        //记录这条日志是方便查看swoole是否挂了又被重启了
        $this->log('服务器启动成功');
    }

    public function onConnect(swoole_server $server, $fd, $fromId)
    {
    }

    /**
     * 接收到数据的回调
     * @param \swoole_server $server
     * @param $fd
     * @param $fromId
     * @param string $data 客户端传递的数组,必须为json格式且必须包含[action]和[app]这两个值
     */
    public function onReceive(swoole_server $server, $fd, $fromId, $data)
    {
        $server->task($data);
    }

    public function onClose(swoole_server $server, $fd, $fromId)
    {
    }

    public function onTask(swoole_server $server, $taskId, $fromId, $data)
    {
        $data = json_decode($data, true);
        //要执行的方法
        $action = strtolower(trim($data['action']));
        //要发送的app名称
        $app = strtoupper(trim($data['app']));
        //检测app名称是否在系统设定的app列表里
        if (!in_array($app, ['STORE', 'CLIENT'])) {
            $this->log($app . ' 不在APP名称列表里');
        }
        switch ($action) {
            case 'push_by_uid'://根据用户ID发送推送消息
                return $this->pushByUid($app, $data['uid'], $data['notificationContent'], $data['extras'], $data['notificationTitle'], $data['messageContent'], $data['messageTitle'], $data['category'], $data['messageType']);
                break;
            case 'update_device_tag_alias'://更新设备的别名和标签
                return $this->updateDeviceTagAlias($app, $data['registrationId'], $data['alias'], $data['addTags'], $data['removeTags']);
                break;
            case 'push_by_platform'://根据设备种类推送消息
                return $this->pushByPlatform($app, $data['platform'], $data['notificationContent'], $data['extras'], $data['notificationTitle'], $data['messageContent'], $data['messageTitle'], $data['category']);
                break;
            case 'remove_device_alias'://删除设备的别名
                return $this->removeDeviceAlias($app, $data['registrationId']);
                break;
            case 'removeDeviceTag'://删除设备的标签
                return $this->removeDeviceTag($app, $data['registrationId']);
                break;
            case 'deleteAlias'://删除别名(用于这个用户被删除之后)
                return $this->deleteAlias($app, $data['alias']);
                break;
        }
    }

    public function onFinish(swoole_server $server, $taskId, $data)
    {
    }

    /**
     * 根据用户ID推送消息
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param string $app 应用ID,可选[CLIENT]和[STORE]
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
    public function pushByUid($app, $uid, $notification_content, $extras = [], $notification_title = null, $message_content = '', $message_title = null, $category = null, $message_type = null)
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
        $client = $this->getJpush($app)->push()
            ->setPlatform(M\all)
            ->setAudience(M\alias($ids));
//        if (!empty($notification_content)) {
        $client->setNotification(M\notification($notification_content,
            M\android($notification_content, $notification_title, null, $extras),
            M\ios($notification_content, 'default', '+1', null, $extras, $category)));
//        }
        if (DEVELOPMENT) {
            $client->setOptions(M\options(null, 604800, null, false));
        }
        return $client->setMessage(M\message($message_content, $message_title, $message_type, $extras))
//            ->printJSON()//测试的时候开启可以打印出要发送的数据
            ->send();
    }


    /**
     * 根据平台推送消息
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param string $app 应用ID,可选[CLIENT]和[STORE]
     * @param string|array $platform 推送到哪些设备，可传【all】或具体的设备名称【ios】、【android】和【winphone】,也可传多个设备
     * @param string $message_content 消息内容
     * @param string $message_title 消息标题
     * @param string $notification_content 通知内容
     * @param string $notification_title 通知标题
     * @param array $extras 附加内容
     * @param string $category 分类，只有苹果可用
     * @return bool
     */
    public function pushByPlatform($app, $platform, $notification_content, $extras = [], $notification_title = null, $message_content = '', $message_title = null, $category = null)
    {
        //TODO 这个接口暂未测试
        if (empty($platform)) E('必须指定推送设备');
        if (is_string($platform) && strpos($platform, ',')) $platform = explode(',', $platform);
        if (empty($message_content)) $message_content = $notification_content;
        $client = self::getJpush($app)->push()
            ->setPlatform($platform)
            ->setAudience(M\all)
            ->setNotification(M\notification($notification_content,
                M\android($notification_content, $notification_title, null, $extras),
                M\ios($notification_content, 'default', '+1', null, $extras, $category)));
        if (DEVELOPMENT) {
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
     * @param string $app 应用ID,可选[CLIENT]和[STORE]
     * @param string $registrationId 登记ID
     * @param null|string $alias 别名
     * @param null|array $addTags 要添加的标签
     * @param null|array $removeTags 要删除的标签
     * @return \JPush\Model\DeviceResponse
     */
    public function updateDeviceTagAlias($app, $registrationId, $alias = null, $addTags = null, $removeTags = null)
    {
        return self::getJpush($app)->updateDeviceTagAlias($registrationId, (string)$alias, $addTags, $removeTags);
    }

    /**
     * 根据设备注册ID移除这个设备的所有别名
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param string $app 应用ID,可选[CLIENT]和[STORE]
     * @param string $registrationId 设备注册ID
     * @return \JPush\Model\DeviceResponse
     */
    public function removeDeviceAlias($app, $registrationId)
    {
        return self::getJpush($app)->removeDeviceAlias($registrationId);
    }

    /**
     * 根据设备注册ID删除设备所有的标签
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param string $app 应用ID,可选[CLIENT]和[STORE]
     * @param string $registrationId 设备注册ID
     * @return \JPush\Model\DeviceResponse
     */
    public function removeDeviceTag($app, $registrationId)
    {
        return self::getJpush($app)->removeDeviceTag($registrationId);
    }

    /**
     * 删除别名（当删除一个用户的时候使用）
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param string $app 应用ID,可选[CLIENT]和[STORE]
     * @param string $alias 别名
     * @return \JPush\Model\DeviceResponse
     */
    public function deleteAlias($app, $alias)
    {
        return self::getJpush($app)->deleteAlias((string)$alias);
    }
}

$server = new server();