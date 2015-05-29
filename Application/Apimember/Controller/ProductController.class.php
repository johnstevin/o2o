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
                ->getNearby($lat, $lng, $range,$words,$wordsOp,$type)));
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
     * @param string|true|false $returnMore 返回相关品牌规格等附带信息，
     * @return mixed
     * @author  stevin WangJiang
     */
    public function getDepotCategory($level=null,$pid=null,$shopIds='',$returnMore='false'){
        //TODO:开发平台上测试的效率不理想，需要进一步优化，修改sq_merchant_depot_pro_category的数据，每次商品上架，该表必须保存指定分类以及他的所有上级节点
        try{
            empty($shopIds) and E('参数shopIds不能为空');

            $returnMore=$returnMore==='true';

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
            if($returnMore and !empty($catIds)){
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

                $ret['brands']=$brands;
                $ret['norms']=$norms;
            }


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
     * 查询商家商品
     * @param array|string $shopIds 商铺ID，多个用','隔开
     * @param null|string $categoryId 分类ID
     * @param null|int $brandId 品牌ID
     * @param null|int $normId 规格ID
     * @param null|string $title 商品标题（模糊查询）
     * @param string $priceMin 商品售价下限
     * @param string $priceMax 商品售价上限
     * @param string|true|false $returnAlters 是否返回'alters'属性
     * @param int $pageSize 页面大小
     * @param int|null $status 状态
     * @return mixed
     * @author  stevin WangJiang
     */
    public function getProductList($shopIds=null,$categoryId = null, $brandId = null,$normId=null, $title = null
        ,$priceMin=null,$priceMax=null
        ,$returnAlters='true',$page = 0, $pageSize = 10){
        try{
            empty($shopIds) and E('参数shopIds不能为空');
            $pageSize > 50 and $pageSize=50;
            $page*=$pageSize;

            $returnAlters=$returnAlters==='true';

            $shopIds=explode(',',$shopIds);
            list($shopBindNames, $bindValues) = build_sql_bind($shopIds);

            $sql=M('MerchantDepot')
                ->join('INNER JOIN sq_merchant_shop as shop on shop.id in ('.implode(',',$shopBindNames).') and shop.id=sq_merchant_depot.shop_id');

            $sql_pro='INNER JOIN sq_product as pro on pro.id=sq_merchant_depot.product_id';

            if(!empty($title)){
                $sql_pro.=' and pro.title like :title';
                $bindValues[':title']='%'.$title.'%';
            }

            $where='';
            if(!is_null($priceMin)){
                $where.='sq_merchant_depot.price>:priceMin';
                $bindValues[':priceMin']=$priceMin;
            }

            if(!is_null($priceMax)){
                if(!empty($where))
                    $where=' and ';
                $where.='sq_merchant_depot.price<:priceMax';
                $bindValues[':priceMax']=$priceMax;
            }

            if(!empty($brandId)){
                //$sql->join('INNER JOIN sq_product as pro on pro.id=sq_merchant_depot.product_id and pro.brand_id=:brandId');
                $sql_pro.=' and pro.brand_id=:brandId';
                $bindValues[':brandId']=$brandId;
            }

            if(!empty($normId)){
                //$sql->join('INNER JOIN sq_product as pro on pro.id=sq_merchant_depot.product_id and pro.brand_id=:brandId');
                $sql_pro.=' and pro.norms_id=:normId';
                $bindValues[':normId']=$normId;
            }

            $sql->join($sql_pro);

            if(!empty($categoryId)) {
                $sql->join('INNER JOIN sq_product_category as pc on pc.category_id=:cateId AND pc.product_id=pro.id');
                $bindValues[':cateId']=$categoryId;
            }

            $sql->field(['sq_merchant_depot.id','pro.id as product_id'
                ,'pro.title as product','sq_merchant_depot.price'
                ,'shop.id as shop_id','shop.title as shop']);

            if(!empty($where))
                $sql->where($where);

            $sql->bind($bindValues)->limit($page,$pageSize);

            $data=$sql->select();

            //print_r($sql->getLastSql());

            $products=[];
            $depots=[];
            foreach($data as $i){
                $i['price'] = floatval($i['price']);
                $pid=$i['product_id'];
                if(!isset($products[$pid]))
                    $products[$pid]=$i;

                if($returnAlters)
                    $depots[$pid][]=$i;

                if($products[$pid]['price']>$i['price'])
                    $products[$pid]=$i;
            }

            $ret=[];
            foreach($products as $k=>$product){
                if($returnAlters){
                    $depot=$depots[$k];
                    $alters=[];
                    foreach($depot as $i){
                        if($product['id']!==$i['id'])
                            $alters[]=array('id'=>$i['id'],'price'=>$i['price'],'shop_id'=>$i['shop_id'],'shop'=>$i['shop']);
                    }
                    $product['alters']=$alters;
                }

                $ret[]=$product;
            }

            $this->apiSuccess(array('data'=>$ret));

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
    public function getProductDetail($id)
    {
        try{
            $this->apiSuccess(['data' => ProductModel::get($id)]);
        }catch (Exception $ex){
            $this->apiError(50006,$ex->getMessage());
        }
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