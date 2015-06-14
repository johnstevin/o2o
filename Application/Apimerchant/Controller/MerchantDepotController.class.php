<?php
namespace Apimerchant\Controller;

use Common\Model\MerchantDepotModel;
use Common\Model\ProductModel;

/**
 * Class MerchantDepot
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @package Apimerchant\Controller
 */
class MerchantDepotController extends ApiController
{
    /**
     * @ignore
     * @param $shopId
     * @param $productId
     * @param null $price
     * @param string $remark
     */
    public function addDepot($shopId, $productId, $price = null, $remark = '')
    {
        $this->apiSuccess(['data' => MerchantDepotModel::addDepot($shopId, $productId, $price, $remark)]);
    }

    /**
     * <pre>
     * 新增商家商品,POST参数,需要accesstoken
     * int shop_id 商铺id，必须
     * string title 商品名称，必须
     * float price 商品价格，必须
     * int category_id 分类ID，必须
     * int brand_id 品牌ID，必须
     * int norm_id 规格ID，必须
     * int picture 照片ID
     * string remark 备注
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
    public function createProduct()
    {
        try {
            if (!IS_POST)
                E('非法调用，请用POST调用该方法');
            $uid = $this->getUserId();
            $shopId = I('shop_id');
            $this->_varify($uid, $shopId);

            $price = I('price', null);
            $remark = I('remark', '');
            $title = I('title', null);
            $category_id = I('category_id', null);
            $brand_id = I('brand_id', null);
            $norm_id = I('norm_id', null);
            $picture = I('picture', '0');

            if (is_null($price))
                E('商品价格必须提供');
            if (is_null($title))
                E('商品名称必须提供');
            if (is_null($category_id))
                E('商品分类必须提供');
            if (is_null($brand_id))
                E('商品品牌必须提供');
            if (is_null($norm_id))
                E('商品规格必须提供');

            $cateChain = $this->_get_cate_chain([$category_id]);

            D()->startTrans();
            try {
                $productId = D('Product')->add(['title' => $title
                    , 'brand_id' => $brand_id
                    , 'norms_id' => $norm_id
                    , 'price' => $price
                    , 'picture' => $picture
                    , 'status' => ProductModel::STATUS_VERIFY]);

                D('ProductCategory')->add(['product_id' => $productId, 'category_id' => $category_id]);

                foreach ($cateChain as $i) {
                    D('MerchantDepotProCategory')->add(['shop_id' => $shopId, 'category_id' => $i]);
                }
                $model = MerchantDepotModel::getInstance();
                if (($data = $model->create(['shop_id' => $shopId
                        , 'product_id' => $productId
                        , 'price' => $price
                        , 'remark' => $remark
                        , 'status' => MerchantDepotModel::STATUS_VERIFY])) == false
                )
                    E(is_array($model->getError()) ? current($model->getError()) : $model->getError());

                //var_dump($data);die;
                if (($depotId = $model->add($data)) == false)
                    E(is_array($model->getError()) ? current($model->getError()) : $model->getError());

                //var_dump($depotId);die;
                D()->commit();

                $this->apiSuccess(['product_id' => $productId, 'depot_id' => $depotId], '');

            } catch (\Exception $ex) {
                D()->rollback();
                throw $ex;
            }

        } catch (\Exception $ex) {
            $this->apiError(50030, $ex->getMessage());
        }
    }

    /**
     * <pre>
     * 新增商家商品,POST参数,需要accesstoken
     * @param int shop_id 商铺id
     * @param int product_id 商品ID
     * @param float price 商品价格
     * @param string remark 备注
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
                //TODO 验证用户权限
                $uid = $this->getUserId();
                $shopId = I('shop_id');
                $this->_varify($uid, $shopId);

                $productId = I('product_id');
                $price = I('price');
                $remark = I('remark', '');

                $cateChain = $this->_filter_cates($productId, $shopId);

                D()->startTrans();
                try {
                    foreach ($cateChain as $i) {
                        D('MerchantDepotProCategory')->add(['shop_id' => $shopId, 'category_id' => $i]);
                    }
                    $data = D('MerchantDepot')
                        ->create(['shop_id' => $shopId, 'product_id' => $productId, 'price' => $price, 'remark' => $remark]);
                    //var_dump($data);die;
                    $newId = D('MerchantDepot')->add($data);
                    //var_dump($newId);die;
                    D()->commit();
                    $this->apiSuccess(['id' => $newId], '');
                } catch (\Exception $ex) {
                    D()->rollback();
                    throw $ex;
                }
                //$this->apiSuccess(['id' => MerchantDepotModel::addDepot($shopId, $productId, $price, $remark)]);
            } else
                E('非法调用，请用POST调用该方法');
        } catch (\Exception $ex) {
            $this->apiError(50030, $ex->getMessage());
        }
    }

    private function _get_cate_chain($ids, &$ret = [], $catem = null)
    {
        if (is_null($catem))
            $catem = D('Category');
        foreach ($ids as $id) {
            $cate = $catem->find($id);
            if ($cate['pid'] == 0) {
                break;
            }

            if (!in_array($cate['pid'], $ret)) {
                $ret[] = $cate['pid'];
            }
            $pids[] = $cate['pid'];
        }
        if ($pids)
            $this->_get_cate_chain($pids, $ret, $catem);
        return $ret;
    }

    /**
     * <pre>
     * 修改商家商品,需要accesstoken
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
                //TODO 验证用户权限
                $uid = $this->getUserId();
                $this->_varify($uid, I('shop_id'));

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
     * 获得商家商品列表,需要accesstoken
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

            //TODO 验证用户权限
            //$this->getUserId();

            $shopIds = explode(',', $shopIds);
            //print_r($shopIds);die;
            $this->apiSuccess(['data' => (new MerchantDepotModel())->getProductList($shopIds, $categoryId, $brandId, $normId, $title
                , $priceMin, $priceMax, false, $page, $pageSize, $status, $groupIds)]);
        } catch (\Exception $ex) {
            $this->apiError(50032, $ex->getMessage());
        }
    }

    /**
     * @param $productId
     * @param $shopId
     * @return array
     */
    private function _filter_cates($productId, $shopId)
    {
        $cates1 = D('ProductCategory')->where(['product_id' => $productId])->field(['category_id as id'])->select();
        $cateIds = [];
        foreach ($cates1 as $i) {
            $cateIds[] = $i['id'];
        }
        //print_r($cateIds);
        $cateChain = $cateIds;
        $this->_get_cate_chain($cateIds, $cateChain);
        $depotCates = D('MerchantDepotProCategory')->where(['shop_id' => $shopId])->field(['category_id'])->select();
        foreach ($depotCates as $i) {
            $k = array_search($i['category_id'], $cateChain);
            if (false !== $k)
                unset($cateChain[$k]);
        }
        return $cateChain;
    }

    /**
     * @param $uid
     * @param $shopId
     */
    private function _varify($uid, $shopId)
    {
        $shop=D('MerchantShop')->find($shopId);
        $role=D('AuthAccess')->where(['uid'=>$uid,'group_id'=>$shop['group_id']])->first();
        //print_r($role);die;
        if($role['role_id']!=C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_SHOP_MANAGER'))
            E('用户无权限操作该店铺');
    }


}