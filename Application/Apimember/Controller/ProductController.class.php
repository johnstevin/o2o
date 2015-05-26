<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Apimember\Controller;
use Common\Model\CategoryModel;
use Common\Model\MerchantShopModel;
use Think\Exception;
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
            $this->apiSuccess(array('data'=>(new MerchantShopModel())
                ->getList($lat, $lng, $range,$words,$wordsOp,$type)));
        }catch (Exception $ex){
            $this->apiError(50002,$ex->getMessage());
        }
    }

    public function getMerchantDetail($id){
        try{
            $this->apiSuccess(array('data'=>(new MerchantShopModel())->get($id)));
        }catch (Exception $ex){
            $this->apiError(50003,$ex->getMessage());
        }
    }

    /**
     * 根据获取的商家仓库的商品获取商品分类接口
     * @param int $level 指定返回分类层级，为空则不限制分类
     * @param int $pid 指定上级分类，为空则忽略，如果设置了$level则忽略该参数
     * @param array|string $shopIds 商铺ID，可以通过getMerchantList获得，该参数可选
     * @param true|false $return_brands_norms 返回相关品牌规格等
     * @return mixed
     * @author  stevin WangJiang
     */
    public function getDepotCategory($level=null,$pid=null,$shopIds='',$return_brands_norms='false'){
        //TODO:开发平台上测试的效率不理想，需要进一步优化，修改sq_merchant_depot_pro_category的数据，每次商品上架，该表必须保存指定分类以及他的所有上级节点
        try{
            empty($shopIds) and E('参数shopIds不能为空');

            $return_brands_norms=$return_brands_norms==='true';

            $shopIds=explode(',',$shopIds);
            list($bindNames, $bindValues) = build_sql_bind($shopIds);

            $sql=M()->table('sq_merchant_depot_pro_category as a,sq_category as b')
                ->where('a.shop_id in ('.implode(',',$bindNames).') and a.category_id=b.id')
                ->field(['b.id','b.title','b.pid','b.level'])
                ->bind($bindValues);

            $data=$sql->select();

            //print_r($bindNames);
            //print_r($sql->getLastSql());

            $ret=[];
            $cats=[];
            $topHint=[];
            $catIds=[];
            if(!is_null($level)){
                //$begin = microtime(true);

                foreach($data as $i){
                    //echo json_encode(CategoryModel::get($i['id']));


                    if($i['level']==$level) {
                        if(!in_array($i['id'],$topHint)){
                            $cats[] = $i;
                            $topHint[]=$i['id'];
                        }
                        !in_array($i['id'],$catIds) and $catIds[]=$i['id'];
                    }else if($i['level']>=$level){
                        $temp=[$i['id']];
                        $top=$this->_find_level_top($i,$level,$temp);
                        if(!is_null($top)) {
                            if (!in_array($top['id'], $topHint)) {
                                $cats[] = $top;
                                $topHint[] = $top['id'];
                            }
                            foreach($temp as $id){
                                !in_array($id,$catIds) and $catIds[]=$id;
                            }
                        }
                    }
                }
                //echo (microtime(true) - $begin);die;
            }else if(!is_null($pid)){
                foreach($data as $i){
                    //echo json_encode(CategoryModel::get($i['id']));
                    if($i['pid']==$pid) {
                        if(!in_array($i['id'],$topHint)){
                            $cats[] = $i;
                            $topHint[]=$i['id'];
                        }
                        !in_array($i['id'],$catIds) and $catIds[]=$i['id'];
                    }else if($i['pid']>0){
                        $temp=[$i['id']];
                        $top=$this->_find_parent_top($i,$pid,$temp);
                        if(!is_null($top)){
                            if(!in_array($top['id'],$topHint)){
                                $cats[] = $top;
                                $topHint[]=$top['id'];
                            }
                            foreach($temp as $id){
                                !in_array($id,$catIds) and $catIds[]=$id;
                            }
                        }
                    }
                }
            }else
                E('参数level或pid不能全部为空');

            $ret['categories']=$cats;

            //print_r($catIds);

            $brands=[];
            $norms=[];
            if($return_brands_norms and !empty($catIds)){
                //print_r($catIds);
                list($bindNames, $bindValues) = build_sql_bind($catIds);
                $sql=M()->table('sq_category_brand_norms as l')
                    ->field(['sq_brand.id as bid','sq_brand.title as brand','sq_norms.id as nid','sq_norms.title as norm'])
                    ->join('LEFT JOIN sq_norms on sq_norms.id=l.norms_id')
                    ->join('left JOIN sq_brand on sq_brand.id=l.brand_id')
                    ->where('l.category_id in ('.implode(',',$bindNames).')')
                    ->bind($bindValues);

                $temp=$sql->select();

                $bidHint=[];
                $nidHint=[];
                foreach($temp as $i){
                    if(!in_array($i['bid'],$bidHint)){
                        $bidHint[]=$i['bid'];
                        $brands[]=array('id'=>$i['bid'],'title'=>$i['brand']);
                    }

                    if(!in_array($i['nid'],$nidHint)){
                        $nidHint[]=$i['nid'];
                        $norms[]=array('id'=>$i['nid'],'title'=>$i['norm']);
                    }
                }
            }
            $ret['brands']=$brands;
            $ret['norms']=$norms;

            $this->apiSuccess(array('data'=>$ret));

        }catch (Exception $ex){
            $this->apiError(50004,$ex->getMessage());
        }
    }

    private function _find_parent_top($i,$pid,&$catIds){
        $i=CategoryModel::get($i['pid']);
        !in_array($i['id'],$catIds) and $catIds[]=$i['id'];
        if($i['pid']==$pid)
            return $i;
        if($i['pid']<=0)
            return null;
        return $this->_find_parent_top($i,$pid,$catIds);
    }

    private function _find_level_top($i,$level,&$catIds){
        $i=CategoryModel::get($i['pid']);
        !in_array($i['id'],$catIds) and $catIds[]=$i['id'];
        if($i['level']==$level)
            return $i;
        return $this->_find_level_top($i,$level,$catIds);
    }

    /**
     * 根据获取的商家仓库的商品获取商品品牌接口
     * @param array|string $shopIds 商铺ID，可以通过getMerchantList获得
     * @param int $categoryId 分类ID
     * @return mixed
     * @author  stevin WangJiang
     * @Deprecated 统一到getDepotCategory中
     */
    public function getDepotBrand($shopIds,$categoryId){
        try{
            empty($shopIds) and E('参数shopIds不能为空');
            empty($categoryId) and E('参数categoryId不能为空');

            $shopIds=explode(',',$shopIds);
            list($bindNames, $bindValues) = build_sql_bind($shopIds);

            $bindValues[':categoryId']=$categoryId;

            $sql=M()->table('sq_merchant_depot_pro_category as a,sq_category as b,sq_category_brand_norms as c,sq_brand as d')
                ->where('a.shop_id in ('.implode(',',$bindNames).') and a.category_id=b.id and b.id=:categoryId and a.category_id=c.category_id and d.id=c.brand_id')
                ->field(['b.id','b.title','d.title as brand'])
                ->bind($bindValues);

            $this->apiSuccess(array('data'=>$sql->select()));

            //print_r($sql->getLastSql());

        }catch (Exception $ex){
            $this->apiError(50004,$ex->getMessage());
        }
    }

    /**
     * 根据获取的商家仓库的商品获取商品规格接口
     * @param
     * @author  stevin WangJiang
     * @Deprecated 统一到getDepotCategory中
     */
    public function getDepotNorms()
    {

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
        try{
        }catch (Exception $ex){
            $this->apiError(50005,$ex->getMessage());
        }
    }

    /**
     * 商品详细
     * @param int $id 上架商品ID
     * @return mixed
     * @author  stevin WangJiang
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