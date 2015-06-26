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
     * 新增商家商品，当商品库中没有需要商品时，商家用该接口提交商品信息，需要等待审核,POST参数,需要accesstoken
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
            can_modify_shop($uid, $shopId);

            $price = I('price', null);
            $remark = I('remark', '');
            $title = I('title', null);
            $category_id = I('category_id', null);
            $brand_id = I('brand_id', null);
            $norm_id = I('norm_id', null);
            $picture = I('picture', '0');
            $number = I('number', null);

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
            if (is_null($number))
                E('商品编码不能为空');

            $cateChain = $this->_get_cate_chain([$category_id]);

            D()->startTrans();
            try {
                $productId = D('Product')->add(['title' => $title
                    , 'brand_id' => $brand_id
                    , 'norms_id' => $norm_id
                    , 'price' => $price
                    , 'picture' => $picture
                    , 'number' => $number
                    , 'status' => ProductModel::STATUS_VERIFY
                    , 'create_uid' => $uid
                    , 'source' => 2]);

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
     * int shop_id 商铺id
     * int product_id 商品ID
     * float price 商品价格
     * string remark 备注
     * </pre>
     * @author WangJiang
     * @return json
     * 调用样例
     * POST apimchant.php?s=/MerchantDepot/create
     * ``` json
     * 返回样例
     * {
     * "success": true,
     * "error_code": 0,
     * "message": ""
     * }
     * ```
     */
    public function create()
    {
        try {
            if (IS_POST) {
                //TODO 验证用户权限
                $uid = $this->getUserId();
                $shopId = I('shop_id');
                can_modify_shop($uid, $shopId);

                $productId = I('product_id', null);
                if (is_null($productId)) {
                    $price = I('price', null);
                    $remark = I('remark', '');
                    $description = I('description', '');
                    $title = I('title', null);
                    $category_id = I('category_id', null);
                    $brand_id = I('brand_id', null);
                    $norm_id = I('norm_id', null);
                    $picture = I('picture', '0');
                    $number = I('number', null);

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
                    if (is_null($number))
                        E('商品编码必须提供');

                    if (!D('Norms')->find($norm_id))
                        E('规格不存在');
                    if (!D('Brand')->find($brand_id))
                        E('商品不存在');
                    if (!D('Category')->find($category_id))
                        E('分类不存在');

                    $productSameNumber = D('Product')->where(['number' => $number])->find();

                    //var_dump($productSameNumber);

                    //根据刘辉的建议，判断是否已经有一个带审核的同number商品
                    if ($productSameNumber)
                        $this->_add_depot($productSameNumber['id'],
                            $shopId, $productSameNumber['status'] == ProductModel::STATUS_ACTIVE ?
                                MerchantDepotModel::STATUS_ACTIVE : MerchantDepotModel::STATUS_VERIFY);
                    else {
                        $cateChain = $this->_get_cate_chain([$category_id]);
                        $insertMerchantDepotProCategory = D('MerchantDepotProCategory')->where(['product_id' => $productId, 'category_id' => $category_id])->count() == 0;

                        $model = MerchantDepotModel::getInstance();
                        $model->startTrans();
                        try {
                            $productId = D('Product')->add(['title' => $title
                                , 'brand_id' => $brand_id
                                , 'norms_id' => $norm_id
                                , 'price' => $price
                                , 'picture' => $picture
                                , 'description' => $description
                                , 'number' => $number
                                , 'status' => ProductModel::STATUS_VERIFY
                                , 'create_uid' => $uid
                                , 'source' => 2]);

                            D('ProductCategory')->add(['product_id' => $productId, 'category_id' => $category_id]);
                            if ($insertMerchantDepotProCategory) {
                                foreach ($cateChain as $i) {
                                    D('MerchantDepotProCategory')->add(['shop_id' => $shopId, 'category_id' => $i]);
                                }
                            }

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
                            $model->commit();

                            $this->apiSuccess(['data' => ['product_id' => $productId, 'id' => $depotId]], '');

                        } catch (\Exception $ex) {
                            $model->rollback();
                            throw $ex;
                        }
                    }

                } else
                    $this->_add_depot($productId, $shopId);
                //$this->apiSuccess(['data'=>['id' => MerchantDepotModel::addDepot($shopId, $productId, $price, $remark)]]);
            } else
                E('非法调用，请用POST调用该方法');
        } catch (\Exception $ex) {
            $this->apiError(50030, $ex->getMessage());
        }
    }

    private function _add_depot($productId, $shopId, $status = MerchantDepotModel::STATUS_ACTIVE)
    {
        if (D('MerchantDepot')->where(['product_id' => $productId, 'shop_id' => $shopId])->count() > 0)
            E('该商品已添加');
        if (!D('Product')->find($productId))
            E('该商品不存在');

        $price = I('price');
        $remark = I('remark', '');

        $cateChain = $this->_filter_cates($productId, $shopId);

        //var_dump($status);
        //var_dump($productId);die;
        $m = D('MerchantDepot');
        $data = $m->create(['shop_id' => $shopId, 'product_id' => $productId,
            'status' => $status, 'price' => $price, 'remark' => $remark]);

        $d = D();
        $d->startTrans();
        try {
            foreach ($cateChain as $i) {
                D('MerchantDepotProCategory')->add(['shop_id' => $shopId, 'category_id' => $i]);
            }
            //var_dump($data);die;
            $newId = $m->add($data);
            //var_dump($newId);die;
            $d->commit();
            $this->apiSuccess(['data' => ['product_id' => $productId, 'id' => $newId]], '');
        } catch (\Exception $ex) {
            $d->rollback();
            throw $ex;
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
                $model = D('MerchantDepot');
                $depot = $model->find(I('id'));
                can_modify_shop($uid, $depot['shop_id']);

                if (!$model->create())
                    E('参数传递失败');
                $model->save();
                $this->apiSuccess(['data' => []], '');
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
        , $priceMin = null, $priceMax = null, $page = 1, $pageSize = 10, $status = MerchantDepotModel::STATUS_ACTIVE, $groupIds = [])
    {
        try {
            if (!IS_GET)
                E('非法调用，请用GET调用该方法');
            $pageSize > 50 and $pageSize = 50;
            $page--;
            $page *= $pageSize;

            //TODO 验证用户权限
            //$this->getUserId();

            $shopIds = explode(',', $shopIds);
            //print_r($shopIds);die;
            $this->apiSuccess(['data' => MerchantDepotModel::getListsByShopId($shopIds, $pageSize, $status)], '');
            //$this->apiSuccess(['data' => (new MerchantDepotModel())->getProductList($shopIds, $categoryId, $brandId, $normId, $title
            //  , $priceMin, $priceMax, false, $page, $pageSize, $status, $groupIds)]);
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
     * ## 获取仓库商品列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param null|int $shopId 商铺ID，可选参数
     * @param null|int|string|array $categoryIds 分类ID，可选参数。可传多个ID，格式为数组或者半角【,】隔开的ID字符串
     * @param null|int $status 状态，可选参数
     * @param null|string $title 标题，可选参数
     * @param string $sort 添加时间排序，可选参数，可传【ASC】或者【DESC】
     * @param bool $getCategorys 是否获得分类信息，可选参数
     * @param bool $getBrand 是否获得品牌信息，可选参数
     * @param int $pageSize 分页大小，可选参数
     * @param bool $getPicture 是否获取商品图片，可选参数
     * @param bool $getNorm 是否获取规格信息，可选参数
     * @param bool $getShop 是否获取商铺信息，可选参数
     */
    public function lists($shopId = null, $categoryIds = null, $status = null, $title = null, $sort = 'asc', $getCategorys = true, $getBrand = true, $pageSize = 20, $getPicture = true, $getNorm = true, $getShop = false)
    {
        $this->apiSuccess(MerchantDepotModel::getInstance()->getLists($shopId, $categoryIds, $status, $title, $sort, $pageSize, $getPicture, $getCategorys, $getBrand, $getNorm, $getShop));
    }

    /**
     * ## 下架商品（可批量），只能下架【已上架】的商品。`需要验证权限`
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 商品ID
     */
    public function offShelf($id)
    {
        if (!IS_POST) E('非法请求', 400);
        bacth_check_can_modify_depot($this->getUserId(), $id);
        $this->apiSuccess(['data' => MerchantDepotModel::getInstance()->offShelf($id)]);
    }

    /**
     * ## 上架商品（可批量）。只能上架【已下架】的商品。`需要验证权限`
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 商品ID
     */
    public function onShelf($id)
    {
        if (!IS_POST) E('非法请求', 400);
        bacth_check_can_modify_depot($this->getUserId(), $id);
        $this->apiSuccess(['data' => MerchantDepotModel::getInstance()->onShelf($id)]);
    }

}