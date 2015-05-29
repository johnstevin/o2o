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

    /**
     * 修改商铺信息
     * @author WangJiang
     * @return json
     POST apimchant.php?s=/MerchantShop/update
     参数按照Form表单的格式提交
    {
    "success": true,
    "error_code": 0,
    "message": ""
    }
     */
    public function update(){
        try{
            if(IS_POST){
                $model = D('MerchantShop');
                if(!$model->create())
                    E('参数传递失败');
                $model->save();
                $this->apiSuccess('');
            }else
                E('非法调用');
        }catch (\Exception $ex){
            $this->apiError(50020,$ex->getMessage());
        }
    }

    /**
     * 新增商铺信息
     * @author WangJiang
     * @return json
    POST apimchant.php?s=/MerchantShop/create
    参数按照Form表单的格式提交
    {
    "success": true,
    "error_code": 0,
    "id": 100
    }
     */
    public function create(){
        try{
            if(IS_POST) {
                $model = D('MerchantShop');
                if(!$model->create())
                    E('参数传递失败');
                $this->apiSuccess(null,null,['id'=>intval($model->add())]);
            }else
                E('非法调用');
        }catch (\Exception $ex){
            $this->apiError(50021,$ex->getMessage());
        }
    }

    /**
     * 获得商铺列表
     * @author WangJiang
     * @param $groupId 用户分组ID，注意：该参数应该来自权限
     * @param null $pid 上级商铺ID
     * @param null $regionId 区域ID
     * @param string $type 商铺类型
     * @param null $title 标题，模糊查询
     * @return json
    GET apimchant.php?s=/MerchantShop/getList/groupId/2
    {

        "success": true,
        "error_code": 0,
        "data": [
            {
                "id": 2,
                "title": "Walm",
                "description": "",
                "group_id": 2,
                "status": 1,
                "type": 1,
                "open_status": 1,
                "open_time_mode": 1,
                "begin_open_time": 32400,
                "end_open_time": 64800,
                "delivery_range": 500,
                "phone_number": "88982230",
                "address": "",
                "pid": 0,
                "add_uid": 0,
                "region_id": 0,
                "lnglat": [
                    106.457046,
                    29.584817
                ]
            },
            {
                "id": 3,
                "title": "西南政法大学7.5",
                "description": "",
                "group_id": 2,
                "status": 1,
                "type": 1,
                "open_status": 1,
                "open_time_mode": 1,
                "begin_open_time": 32400,
                "end_open_time": 64800,
                "delivery_range": 500,
                "phone_number": "88982231",
                "address": "",
                "pid": 0,
                "add_uid": 0,
                "region_id": 0,
                "lnglat": [
                    106.448422,
                    29.573258
                ]
            }
        ]

    }
     */
    public function getList($groupId,$pid=null,$regionId=null,$type='0',$title=null){
        try{
            if(IS_GET){
                $model = D('MerchantShop');

                $where['group_id']=$groupId;
                !is_null($pid) and $where['pid']=$pid;
                !is_null($regionId) and $where['region_id']=$regionId;
                $type!=='0' and $where['type']=$type;
                !is_null($title) and $where['title']=['like',"%$title%"];
                $data=$model->field(['id',
                    'title',
                    'description',
                    'group_id',
                    'status',
                    'type',
                    'open_status',
                    'open_time_mode',
                    'begin_open_time',
                    'end_open_time',
                    'delivery_range',
                    'phone_number',
                    'address',
                    'pid',
                    'add_uid',
                    'region_id','st_astext(lnglat) as lnglat'])
                    ->where($where)->select();
                //print_r($model->getLastSql());
                //print_r($data);
                $this->apiSuccess(null,null,['data'=>$data]);
            }else
                E('非法调用');
        }catch (\Exception $ex){
            $this->apiError(50022,$ex->getMessage());
        }
    }

}