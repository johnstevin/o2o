<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Apimember\Controller;
use Common\Model\MerchantShopModel;
use Think\Exception;
use Common\Model\ProductModel;

/**
 * 商品
 * Class ProductController
 * @package Api\Controller
 */
class ProductController extends ApiController {

    /**
     * 根据经纬度获取附近商家信息接口
     * @param double $lat 查询中心维度，必须是百度坐标
     * @param double $lng 查询中心经度，必须是百度坐标
     * @param int $range 查询半径，单位米，缺省100米
     * @param null|string|array words 关键字，w1,w2... 在title以及description字段中查找
     * @param string words_op  or|and，关键字组合方式
     * @param int $type 商家门店类型，可选0-所有类型，1-超市，2-生鲜，3-洗车，4-送水，缺省0
     * @return mixed
     * @author  stevin WangJiang
     */
    public function getMerchantList($lat, $lng, $range = 100,$words=null,$wordsOp='or',$type=0){
        try{
            $this->apiSuccess('','',array('data'=>(new MerchantShopModel())
                ->getList($lat, $lng, $range,$words,$wordsOp,$type)));
        }catch (Exception $ex){
            $this->apiError(50002,$ex->getMessage());
        }
    }

    /**
     * 根据获取的商家仓库的商品获取商品分类接口
     * @param array|string $groupIds 商家分组ID，可以通过getMerchantList获得
     * @author  stevin
     */
    public function getDepotCategory($groupIds=''){
        if(empty($groupIds)){
            $this->apiError(50003,'参数groupIds不能为空');
            return;
        }

        if(is_string($groupIds))
            $groupIds=explode(',',$groupIds);

        $this->apiSuccess('','',array('data'=>M()->table('sq_merchant_depot_pro_category as a,sq_category as b')
            ->where('a.group_id in (:groupIds) and a.category_id=b.id')
            ->field(['b.id','b.title'])
            ->bind(':groupIds',$groupIds)
            ->select()));
    }

    /**
     * 根据获取的商家仓库的商品获取商品品牌接口
     * @param
     * @author  stevin
     */
    public function getDepotBrand($groupIds=''){
        if(empty($groupIds)){
            $this->apiError(50004,'参数groupIds不能为空');
            return;
        }

        if(is_string($groupIds))
            $groupIds=explode(',',$groupIds);

        $this->apiSuccess('','',array('data'=>M()->table('sq_merchant_depot_pro_category as a,sq_category as b,sq_category_brand_norms as c')
            ->where('a.group_id in (:groupIds) and a.category_id=b.id and ')
            ->field(['b.id','b.title'])
            ->bind(':groupIds',$groupIds)
            ->select()));
    }

    /**
     * 根据获取的商家仓库的商品获取商品规格接口
     * @param
     * @author  stevin
     */
    public function getDepotNorms(){

    }

    /**
     * 根据分类（品牌、规格）获取商品信息接口
     * @param null|string $categoryId 分类ID
     * @param null|int $brandId 品牌ID
     * @param null|string $title 商品标题（模糊查询）
     * @param int $pageSize 页面大小
     * @param int|null $status 状态
     * @return mixed
     * @author  stevin WangJiang
     */
    public function getProductList($categoryId = null, $brandId = null, $status = ProductModel::STATUS_ACTIVE, $title = null, $pageSize = 10){
        $this->apiSuccess('','',array('data'=>ProductModel::getLists($categoryId, $brandId, $status, $title, $pageSize)));
    }

    /**
     * 商品详细
     * @param int $id 上架商品ID
     * @return mixed
     * @author  stevin WangJiang
     */
    public function getProductDetail($id){
        $this->apiSuccess('','',array('data'=>ProductModel::get($id)));
    }


}