<?php
namespace Apimember\Controller;

use Common\Model\MerchantDepotModel;

class MerchantDepotController extends ApiController
{
    /**
     * 根据ID获取仓库的商品信息
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $id 仓库商品的ID，注意，是depot_id
     * @param bool|false $getCategory 是否获取分类信息
     * @param bool|false $getBrand 是否获取品牌信息
     * @param bool|false $getNorm 是否获取规格信息
     * @param bool|false $getShop 是否获取商铺信息
     */
    public function getById($id, $getCategory = false, $getBrand = false, $getNorm = false, $getShop = false)
    {
        $this->apiSuccess(['data' => MerchantDepotModel::getInstance()->getById($id, null, $getCategory, $getShop, $getBrand, $getNorm)]);
    }
}