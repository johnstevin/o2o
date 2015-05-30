<?php
namespace Apimerchant\Controller;

use Common\Model\MerchantDepotModel;

/**
 * Class MerchantDepot
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @package Apimerchant\Controller
 */
class MerchantDepotController extends ApiController
{
    public function addDepot($shopId, $productId, $price = null, $remark = '')
    {
        $this->apiSuccess(['data' => MerchantDepotModel::addDepot($shopId, $productId, $price, $remark)]);
    }
}