<?php
/**
 * Created by PhpStorm.
 * User: biu
 * Date: 5/30/15
 * Time: 9:42 AM
 */
namespace Apimember\Controller;

use Common\Model\MemberAddressModel;

class MemberAddressController extends ApiController
{
    /**
     * 添加地址、
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $uid 用户ID
     * @param string $name 收货人姓名
     * @param string $address 收货地址
     * @param string|int $mobile 联系方式
     * @param int $regionId 区域ID
     * @param float $lng 经度
     * @param float $lat 纬度
     */
    public function add($uid, $name, $address, $mobile, $regionId, $lng, $lat)
    {
        $this->apiSuccess(['data' => MemberAddressModel::getInstance()->addAddress($uid, $name, $address, $mobile, $regionId, $lng, $lat)]);
    }

    /**
     * 更新地址信息
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 地址ID
     * @param string $name 收货人姓名
     * @param string $address 收货地址
     * @param int|string $mobile 联系方式
     * @param int $regionId 区域ID
     * @param float $lng 经度
     * @param float $lat 纬度
     */
    public function update($id, $name, $address, $mobile, $regionId, $lng, $lat)
    {
        $this->apiSuccess(['data' => MemberAddressModel::getInstance()->updateAddress($id, $name, $address, $mobile, $regionId, $lng, $lat)]);
    }

    /**
     * 逻辑删除地址
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 地址ID
     */
    public function delete($id)
    {
        $this->apiSuccess(['data' => MemberAddressModel::deleteAddress($id)]);
    }

    /**
     * 把逻辑删除的地址恢复
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 地址ID
     */
    public function active($id)
    {
        $this->apiSuccess(['data' => MemberAddressModel::activeAddress($id)]);
    }

    public function lists()
    {
    }
}