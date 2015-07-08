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

    abstract protected function isLogin($token);

    /**
     * 获得调用token
     * @return mixed
     */
    protected function getToken()
    {
        //客户端将token放入自定义header，access_token中
        return $_SERVER['HTTP_ACCESSTOKEN'] ?: I('accesstoken');//I('server.ACCESSTOKEN',null);
    }

    /**
     * 获得UserID，未登录抛异常
     * @return int|void
     */
    protected function getUserId(){
        if(defined('UID')) return UID;

        $token=$this->getToken();
        $loginId=decode_token($token);
        define('UID',$this->isLogin($loginId));
        if( !UID )
            E('用户未登录，不能访问该方法。');
        return UID;
    }

    /**
     * 获得用户分组，未登录抛异常
     * @return array
     */
    protected function getUserGroupIds($roleId=null,$throwEmpty=false){
        $access=$this->getUserAccess($roleId);
        $ret=[];
        foreach($access as $i){
            $ret[]=$i['group_id'];
        }
        if($throwEmpty and empty($ret))
            E('用户无足够权限');
        return $ret;
    }

    /**
     * 获得用户角色，未登录抛异常
     * @return array
     */
    protected function getUserRoleIds($groupId=null,$throwEmpty=false){
        $access=$this->getUserAccess(null,$groupId);
        $ret=[];
        foreach($access as $i){
            $ret[]=$i['role_id'];
        }
        if($throwEmpty and empty($ret))
            E('用户无足够权限');
        return $ret;
    }

    /**
     * 获得分组和角色，未登录抛异常
     * @param null $roleId 指定角色ID
     * @param null $groupId 指定分组ID
     * @return mixed
     */
    protected function getUserAccess($roleId=null,$groupId=null){
        $uid=$this->getUserId();
        $where['uid']=$uid;
        if(!is_null($roleId))
            $where['role_id']=$roleId;
        if(!is_null($groupId))
            $where['group_id']=$groupId;
        $access= M()->table('sq_auth_access')->where($where)->select();
        return $access;
    }

    public function _initialize()
    {
        set_exception_handler(function ($e) {
            header('Content-Type:application/json; charset=utf-8');
//            header('HTTP/1.1 ' . $e->getCode() . ' Not Found');
//            header('Status:' . $e->getCode() . ' Not Found');
            exit(json_encode([
                'success' => false,
                'error_code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]));
        });
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
            exit();
        } else if($format == 'xml') {
            header('Content-Type:text/xml; charset=utf-8');
            echo xml_encode($result);
            exit();
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
    protected function apiSuccess($extra = null, $message = '', $redirect = null)
    {
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