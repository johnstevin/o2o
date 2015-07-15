<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 6/19/15
 * Time: 1:10 PM
 */

namespace Apimember\Controller;
use Common\Model\MerchantModel;

class MerchantController extends ApiController {

    /**
     * 获得附件洗车工
     * @author WangJiang
     * @param float $lat 经度，必须
     * @param float $lng 纬度，必须
     * @param int $range 范围，单位米，必须
     * @param int $range 范围，单位米，必须
     * @param $presetTime 预定时间，绝对时间戳，必须
     * @param string $number 员工号，可选
     * @param string $name 员姓名，可选
     * @param int $page 页号，可选
     * @param int $pageSize 单页大小，可选
     * @return json
     */
    public function getCarWashers($lat, $lng, $range = 100,$presetTime,$name=null,$number=null,$page=1,$pageSize=10){
        try {
            $this->apiSuccess(['data' => (new MerchantModel())
                ->getCarWashersNearby($lat, $lng, $range,$presetTime,$name, $number,$page,$pageSize)],'');
        } catch (\Exception $ex) {
            $this->apiError(51002, $ex->getMessage());
        }
    }

    /**
     * 获得洗车工评价
     * @author WangJiang
     * @param $merchantId  员工ID，必须
     * @param int $page 页号，从1开始
     * @param int $pageSize 每页大小
     * @return json
     * ``` json
     * {
     *    "success": true,
     *    "error_code": 0,
     *    "data":
     *      [
     *          {
     *              "id": "<ID>",
     *              "order_id": "<相关订单ID>",
     *              "shop_id": "<商铺ID>",
     *              "user_id": "<评价客户ID>",
     *              "merchant_id": "<商家ID>",
     *              "content": "<内容>",
     *              "grade_1": "<打分1>",
     *              "grade_2": "<打分1>",
     *              "grade_3": "<打分1>",
     *              "status": "<状态，-1-关闭,1-已评价>",
     *              "update_time": "<修改时间>"
     *          }...
     *      ]
     * }
     * ```
     *调用样例 GET apimber.php?s=Merchant/getAppriseList/merchantId/2
     */
    public function getAppriseList($merchantId,$page = 1, $pageSize = 10){
        try {
            $pageSize > 50 and $pageSize = 50;
            //$page--;
            //$page *= $pageSize;

            $this->apiSuccess(['data' =>D('Appraise')
                ->join('join sq_member on sq_member.uid=sq_appraise.user_id')
                ->join('join sq_ucenter_member on sq_ucenter_member.id=sq_appraise.user_id')
                ->join('left join sq_picture on sq_picture.id=sq_ucenter_member.photo')
                ->where(['merchant_id'=>$merchantId])
                ->page($page, $pageSize)
                ->order('update_time')
                ->field([
                    'sq_appraise.id','ifnull(sq_appraise.content,\'\') as content',
                    'sq_appraise.grade_1','sq_appraise.grade_2','sq_appraise.grade_3',
                    '(sq_appraise.grade_1+sq_appraise.grade_2+sq_appraise.grade_3)/3 as grade',
                    'sq_appraise.update_time','ifnull(sq_picture.path,\'\') as picture_path','sq_member.nickname'
                ])
                ->select()]);
        } catch (\Exception $ex) {
            $this->apiError(50002, $ex->getMessage());
        }
    }
}
