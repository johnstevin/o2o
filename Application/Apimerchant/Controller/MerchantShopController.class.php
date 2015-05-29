<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 15-5-28
 * Time: 上午11:45
 */

namespace Apimerchant\Controller;
use Common\Model\MerchantShopModel;

/**
 * Class MerchantShopController
 * @package Apimerchant\Controller
 * @author WangJiang
 */
class MerchantShopController extends ApiController {

    public function update(){
        if(IS_POST){
            $model = D('MerchantShop');
            if(!$model->create())
                E('参数传递失败');
            $model->save();
            $this->apiSuccess('');
        }else
            E('非法调用');
    }

    public function create(){
        if(IS_POST) {
            $model = D('MerchantShop');
            if(!$model->create())
                E('参数传递失败');
            $model->add();
            $this->apiSuccess('');
        }else
            E('非法调用');
    }

}