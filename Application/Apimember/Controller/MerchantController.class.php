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
     * @param string $number 员工号，可选
     * @param string $name 员姓名，可选
     * @param int $page 页号，可选
     * @param int $pageSize 单页大小，可选
     * @return json
     */
    public function getCarWashers($lat, $lng, $range = 100,$name=null,$number=null,$page=1,$pageSize=10){
        try {
            $pageSize > 50 and $pageSize = 50;
            $page--;
            $page *= $pageSize;

            $this->apiSuccess(['data' => (new MerchantModel())
                ->getCarWashersNearby($lat, $lng, $range,$name, $number,$page,$pageSize)],'');
        } catch (\Exception $ex) {
            $this->apiError(51002, $ex->getMessage());
        }
    }
}