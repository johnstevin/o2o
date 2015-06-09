<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 15-5-30
 * Time: 上午9:08
 */
namespace Common\Controller;

use Common\Exception\ReturnException;
use Think\Controller;

/**
 * 所有REST API的公共类 *
 * Class RestController
 * @package Common\Controller
 */
abstract class RestController  extends Controller{

    protected $api;             //UserApi
    protected $isInternalCall=false;  //是否内部调用

    /**
     * 找不到接口时调用该函数
     */
    public function _empty() {
        $this->apiError(404, "找不到该接口");
    }

    protected function getUserId(){
        echo $_SERVER;die;
        if(defined('UID')) return ;
        $token=I('token');
        define('UID',is_merchant_login($token));
        if( !UID )
            E('用户未登录，不能访问该方法。');
        return UID;
    }

    public function _initialize() {
    }

    public function setInternalCallApi($value=true) {
        $this->isInternalCall = $value ? true : false;
    }

    /**
     * @param $success
     * @param int $error_code
     * @param null $message
     * @param null $redirect
     * @param null $extra
     * @return mixed
     * @throws ReturnException
     */
    protected function apiReturn($success, $error_code=0, $message=null, $redirect=null, $extra=null){

        //生成返回信息
        $result = array();
        $result['success'] = $success;
        $result['error_code'] = $error_code;

        if($message !== null) $result['message'] = $message;
        if($redirect !== null) $result['redirect'] = $redirect;

        foreach($extra as $key=>$value) {
            $result[$key] = $value;
        }

        //将返回信息进行编码
        $format = $_REQUEST['format'] ? $_REQUEST['format'] : 'json';//返回值格式，默认json
        if($this->isInternalCall) {
            throw new ReturnException($result);
        } else if($format == 'json') {
            header('Content-Type:application/json; charset=utf-8');
            echo json_encode($result);
        } else if($format == 'xml') {
            header('Content-Type:text/xml; charset=utf-8');
            echo xml_encode($result);
        } else {
            $_GET['format'] = 'json';
            $_REQUEST['format'] = 'json';
            return $this->apiError(400, "format参数错误");
        }
    }

    /**
     * 成功时调用
     * @param $message　      消息，例：返回成功时提示消息
     * @param null $redirect　转向目录，手机端调用，参数设null
     * @param null $extra     扩展目录，返回数据，可包含多维数组，默认键名为data,例：array('data'=>$return)
     * @return mixed
     * @throws ReturnException
     */
    protected function apiSuccess($extra=null, $message, $redirect=null) {
        return $this->apiReturn(true, 0, $message, $redirect, $extra);
    }

    /**
     * 失败时调用
     * @param $error_code　　　错误代码，商户默认以50001开始
     * @param $message　　　　　错误信息
     * @param null $redirect　　手机端调用，参数设null
     * @param null $extra      扩展目录，返回数据，可包含多维数组，默认键名为data,例：array('data'=>$return)
     * @return mixed
     * @throws ReturnException
     */
    protected function apiError($error_code, $message, $redirect=null, $extra=null) {
        return $this->apiReturn(false, $error_code, $message, $redirect, $extra);
    }

}