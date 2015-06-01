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

    /**
     * <pre>
     * 新增商家商品
     * shop_id 商铺id
     * product_id 商品ID
     * price 商品价格
     * remark 备注
     * </pre>
     * @author WangJiang
     * @return json
     * <pre>
     * 调用样例
     * POST apimchant.php?s=/MerchantDepot/create
     * 返回样例
     * {
     * "success": true,
     * "error_code": 0,
     * "message": ""
     * }
     * </pre>
     */
    public function create()
    {
        try {
            if (IS_POST) {
                $shopId = I('shop_id');
                $productId = I('product_id');
                $price = I('price');
                $remark = I('remark', '');
                $this->apiSuccess(['data' => MerchantDepotModel::addDepot($shopId, $productId, $price, $remark)]);
            } else
                E('非法调用，请用POST调用该方法');
        } catch (\Exception $ex) {
            $this->apiError(50030, $ex->getMessage());
        }
    }

    /**
     * <pre>
     * 修改商家商品
     * id 上架商品ID
     * shop_id 商铺id
     * product_id 商品ID
     * status 商品状态
     * price 商品价格
     * remark 备注
     * </pre>
     * @author WangJiang
     * @return json
     * <pre>
     * 调用样例
     * POST apimchant.php?s=/MerchantDepot/update
     * 返回样例
     * {
     * "success": true,
     * "error_code": 0,
     * "message": ""
     * }
     * </pre>
     */
    public function update()
    {
        try {
            if (IS_POST) {
                //print_r($_POST);die;
                $model = D('MerchantDepot');
                if (!$model->create())
                    E('参数传递失败');
                $model->save();
                $this->apiSuccess(null, '');
            } else
                E('非法调用，请用POST调用该方法');
        } catch (\Exception $ex) {
            $this->apiError(50031, $ex->getMessage());
        }
    }

    /**
     * 获得商家商品列表
     * @author WangJiang
     * @param array $shopIds 商铺ID，多个用‘,’隔开
     * @param null|string $categoryId 分类ID
     * @param null|int $brandId 品牌ID
     * @param null|int $normId 规格ID
     * @param null|string $title 商品标题（模糊查询）
     * @param null|int $priceMin 商品售价下限，未null则忽略
     * @param null|int $priceMax 商品售价上限，未null则忽略
     * @param int $page 分页下标，从0开始
     * @param int $pageSize 页面大小
     * @param int $status 查询状态，-1：逻辑删除,0:不可用，1：可用，为空返回所有状态
     * @param array $groupIds 用户分组ID，多个用‘,’隔开，注意：该参数应该从登录用户那里获得
     * @return json
     * 调用样例 GET apimchant.php?s=/MerchantDepot/getList/shopIds/3/pageSize/2
     * 返回
     *``` json
     * {
     *   "success": true,
     *   "error_code": 0,
     *   "data": [
     *     {
     *       "id": "38637",
     *       "product_id": "1",
     *       "product": "妮维雅凝水活才保湿眼霜",
     *       "price": 97.45,
     *       "shop_id": "3",
     *       "shop": "西南政法大学7.5",
     *       "brand": "妮维雅",
     *       "norm": "瓶"
     *     },
     *     {
     *       "id": "38639",
     *       "product_id": "2",
     *       "product": "爱得利十字孔家居百货05奶嘴",
     *       "price": 1.83,
     *       "shop_id": "3",
     *       "shop": "西南政法大学7.5",
     *       "brand": "爱得利",
     *       "norm": "个"
     *     }
     *   ]
     * }
     *```
     */
    public function getList($shopIds = null, $categoryId = null, $brandId = null, $normId = null, $title = null
        , $priceMin = null, $priceMax = null, $page = 0, $pageSize = 10, $status = MerchantDepotModel::STATUS_ACTIVE, $groupIds = [])
    {
        try {
            if (!IS_GET)
                E('非法调用，请用GET调用该方法');
            $shopIds = explode(',', $shopIds);
            //print_r($shopIds);die;
            $this->apiSuccess(['data' => (new MerchantDepotModel())->getProductList($shopIds, $categoryId, $brandId, $normId, $title
                , $priceMin, $priceMax, false, $page, $pageSize, $status, $groupIds)]);
        } catch (\Exception $ex) {
            $this->apiError(50032, $ex->getMessage());
        }
    }


}