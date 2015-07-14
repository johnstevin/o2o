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
     * @param string $carNumber 车牌号
     * @param string $address 地址
     * @param bool $isDefault 是否默认，可传1或0
     * @param int $pictureId 图片ID
     * @param float $lng 经度
     * @param float $lat 纬度
     * @param string $streetNumber 门牌号
     * @param string|int $mobile 联系电话
     */
    public function add($carNumber, $address, $isDefault, $pictureId, $lng, $lat, $streetNumber, $mobile)
    {
        $this->apiSuccess(['data' => MemberAddressVehideModel::getInstance()->addAddress($this->getUserId(), $carNumber, $address, $isDefault, $pictureId, $lng, $lat, $streetNumber, $mobile)]);
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
     * @param string|int $mobile 联系电话
     */
    public function update($id, $carNumber = null, $address = null, $isDefault = null, $pictureId = null, $streetNumber = null, $lng = null, $lat = null, $mobile = null)
    {
        if (!$old = MemberAddressVehideModel::getInstance()->find(intval($id))) E('地址不存在');
        if ($old['user_id'] != $this->getUserId()) E('您无权修改这个地址');
        $this->apiSuccess(['data' => MemberAddressVehideModel::getInstance()->updateAddress($id, $carNumber, $address, $isDefault, $pictureId, $streetNumber, $lng, $lat, $mobile)]);
    }

    /**
     * ## 删除记录 需要验证权限
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $id ID
     */
    public function delete($id)
    {
        if (!$address = MemberAddressVehideModel::getInstance()->find($id)) E('未找到相应的地址记录');
        if ($address['user_id'] != $this->getUserId()) E('您没有权限删除这条记录');
        $this->apiSuccess(['data' => MemberAddressVehideModel::getInstance()->deleteById($id)]);
    }

    /**
     * 获取地址列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param null $status 状态
     * @param int $pageSize 分页大小
     */
    public function lists($status = null, $pageSize = 10)
    {
        $this->apiSuccess(['data' => MemberAddressVehideModel::getInstance()->getList($this->getUserId(), $status, $pageSize)]);
    }

    /**
     * 获得用户的默认地址
     * @author Fufeng Nie <niefufeng@gmail.com>
     */
    public function getDefault()
    {
        $this->apiSuccess(['data' => MemberAddressVehideModel::getInstance()->getDefault($this->getUserId())]);
    }
}