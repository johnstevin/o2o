<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 6/22/15
 * Time: 10:26 AM
 */

namespace Apimember\Controller;


use Common\Model\PictureModel;

class FileController extends ApiController{

    /**
     * 上传图片文件
     * @author WangJiang
     */
    public function uploadPicture(){

        try{
            if(!IS_POST)
                E('非法调用，请用POST调用');
            //验证登录用户
            //测试时注释$this->getToken();

            /* 调用文件上传组件上传文件 */
            $Picture = new PictureModel();
            $pic_driver = C('PICTURE_UPLOAD_DRIVER');
            $info = $Picture->upload(
                $_FILES,
                C('PRODUCT_PICTURE_UPLOAD'),
                C('PICTURE_UPLOAD_DRIVER'),
                C("UPLOAD_{$pic_driver}_CONFIG")
            );

            if($info==false)
                E($Picture->getError());

            $this->apiSuccess(['data'=>$info]);

        }catch (\Exception $ex){
            $this->apiError(550001,$ex->getMessage());
        }

    }
}
