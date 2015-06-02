<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 15-6-2
 * Time: 下午3:13
 */
namespace Addons\Sms\Driver;
use Addons\Sms\SmsServer;

/**
 * Class YunTongXun
 *<pre>
 * 云通讯短信服务
 * </pre>
 * @package Addons\Sms\Driver
 * @author WangJiang
 */
class YunTongXun implements SmsServer{

    private $_peer;
    private $_templateId;

    public function  __construct($options){
        $this->_peer=new REST($options['serverIP'],$options['serverPort'],$options['softVersion']);
        $this->_peer->setAccount($options['accountSid'],$options['accountToken']);
        $this->_peer->setAppId($options['appId']);
        $this->_templateId=$options['templateId'];
    }

    public function sendMessage(array $targets,$message){
        $result = $this->_peer->sendTemplateSMS(implode(',',$targets),[$message],$this->_templateId);
        if($result == NULL ) {
            echo "result error!";
        }
        if($result->statusCode!=0) {
            echo "error code :" . $result->statusCode . "<br>";
            echo "error msg :" . $result->statusMsg . "<br>";
            //TODO 添加错误处理逻辑
        }else{
            echo "Sendind TemplateSMS success!<br/>";
            // 获取返回信息
            $smsmessage = $result->TemplateSMS;
            echo "dateCreated:".$smsmessage->dateCreated."<br/>";
            echo "smsMessageSid:".$smsmessage->smsMessageSid."<br/>";
            //TODO 添加成功处理逻辑
        }
    }
}
