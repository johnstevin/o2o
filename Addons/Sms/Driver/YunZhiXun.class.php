<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 15-6-2
 * Time: 下午3:22
 */

namespace Addons\Sms\Driver;
use Addons\Sms\SmsServer;

/**
 * Class YunZhiXun 云之讯短信服务
 * @package Addons\Sms\Driver
 * @author WangJiang
 */
class YunZhiXun  implements SmsServer{

    private $_peer;
    private $_templateId;
    private $_appId;

    public function  __construct($options){
        $this->_peer=new Ucpaas($options);
        $this->_templateId=$options['templateId'];
        $this->_appId=$options['appId'];
    }

    public function sendMessage(array $targets,$message){

        foreach($targets as $i){
            $this->_peer->templateSMS($this->_appId,$i,$this->_templateId,$message);
        }

    }
}
