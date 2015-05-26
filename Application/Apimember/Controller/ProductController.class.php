<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Apimember\Controller;

use Common\Model\ProductModel;

/**
 * 商品
 * Class ProductController
 * @package Api\Controller
 */
class ProductController extends ApiController
{

    /**
     * 根据经纬度获取附近商家信息接口
     * @param
     * @author  stevin
     */
    public function getMerchantList()
    {

    }

    /**
     * 根据获取的商家仓库的商品获取商品分类接口
     * @param
     * @author  stevin
     */
    public function getDepotCategory()
    {

    }

    /**
     * 根据获取的商家仓库的商品获取商品品牌接口
     * @param
     * @author  stevin
     */
    public function getDepotBrand()
    {

    }

    /**
     * 根据获取的商家仓库的商品获取商品规格接口
     * @param
     * @author  stevin
     */
    public function getDepotNorms()
    {

    }

    /**
     * 根据分类（品牌、规格）获取商品信息接口
     * @param
     * @author  stevin
     */
    public function getProductList()
    {

    }

    /**
     * 商品详细
     * @param
     * @author  stevin
     */
    public function getProductDetail()
    {

    }

    /**
     * 活取列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param null|string $categoryId 分类ID
     * @param null|int $brandId 品牌ID
     * @param null|string $title 商品标题（模糊查询）
     * @param int $pagesize 页面大小
     * @param int|null $status 状态
     * @param string|array $relation 是否进行关联查询
     * @return json
     */
    public function lists($categoryId = null, $brandId = null, $title = null, $pagesize = 10, $status = ProductModel::STATUS_ACTIVE, $relation = [])
    {
        $this->apiSuccess(['data' => ProductModel::getLists($categoryId, $brandId, $status, $title, $pagesize, $relation)['data']]);
    }

    /**
     * 根据ID查找单条记录
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id
     * @param bool|string|array $fields 要查询的字段
     * @return json|xml
     */
    public function find($id, $fields = true)
    {
        $this->apiSuccess(['data' => ProductModel::get($id, $fields)]);
    }

}