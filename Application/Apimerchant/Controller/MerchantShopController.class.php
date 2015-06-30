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
class MerchantShopController extends ApiController
{

    /**
     * <pre>
     * 修改商铺信息,需要accesstoken
     * 参数按照Form表单的格式提交，参数列表：
     * int id 商铺ID，必需提供
     * string title 店面名称
     * string description 店面介绍
     * string lnglat 格式为'lng lat'，店面坐标，采用百度地图经纬度
     * int open_status 营业状态：0-关闭，1-开放
     * int open_time_mode 营业时间模式，1-有时间段，2-7X24小时
     * int begin_open_time 营业开始时间，24小时内，单位秒,缺省9点
     * int end_open_time 营业结束时间，24小时内，单位秒，缺省18点
     * string phone_number 店面电话，客户可以直接联系
     * string address 店面地址，供客户参考
     * pay_delivery_time 付费送货开始时间，24小时内，单位秒，缺省0点，即不设置,
     * delivery_time_cost 送货时间加价金额:单位元,
     * delivery_distance_limit 送货范围上限:单位米,
     * free_delivery_distance 免费送货距离:单位米,
     * pay_delivery_distance 付费送货距离:单位米,
     * delivery_distance_cost 送货距离加价金额:单位元,
     * pay_delivery_amount 免费送货总金额:单位元,
     * delivery_amount_cost 送货总金额加价金额:单位元,
     * pay_delivery_mode 送货加价模式：1-总金额优先，2-距离优先，3-时间段优先,
     * </pre>
     * @author WangJiang
     * @return json
    调用样例
     * POST apimchant.php?s=/MerchantShop/update
     * 返回样例
     * {
     * "success": true,
     * "error_code": 0,
     * "message": ""
     * }
     * </pre>
     */
    public function update()
    {
        try {
            if (IS_POST) {
                $model = D('MerchantShop');
                if (!($data=$model->create()))
                    E('参数传递失败');

                can_modify_shop($this->getUserId(),$data['id']);

                $model->data($data);
                //var_dump($data);die;
                $model->save();
                $this->apiSuccess(['data'=>[]], '');
            } else
                E('非法调用');
        } catch (\Exception $ex) {
            $this->apiError(50020, $ex->getMessage());
        }
    }

    /**
     * 新增商铺信息,需要accesstoken
     * @internal 参数按照Form表单的格式提交，参数列表参考{@link update()}
     * string title 店面名称
     * string description 店面介绍
     * string lnglat 格式为'lng lat'，店面坐标，采用百度地图经纬度
     * int open_status 营业状态：0-关闭，1-开放
     * int open_time_mode 营业时间模式，1-有时间段，2-7X24小时
     * int begin_open_time 营业开始时间，24小时内，单位秒,缺省9点
     * int end_open_time 营业结束时间，24小时内，单位秒，缺省18点
     * string phone_number 店面电话，客户可以直接联系
     * string address 店面地址，供客户参考
     * pay_delivery_time 付费送货开始时间，24小时内，单位秒，缺省0点，即不设置,
     * delivery_time_cost 送货时间加价金额:单位元,
     * delivery_distance_limit 送货范围上限:单位米,
     * free_delivery_distance 免费送货距离:单位米,
     * pay_delivery_distance 付费送货距离:单位米,
     * delivery_distance_cost 送货距离加价金额:单位元,
     * pay_delivery_amount 免费送货总金额:单位元,
     * delivery_amount_cost 送货总金额加价金额:单位元,
     * pay_delivery_mode 送货加价模式：1-总金额优先，2-距离优先，3-时间段优先,
     * yyzz_picture  '营业执照',
     * spwsxkz_picture  '食品卫生许可证',
     * id_cart_front_picture '法人身份证照片正面',
     * id_cart_back_picture '法人身份证照片背面',
     * @author WangJiang
     * @return json
     * <pre>
     * 调用样例 POST apimchant.php?s=/MerchantShop/create
     * 参数按照Form表单的格式提交
     * {
     * "success": true,
     * "error_code": 0,
     * "id": 100
     * }</pre>
     */
    public function create()
    {
        try {
            if (IS_POST) {
                $model = D('MerchantShop');
                if (!($data=$model->create()))
                    E('参数传递失败');
                //TODO 验证用户权限
                $data['add_uid']=$this->getUserId();
                $data['group_id']=$this->_get_group_id($data['type']);
                $model->data($data);
                $this->apiSuccess(['id' => intval($model->add())],'');
            } else
                E('非法调用');
        } catch (\Exception $ex) {
            $this->apiError(50021, $ex->getMessage());
        }
    }

    private function _get_group_id($type){
        return $type==1
            ?C('AUTH_GROUP_ID.GROUP_ID_MERCHANT_SHOP')
            :C('AUTH_GROUP_ID.GROUP_ID_MERCHANT_VEHICLE');
    }

    /**
     * 获得商铺列表,需要accesstoken
     * @author WangJiang
     * @param null $regionId 区域ID
     * @param string $type 商铺类型
     * @param null $title 标题，模糊查询
     * @return json
    调用样例 GET apimchant.php?s=/MerchantShop/getList/groupId/2
     * ''' json
     *{
     *  "success": true,
     *  "error_code": 0,
     *   "data": [
     *       {
     *           "id": 2,
     *           "title": "Walm",
     *           "description": "",
     *           "group_id": 2,
     *           "status": 1,
     *           "type": 1,
     *           "open_status": 1,
     *           "open_time_mode": 1,
     *           "begin_open_time": 32400,
     *           "end_open_time": 64800,
     *           "delivery_range": 500,
     *           "phone_number": "88982230",
     *           "address": "",
     *           "pid": 0,
     *           "add_uid": 0,
     *           "region_id": 0,
     *           "lnglat": [
     *               106.457046,
     *               29.584817
     *           ]
     *       },
     *       {
     *           "id": 3,
     *           "title": "西南政法大学7.5",
     *           "description": "",
     *           "group_id": 2,
     *           "status": 1,
     *           "type": 1,
     *           "open_status": 1,
     *           "open_time_mode": 1,
     *           "begin_open_time": 32400,
     *           "end_open_time": 64800,
     *           "delivery_range": 500,
     *           "phone_number": "88982231",
     *           "address": "",
     *           "pid": 0,
     *           "add_uid": 0,
     *           "region_id": 0,
     *           "lnglat": [
     *               106.448422,
     *               29.573258
     *           ]
     *       }
     *   ]
     *}
     * '''
     */
    public function getList($regionId = null, $type = 1, $title = null,$status=null,$page=1,$pageSize=10)
    {
        try {
            if (IS_GET) {
                //TODO 验证用户权限
                $pageSize > 50 and $pageSize = 50;
                $page--;
                $page *= $pageSize;

                //获得担任店长的商铺组
                $groupIds=$this->getUserGroupIds(C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_SHOP_MANAGER'));
                $model = D('MerchantShop');

                $where['group_id'] = ['in',$groupIds];

                !is_null($regionId) and $where['region_id'] = $regionId;
                $type !== '0' and $where['sq_merchant_shop.type'] = $type;
                !is_null($title) and $where['title'] = ['like', "%$title%"];
                !is_null($status) and $where['sq_merchant_shop.status']=$status;
                $data = $model->field(['sq_merchant_shop.id',
                    'title',
                    'description',
                    'group_id',
                    'sq_merchant_shop.status',
                    'sq_merchant_shop.type',
                    'open_status',
                    'open_time_mode',
                    'begin_open_time',
                    'end_open_time',
                    'phone_number',
                    'address',
                    'add_uid',
                    'region_id',
                    'pay_delivery_time',
                    'delivery_time_cost',
                    'delivery_distance_limit',
                    'pay_delivery_distance',
                    'delivery_distance_cost',
                    'free_delivery_amount',
                    'pay_delivery_amount',
                    'delivery_amount_cost',
                    'message',
                    'photo.id as picture',
                    //TODO 暂时这么做，后期删除
                    'sq_merchant_shop.status',
                    //end
                    'ifnull(photo.path,\'\') as picture_path',
                    'yyzz_picture.id as yyzz_picture_id',
                    'ifnull(yyzz_picture.path,\'\') as yyzz_picture_path',
                    'st_astext(sq_merchant_shop.lnglat) as lnglat'])
                    ->join('left join sq_picture as photo on photo.id=sq_merchant_shop.picture')
                    ->join('left join sq_picture as yyzz_picture on yyzz_picture.id=sq_merchant_shop.yyzz_picture')
                    ->where($where)->limit($page,$pageSize)->select();

                foreach($data as &$i){
                    $sid=$i['id'];
                    $tags=D()->query("select tag_id from sq_shop_tag where shop_id=$sid;");
                    //print_r($i);print_r($tags);die;
                    $i['tags']=[];
                    foreach($tags as $t){
                        $i['tags'][]=$t['tag_id'];
                    }
                }

                //print_r($model->getLastSql());die;
                //print_r($data);die;
                $this->apiSuccess(['data' => $data],'');
            } else
                E('非法调用');
        } catch (\Exception $ex) {
            $this->apiError(50022, $ex->getMessage());
        }
    }

    /**
     * 获得所有店铺类型
     * @return json
     * 调用样例 GET apimchant.php?s=MerchantShop/getTypes
     * ``` json
     * {
     *      "success": true,
     *      "error_code": 0,
     *      "data":
     *      {
     *          "17": "超市",
     *          "18": "洗车",
     *          "89": "生鲜",
     *          "90": "送水"
     *      }
     * }
     * ```
     */
//    public function getTypes(){
//        try{
//            $this->apiSuccess(['data'=>C('SHOP_TYPE')]);
//        }catch (\Exception $ex){
//            $this->apiError(50024,$ex->getMessage());
//        }
//    }

//    /**
//     * <pre>
//     * 设置店铺临时状态
//     * POST临时参数如下：
//     * </pre>
//     * ''' json
//     * {
//     *      "delay_time":<延时开店时间，单位秒，可选>,
//     *      "open_status":<临时关闭状态，1-打开，0-关闭，可选>
//     * }
//     * '''
//     * @author WangJiang
//     * @param $shopId 店铺ID
//     * @return json
//     */
//    public function setTemporary($shopId){
//        try{
//            if(IS_GET)
//                E('非法操作');
//            $content=json_decode(file_get_contents('php://input'));
//            if(isset($content['open_status']))
//                F('shop_temporary_open_status_'.$shopId,$content['open_status']);
//            if(isset($content['delay_time'])) {
//                $close_time = time() + $content['delay_time'];
//                F('shop_temporary_close_time_' . $shopId, $close_time);
//            }
//            $this->apiSuccess(null);
//        }catch (\Exception $ex){
//            $this->apiError(50023,$ex->getMessage());
//        }
//    }
//
//    /**
//     * 获取店铺临时状态
//     * @author WangJiang
//     * @param $shopId
//     */
//    public function getTemporary($shopId){
//        try{
//            if(IS_POST)
//                E('非法操作');
//            $ret=['shop_id'=>$shopId];
//            $open_status=F('shop_temporary_open_status_'.$shopId);
//            if($open_status!==false)
//                $ret['open_status']=$open_status;
//            $close_time=F('shop_temporary_close_time_'.$shopId);
//            if($close_time!==false)
//                $ret['close_time']=$close_time;
//            $this->apiSuccess(['data'=>$ret]);
//        }catch (\Exception $ex){
//            $this->apiError(50023,$ex->getMessage());
//        }
//    }

}