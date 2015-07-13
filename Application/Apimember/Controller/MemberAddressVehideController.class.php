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
     * @param $carNumber
     * @param $address
     * @param $isDefault
     * @param $pictureId
     * @param $lng
     * @param $lat
     * @param $streetNumber
     */
    public function add($carNumber, $address, $isDefault, $pictureId, $lng, $lat, $streetNumber)
    {
        $this->apiSuccess(['data' => MemberAddressVehideModel::getInstance()->addAddress($this->getUserId(), $carNumber, $address, $isDefault, $pictureId, $lng, $lat, $streetNumber)]);
    }

    /**
     * 更新地址
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $id 地址ID
     * @param null|string $carNumber 车牌号
     * @param null|string $address 地址
     * @param null|int $isDefault 是否设为默认
     * @param null|int $pictureId 图片ID
     * @param null|string $streetNumber 门牌号
     * @param null|float $lng 经度
     * @param null|float $lat 纬度
     */
    public function update($id, $carNumber = null, $address = null, $isDefault = null, $pictureId = null, $streetNumber = null, $lng = null, $lat = null)
    {
        if (!$old = MemberAddressVehideModel::getInstance()->find(intval($id))) E('地址不存在');
        if ($old['user_id'] != $this->getUserId()) E('您无权修改这个地址');
        $this->apiSuccess(['data' => MemberAddressVehideModel::getInstance()->updateAddress($id, $carNumber, $address, $isDefault, $pictureId, $streetNumber, $lng, $lat)]);
    }
}