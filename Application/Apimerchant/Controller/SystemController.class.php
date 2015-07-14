<?php
/**
 * Created by Stevin.John
 * Author:  Stevin.John@qq.com
 */

namespace Apimerchant\Controller;


class SystemController extends ApiController
{

    public function apkDownload($update = false)
    {
        $model = M('Version');
        $fields = 'name,path,version,description,forced';
        $map = array(
            'status' => 1,
            'package_type' => C('VERSION_PACKAGE_TYPE.MERCHANT'),
            //'version_type' => C('VERSION_TYPE.RELEASE'),
        );

        $result = $model->field($fields)->where($map)->order('version desc')->limit(1)->select();

        if ($update) {
            try {

                $this->apiSuccess(array('data' => $result[0]), $result[0]['version']);

            } catch (\Exception $ex) {
                $this->apiError(40050, $ex->getMessage());
            }

        } else {
            $path = 'http://'.$_SERVER['HTTP_HOST'] . $result[0]['path'];
            header('Location:'.$path);
            //$this->redirect($path);
        }
    }

}
