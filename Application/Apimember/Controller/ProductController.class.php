<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Api\Controller;
use Think\Exception;
use Common\Model\MerchantShop;

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
     * @param double $range 查询半径，单位米，缺省100米
     * @param null|string|array words 关键字，w1,w2... 在title以及description字段中查找
     * @param string words_op  or|and，关键字组合方式
     * @param int $type 商家门店类型，可选0-所有类型，1-超市，2-生鲜，3-洗车，4-送水，缺省0
     * @author  stevin WangJiang
     */
    public function getMerchantList($lat, $lng, $range = 100,$words=null,$words_op='or',$type='0'){
        try{
            $m=new MerchantShop();
            $this->apiSuccess(array('data'=>$m->getList($lat, $lng, $range,$words,$words_op,$type)));
        }catch (Exception $ex){
            $this->apiError(50002,$ex->getMessage());
        }
    }

    /**
     * 根据获取的商家仓库的商品获取商品分类接口
     * @param
     * @author  stevin
     */
    public function getDepotCategory(){

    }

    /**
     * 根据获取的商家仓库的商品获取商品品牌接口
     * @param
     * @author  stevin
     */
    public function getDepotBrand(){

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
     * @param
     * @author  stevin
     */
    public function getProductList(){

    }

    /**
     * 商品详细
     * @param
     * @author  stevin
     */
    public function getProductDetail(){

    }


}