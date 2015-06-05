<?php
// +----------------------------------------------------------------------
// | 商户管理模块
// +----------------------------------------------------------------------
// | Data:2015-5-20
// +----------------------------------------------------------------------
namespace Admin\Controller;

class MerchantController extends AdminController {

    public function index(){

    }

    /**
     * 商户列表
     * 1.
     */
    public function lists(){
        if(IS_POST){

        }else{
            $model = D('MerchantShop');
            $result = $model->lists(null, null , '*');
            $this->assign('_meta_title', '商户列表');
            $this->assign('_list', $result);
            $this->display();
        }

    }

    /**
     * 商户审核
     * 1.管理员登录后台
    2.判断角色拥有的权限（eg：角色-鹭岛小区审核员）
    3.Merchant_shop找出region_id=鹭岛小区id所有的商铺信息
    4.查看详情，添加用户add_uid等信息
    5.审核（-1软删除,0-待审核,1-审核通过,2-审核中,3-审核未通过）
    6.审核通过（这里先不自动化处理，问题点：有可能会重复添加等等问题系统无法控制）
    A. Auth_group根据店铺类别：超市，创建下面的组：红旗超市
    B. Auth_group_region根据region_id=鹭岛小区id，和红旗超市组添加
    C. Auth_role 添加角色权限无法实现控制
    D. Auth_access 给add_uid分配角色，状态1
    E. Merchant_shop的status状态置1审核通过
    问题，同一个红旗超市，可能会添加两次，无法做判断控制等等很多问题。
    手动处理
    A. 在商户-超市组（店铺类别：超市）下添加组：红旗超市(并选择区域：鹭岛小区)
    B. 添加角色：老板
    C. 给add_uid授权组织和角色，状态1
    D. Merchant_shop的status状态置1审核通过
     */
    public function checkInfo(){
        if(IS_POST){
            $shop_id = is_numeric(I('post.shop_id')) ? I('post.shop_id') : 0;
            $status  = is_numeric(I('post.status')) ? I('post.status') : 0;
            ($shop_id !== 0 || $status !== 0) ? : $this->error('禁止操作');
            //检查商户是否存在
            $info  = D('MerchantShop')->info($shop_id, '*');
            if(empty($info))
                $this->error('没有此商铺');


            $this->success('成功');

        }else{

            $shop_id = is_numeric(I('get.shop_id')) ? I('get.shop_id') : 0;
            $shop_id !== 0 ? : $this->error('禁止操作');
            $result  = D('MerchantShop')->info($shop_id, '*');
            $this->assign('_info', $result);
            $this->assign('_meta_title', '商户审核');
            $this->display();
        }
    }

}