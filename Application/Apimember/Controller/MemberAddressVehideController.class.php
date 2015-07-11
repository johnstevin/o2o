<?php
namespace Apimember\Controller;
use Common\Model\MemberAddressVehideModel;


/**
 * 停车地址控制器
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @package Apimember\Controller
 */
class MemberAddressVehideController extends ApiController
{
    public function add($userId, $carNumber, $address, $isDefault, $pictureId, $lng, $lat, $regionId = null)
    {
        $this->apiSuccess(['data' => MemberAddressVehideModel::getInstance()->addAddress($userId, $carNumber, $address, $isDefault, $pictureId, $lng, $lat, $regionId)]);
    }
}