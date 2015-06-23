<?php
namespace Apimerchant\Controller;

use Common\Model\ProductModel;

/**
 * 商品控制器
 * @package Apimerchant\Controller
 */
class ProductController extends ApiController
{
    /**
     * 根据商品条形码查询商品
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int|string $number
     * @param string $fileds 要查询的字段
     * @param bool $getBrand 是否要获得品牌信息
     * @param bool $getCategorys 是否要获得分类信息
     * @param bool $getNorm 是否获得规格信息
     */
    public function findByNumber($number, $fileds = '*', $getBrand = true, $getCategorys = true, $getNorm = true)
    {
        $this->apiSuccess(['data' => ProductModel::getInstance()->getByNumber($number, $fileds, $getCategorys, $getBrand, $getNorm)]);
    }
}