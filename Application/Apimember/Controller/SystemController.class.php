<?php
/**
 * Created by Stevin.John
 * Author:  Stevin.John@qq.com
 */

namespace Apimember\Controller;


class SystemController extends ApiController{

    public function apkDownload(){
        try{
            $release = I('get.version') != '' ? I('get.version') : E('版本号不能为空');
            $model   = M('Version');
            $fields  = 'path,package_type,version_type,version,name';
            $map     = array(
                'release'     => array('gt', $release),
                'type'        => C('VERSION_PACKAGE_TYPE.MEMBER'),
            );
            $result  = $model->field($fields)->where($map)->find();
            empty($result) ? $this->apiSuccess(array('data'=>''),'没有最新版本') : '';
            $this->apiSuccess(array('data'=>$result), '请下载最新版本');
        } catch (\Exception $ex) {
            $this->apiError(40050, $ex->getMessage());
        }
        

    }

}
