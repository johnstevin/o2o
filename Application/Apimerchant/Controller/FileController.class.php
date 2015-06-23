<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 6/22/15
 * Time: 10:26 AM
 */

namespace Apimerchant\Controller;


class FileController extends ApiController{

    /**
     * 上传图片文件,POST参数,需要accesstoken
     * @author WangJiang
     * @return json
     * ``` json
     * {
     *  "success":true,
     *  "error_code":0,
     *  "data":{
     *      "filedata":{
     *      "name":"c050fce5e0094decb57fdb53f4ca4254.jpg",
     *      "type":2,
     *      "size":23162,
     *      "key":"filedata",
     *      "ext":"jpg",
     *      "md5":"0343aad375caf34b59d7d1683d179dc2",
     *      "sha1":"7b08f4b29ab1eb5d50a936a75738fa58d6e0413b",
     *      "savename":"55891556a0702.jpg",
     *      "savepath":"2015\/06\/23\/",
     *      "create_ip":"2130706433",
     *      "uid":"UID",
     *      "path":"\/Uploads\/Product\/2015\/06\/23\/55891556a0702.jpg",
     *      "id":"13"  图片ID
     *      }
     *  }
     * }
     * ```
     */
    public function uploadPicture(){

        try{
            if(!IS_POST)
                E('非法调用，请用POST调用');
            //验证登录用户
            //测试时注释$this->getToken();

            //print_r($_FILES);
            $this->apiSuccess(['data'=>upload_picture($this->getUserId())]);

        }catch (\Exception $ex){
            $this->apiError(550001,$ex->getMessage());
        }

    }
}
