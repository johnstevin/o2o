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
    /**
     * 添加停车地址
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param $userId
     * @param $carNumber
     * @param $address
     * @param $isDefault
     * @param $pictureId
     * @param $lng
     * @param $lat
     * @param $streetNumber
     */
    public function add($userId, $carNumber, $address, $isDefault, $pictureId, $lng, $lat, $streetNumber)
    {
        $this->apiSuccess(['data' => MemberAddressVehideModel::getInstance()->addAddress($userId, $carNumber, $address, $isDefault, $pictureId, $lng, $lat, $streetNumber)]);
    }
}