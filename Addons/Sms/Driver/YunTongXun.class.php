<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 15-6-2
 * Time: 下午3:13
 */
namespace Addons\Sms\Driver;

class YunTongXun implements SmsServer{

    private $_peer;
    private $_templateId;

    public function  __construct($options){
        $params=$options['params'];
        $this->_peer=new \Addons\Sms\Driver\REST($params['serverIP'],$params['serverPort'],$params['softVersion']);
        $this->_peer->setAccount($params['accountSid'],$params['accountToken']);
        $this->_peer->setAppId($params['appId']);
        $this->_templateId=$params['templateId'];
    }

    public function sendMessage(array $targets,$message){
        $result = $this->_peer->sendTemplateSMS(implode(',',$targets),[$message],$this->_templateId);
        if($result == NULL ) {
            //echo "result error!";
        }
        if($result->statusCode!=0) {
           // echo "error code :" . $result->statusCode . "<br>";
            //echo "error msg :" . $result->statusMsg . "<br>";
            //TODO 添加错误处理逻辑
        }else{
            //echo "Sendind TemplateSMS success!<br/>";
            // 获取返回信息
            $smsmessage = $result->TemplateSMS;
            //echo "dateCreated:".$smsmessage->dateCreated."<br/>";
            //echo "smsMessageSid:".$smsmessage->smsMessageSid."<br/>";
            //TODO 添加成功处理逻辑
        }
    }
}
