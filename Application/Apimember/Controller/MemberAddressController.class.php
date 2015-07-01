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
     * @param string $name 收货人姓名
     * @param string $address 收货地址
     * @param string|int $mobile 联系方式
     * @param int $regionId 区域ID
     * @param float $lng 经度
     * @param float $lat 纬度
     * @param bool $isDefault 是否为默认地址
     */
    public function add($name, $address, $mobile, $regionId, $lng, $lat, $isDefault = false)
    {
        $this->getUserId();die;
        $this->apiSuccess(['data' => MemberAddressModel::getInstance()->addAddress($this->getUserId(), $name, $address, $mobile, $regionId, $lng, $lat, $isDefault)]);
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
     * @param bool $isDefault 是否为默认地址
     */
    public function update($id, $name, $address, $mobile, $regionId, $lng, $lat, $isDefault = null)
    {
        $this->apiSuccess(['data' => MemberAddressModel::getInstance()->updateAddress($id, $name, $address, $mobile, $regionId, $lng, $lat, $isDefault)]);
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

    /**
     * 获取地址列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param null|string $name 收货人姓名
     * @param null|int $regionId 区域ID
     * @param int $status 状态
     * @param string|array $fields 要查询的字段
     * @param int $pageSize 分页大小
     * @return null|array
     */
    public function lists($name = null, $regionId = null, $status = null, $fields = '*', $pageSize = 20)
    {
        $this->apiSuccess(MemberAddressModel::getInstance()->getLists($this->getUserId(), $name, $regionId, $status, $fields, $pageSize));
    }
}