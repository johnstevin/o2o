<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Apimember\Controller;

use Common\Model\AppraiseModel;
use Common\Model\CategoryModel;
use Common\Model\MerchantDepotModel;
use Common\Model\MerchantShopModel;
use Common\Model\OrderModel;
use Common\Model\OrderVehicleModel;
use Think\Exception;
use Common\Model\ProductModel;
use Think\Model;

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
     * @param int $tagId 商家门店服务类型，可选''表示所有店铺，1-'商超'，2-'生鲜'，3-'送水'
     * @param int $type 商家类别，有1-超市，洗车
     * @param int $order 排序，1-按距离，2-按评价
     * @return json
     * ``` json
     *   {
     *       {
     *       "success": true,
     *       "error_code": 0,
     *       "data": [
     *           {
     *               "id": <商铺ID，shop id>,
     *               "title": "<商铺名称>",
     *               "distance": <当前距离，米>,
     *               "lnglat": [
     *                   <经度>,
     *                   <纬度>
     *               ]
     *           }...
     *       ]
     *   }
     * ```
     * 调用样例 GET apimber.php?s=/Product/getMerchantList/lat/29.58733/lng/106.524311/range/6000
     * ``` json
     *   {
     *       {
     *       "success": true,
     *       "error_code": 0,
     *       "data": [
     *           {
     *               "id": 4,
     *               "title": "石子山公园3.5",
     *               "distance": 3428.5691160108,
     *               "lnglat": [
     *                   106.494415,
     *                   29.603912
     *               ]
     *           },
     *           {
     *               "id": 6,
     *               "title": "重庆医科大学5.7",
     *               "distance": 5505.6004590446,
     *               "lnglat": [
     *                   106.518562,
     *                   29.53807
     *               ]
     *           }
     *       ]
     *   }
     * ```
     * @author  stevin WangJiang
     */
    public function getMerchantList($lat, $lng, $range = 100, $words = null, $wordsOp = 'or'
        , $tagId = 0, $type = null, $order = 1
        //,page,pageSize该函数不能采用分页，原因时客户端需要一下获得所有商铺
    )
    {
        try {
            $this->apiSuccess(['data' => (new MerchantShopModel())
                ->getNearby($lat, $lng, $range, $words, $wordsOp, $tagId, $type, $order)],'');
        } catch (\Exception $ex) {
            $this->apiError(50002, $ex->getMessage());
        }
    }

    /**
     * 查询商铺评价详细
     * @author WangJiang
     * @param $shopId
     * @param int $page
     * @param int $pageSize
     * @return json
     * ``` json
     * {
     *    "success": true,
     *    "error_code": 0,
     *    "data":
     *      [
     *          {
     *              "id": "<ID>",
     *              "order_id": "<相关订单ID>",
     *              "shop_id": "<商铺ID>",
     *              "user_id": "<评价客户ID>",
     *              "merchant_id": "<商家ID>",
     *              "content": "<内容>",
     *              "grade_1": "<打分1>",
     *              "grade_2": "<打分1>",
     *              "grade_3": "<打分1>",
     *              "status": "<状态，-1-关闭,1-已评价>",
     *              "update_time": "<修改时间>"
     *          }...
     *      ]
     * }
     * ```
     *调用样例 GET apimber.php?s=Product/getAppriseList/shopId/2
     */
    public function getAppriseList($shopId,$page = 1, $pageSize = 10){
        try {
            $pageSize > 50 and $pageSize = 50;

            $this->apiSuccess(['data' =>D('Appraise')
                ->join('join sq_member on sq_member.uid=sq_appraise.user_id')
                ->join('join sq_ucenter_member on sq_ucenter_member.id=sq_appraise.user_id')
                ->join('left join sq_picture on sq_picture.id=sq_ucenter_member.photo')
                ->where(['sq_appraise.shop_id'=>$shopId])
                ->page($page, $pageSize)
                ->order('sq_appraise.update_time')
                ->field([
                    'sq_appraise.id',
                    'ifnull(sq_appraise.content,\'\') as content',
                    'sq_appraise.grade_1',
                    'sq_appraise.grade_2',
                    'sq_appraise.grade_3',
                    '(sq_appraise.grade_1+sq_appraise.grade_2+sq_appraise.grade_3)/3 as grade',
                    'sq_appraise.update_time',
                    'if(anonymity=0,ifnull(sq_picture.path,\'\'),\'\') as picture_path',
                    'if(anonymity=0,sq_member.nickname,\'匿名用户\') as nickname',
                ])
                ->select()],'');
        } catch (\Exception $ex) {
            $this->apiError(50002, $ex->getMessage());
        }
    }

    /**
     * @ignore
     * @param $id
     */
    public function getMerchantDetail($id)
    {
        try {
            $this->apiSuccess(['data' => (new MerchantShopModel())->get($id)],'');
        } catch (Exception $ex) {
            $this->apiError(50003, $ex->getMessage());
        }
    }

    /**
     * 根据获取的商家仓库的商品获取商品分类接口
     * @param int $level 指定返回分类层级，为空则不限制分类
     * @param int $pid 指定上级分类，为空则忽略，如果设置了$level则忽略该参数
     * @param array|string $shopIds 商铺ID，可以通过getMerchantList获得，该参数可选
     * @param string|true|false $returnMore 返回相关品牌规格等附带信息，
     * @return json
    ``` json
     *   {
     *       "success": true,
     *       "error_code": 0,
     *       "data": {
     *       "categories": [ 分类
     *           {
     *               "id": "<分类ID>",
     *               "title": "<分类名称>",
     *               "pid": "<上级分类ID>",
     *               "level": "<分类层，例如：顶层分类为0>"
     *           }...
     *       ],
     *       "brands": [  品牌，returnMore=false时为空
     *           {
     *               "id": "<品牌ID>",
     *               "title": "<品牌名称>"
     *           }...
     *       ],
     *       "norms": [  规格，returnMore=false时为空
     *           {
     *               "id": "<规格ID>",
     *               "title": "<规格名称>"
     *           }...
     *           ]
     *       }
     *   }
     * ```
     * 调用样例 GET apimber.php?s=Product/getDepotCategory/shopIds/6,4,2/pid/2/returnMore/true
     * ``` json
     *   {
     *       "success": true,
     *       "error_code": 0,
     *       "data": {
     *       "categories": [
     *           {
     *               "id": "17",
     *               "title": "调料",
     *               "pid": "2",
     *               "level": "0"
     *           },
     *           {
     *               "id": "25",
     *              "title": "零食",
     *               "pid": "2",
     *               "level": "0"
     *           }
     *       ],
     *       "brands": [
     *           {
     *               "id": "91",
     *               "title": "恒顺"
     *           },
     *           {
     *               "id": "215",
     *               "title": "王致和"
     *           }
     *       ],
     *       "norms": [
     *           {
     *               "id": "1",
     *               "title": "瓶"
     *           },
     *           {
     *               "id": "19",
     *               "title": "壶"
     *           }
     *           ]
     *       }
     *   }
     * ```
     * @author  stevin WangJiang
     */
    public function getDepotCategory($level = null, $pid = 0, $shopIds = '', $returnMore = 'false')
    {
        //TODO:开发平台上测试的效率不理想，需要进一步优化，修改sq_merchant_depot_pro_category的数据，每次商品上架，该表必须保存指定分类以及他的所有上级节点
        try {
            empty($shopIds) and E('参数shopIds不能为空');
            $returnMore = $returnMore === 'true';
            $shopIds = explode(',', $shopIds);

            list($bindNames, $bindValues) = build_sql_bind($shopIds);

            $where='a.shop_id in (' . implode(',', $bindNames) . ') and a.category_id=b.id';

            if(!empty($pid)){
                $where.=' and b.pid=:pid';
                $bindValues[':pid']=$pid;
            }

            $sql = M()->table('sq_merchant_depot_pro_category as a,sq_category as b')
                ->where($where)
                ->field(['b.id', 'b.title', 'b.pid', 'b.level'])
                ->group('b.id')//->fetchSql()
                ->bind($bindValues);

            $data = $sql->select();

            //var_dump($data);die;

            //print_r($sql->getLastSql());die;

            if ($returnMore and !empty($data)) {

                if(empty($catIds)){
                    foreach($data as $i){
                        $catIds[]=$i['id'];
                    }
                }

                //print_r($catIds);
                list($bindNames, $bindValues) = build_sql_bind($catIds);
                $sql = M()->table('sq_category_brand_norms as l')
                    ->field(['sq_brand.id as bid', 'sq_brand.title as brand', 'sq_norms.id as nid', 'sq_norms.title as norm','l.category_id as cid'])
                    ->join('LEFT JOIN sq_norms on sq_norms.id=l.norms_id')
                    ->join('left JOIN sq_brand on sq_brand.id=l.brand_id')
                    ->where('l.norms_id<>0 and l.category_id in (' . implode(',', $bindNames) . ')')
                    ->bind($bindValues);

                $temp = $sql->select();

                $cates=[];
                foreach ($catIds as $i) {
                    $cid=$i;
                    if(!array_key_exists($cid,$cates)){
                        $cates[$cid]['norms']=[];
                        $cates[$cid]['brands']=[];
                    }
                    $brandHint=[];
                    foreach($temp as $cbn){
                        if($cid==$cbn['cid']){
                            $cates[$cid]['norms'][]=['id' => $cbn['nid'], 'title' => $cbn['norm'],'bid'=>$cbn['bid']];
                            if(!in_array($cbn['bid'],$brandHint)){
                                $brandHint[]=$cbn['bid'];
                                $cates[$cid]['brands'][]=['id' => $cbn['bid'], 'title' => $cbn['brand']];
                            }
                        }
                    }
                }

                foreach($cates as &$v){
                    $brands=&$v['brands'];
                    $norms=$v['norms'];
                    foreach($brands as &$b){
                        foreach($norms as $n){
                            if($n['bid']==$b['id']){
                                $b['norms'][]=$n;
                            }
                        }
                    }
                    unset($v['norms']);
                }

                foreach($data as &$i){
                    foreach ($cates as $k=>$v) {
                        if ($k==$i['id']) {
                            $i['brands'][] = $v['brands'];
                        }
                    }
                }
            }
            $ret['categories']=list_to_tree($data,'id', 'pid', '_child', $root = $pid);

            $this->apiSuccess(['data' => $ret],'');

        } catch (Exception $ex) {
            $this->apiError(50004, $ex->getMessage());
        }
    }

    private function _find_parent_top($i, $pid, &$catIds)
    {
        $i = CategoryModel::get($i['pid']);
        !in_array($i['id'], $catIds) and $catIds[] = $i['id'];
        if ($i['pid'] == $pid)
            return $i;
        if ($i['pid'] <= 0)
            return null;
        return $this->_find_parent_top($i, $pid, $catIds);
    }

    private function _find_level_top($i, $level, &$catIds)
    {
        $i = CategoryModel::get($i['pid']);
        !in_array($i['id'], $catIds) and $catIds[] = $i['id'];
        if ($i['level'] == $level)
            return $i;
        return $this->_find_level_top($i, $level, $catIds);
    }

    /**
     * 根据获取的商家仓库的商品获取商品品牌接口
     * @param int $categoryId 分类ID
     * @return mixed
     * @author  stevin WangJiang
     */
    public function getBrand($categoryId)
    {
        try {
            empty($categoryId) and E('参数categoryId不能为空');

            $sql = M()->table('sq_category_brand_norms as l')
                ->join('LEFT JOIN sq_norms on sq_norms.id=l.norms_id')
                ->join('left JOIN sq_brand on sq_brand.id=l.brand_id')
                ->field(['sq_brand.id as bid', 'sq_brand.title as brand', 'sq_norms.id as nid', 'sq_norms.title as norm'])
                ->where('l.category_id=:cid')
                ->bind([':cid'=>$categoryId]);

            $data = $sql->select();

            $brandHint=[];
            foreach($data as $i){
                if(!array_key_exists($i['bid'],$brandHint)){
                    $brandHint[$i['bid']]=['id'=>$i['bid'],'title'=>$i['brand'],'norms'=>[]];
                }
            }

            foreach($data as $i){
                if(!is_null($i['nid']) and !array_key_exists($i['nid'],$brandHint[$i['bid']]['norms'])){
                    $brandHint[$i['bid']]['norms'][]=['id'=>$i['nid'],'title'=>$i['norm']];
                }
            }

            $ret=[];
            foreach($brandHint as $i){
                $ret[]=$i;
            }

            $this->apiSuccess(['data' => $ret],'');

            //print_r($sql->getLastSql());

        } catch (Exception $ex) {
            $this->apiError(50004, $ex->getMessage());
        }
    }

    /**
     * @ignore
     * 根据获取的商家仓库的商品获取商品规格接口
     * @param
     * @author  stevin WangJiang
     * @deprecated 统一到getDepotCategory中
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
     * @param int $page 指定页号
     * @param int|null $status 状态
     * @return json
    ``` json
     *{
     *    "success": true,
     *    "error_code": 0,
     *    "data": [
     *        {
     *            "id": "<当选商铺库存ID>",
     *            "product_id": "<商品ID>",
     *            "product": "<商品名称>",
     *            "price": <当选商铺定价>,
     *            "shop_id": "<当选商铺ID>",
     *            "shop": "<当选商铺名称>",
     *            "alters": [ 其他销售该商品的商铺
     *            {
     *                "id": "<商铺库存ID>",
     *                "price": <商铺定价>,
     *                "shop_id": "<商铺ID>",
     *                "shop": "<商铺名称>"
     *            }...
     *            ]
     *        }...
     *    ]
     *    "page":<当前页号，用这个来前后翻动>
     *}     ```
     * 调用样例  GET apimber.php?s=/Product/getProductList/shopIds/2,4,6
     * ``` json
     *{
     *    "success": true,
     *    "error_code": 0,
     *    "data": [
     *        {
     *            "id": "38636",
     *            "product_id": "1",
     *            "product": "妮维雅凝水活才保湿眼霜",
     *            "price": 91.59,
     *            "shop_id": "2",
     *            "shop": "磁器口6.3",
     *            "alters": [ ]
     *        },
     *        {
     *            "id": "38640",
     *            "product_id": "2",
     *            "product": "爱得利十字孔家居百货05奶嘴",
     *            "price": 2.14,
     *            "shop_id": "6",
     *            "shop": "重庆医科大学5.7",
     *            "alters": [ ]
     *        },
     *        {
     *            "id": "38642",
     *            "product_id": "3",
     *            "product": "爱得利旋转把柄A17大奶瓶",
     *            "price": 16.71,
     *            "shop_id": "4",
     *            "shop": "石子山公园3.5",
     *            "alters": [
     *            {
     *                "id": "38644",
     *                "price": 17.8,
     *                "shop_id": "6",
     *                "shop": "重庆医科大学5.7"
     *            }
     *            ]
     *        },
     *        {
     *            "id": "38652",
     *            "product_id": "5",
     *            "product": "爱得利全自动奶瓶",
     *            "price": 20.63,
     *            "shop_id": "6",
     *            "shop": "重庆医科大学5.7",
     *            "alters": [
     *                {
     *                    "id": "38648",
     *                    "price": 23.3,
     *                    "shop_id": "2",
     *                    "shop": "磁器口6.3"
     *                },
     *                {
     *                    "id": "38650",
     *                    "price": 22.76,
     *                    "shop_id": "4",
     *                    "shop": "石子山公园3.5"
     *                }
     *            ]
     *        },
     *        {
     *            "id": "38655",
     *            "product_id": "7",
     *            "product": "爱得利360度全自动G02奶瓶双吸管",
     *            "price": 6.98,
     *            "shop_id": "2",
     *            "shop": "磁器口6.3",
     *            "alters": [ ]
     *        },
     *        {
     *            "id": "38658",
     *            "product_id": "8",
     *            "product": "爱得利安抚C01奶嘴",
     *            "price": 3.59,
     *            "shop_id": "2",
     *            "shop": "磁器口6.3",
     *            "alters": [ ]
     *        },
     *        {
     *            "id": "38660",
     *            "product_id": "9",
     *            "product": "爱得利F01奶瓶刷",
     *            "price": 4.59,
     *            "shop_id": "2",
     *           "shop": "磁器口6.3",
     *            "alters": [ ]
     *        }
     *    ]
     *}
     * ```
     * @author  stevin WangJiang
     */
    public function getProductList($shopIds = null, $categoryId = null, $brandId = null, $normId = null, $title = null
        , $priceMin = null, $priceMax = null
        , $returnAlters = 'true',$page = 1,$pageSize=30)
    {
        try {
            empty($shopIds) and E('参数shopIds不能为空');
            $pageSize > 50 and $pageSize = 50;
            $page--;
            $page *= $pageSize;
            $returnAlters = $returnAlters === 'true';
            $shopIds = explode(',', $shopIds);

            $this->apiSuccess(['data'=>(new MerchantDepotModel())->getProductList($shopIds, $categoryId, $brandId, $normId, $title
                , $priceMin, $priceMax
                , $returnAlters,$page, $pageSize),'page'=>$page+1],'');

        } catch (Exception $ex) {
            $this->apiError(50005, $ex->getMessage());
        }
    }

    /**
     * 查询指定商品商品，按照商铺带商品的方式返回
     * @author  WangJiang
     * @param null $shopIds
     * @param null $categoryId
     * @param null $title
     * @param null $priceMin
     * @param null $priceMax
     */
    public function getShopProductList($shopIds = null, $categoryId = null, $title = null
        , $priceMin = null, $priceMax = null){
        try {
            empty($shopIds) and E('参数shopIds不能为空');
            $shopIds = explode(',', $shopIds);

            $this->apiSuccess(['data'=>(new MerchantShopModel())->getProductList($shopIds, $categoryId,  $title
                , $priceMin, $priceMax)],'');

        } catch (Exception $ex) {
            $this->apiError(50005, $ex->getMessage());
        }
    }

    /**
     * @ignore
     * 商品详细
     * @param int $id 上架商品ID
     * @return mixed
     * @author  stevin WangJiang
     */
    public function getProductDetail($id)
    {
        try {
            $this->apiSuccess(['data' => ProductModel::get($id)],'');
        } catch (Exception $ex) {
            $this->apiError(50006, $ex->getMessage());
        }
    }

    /**
     * 获取系统的商品列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param null|string $categoryIds 分类ID
     * @param null|string|array $normsIds 规格ID
     * @param null|int $brandId 品牌ID
     * @param null|string $title 商品标题（模糊查询）
     * @param int $pagesize 页面大小
     * @param int|null $status 状态
     * @param bool $getCategorys 是否关联查询分类
     * @param bool $getBrand 是否关联查询品牌
     * @return string
     * ``` JSON
     * {
     *     "success": true,
     *     "error_code": 0,
     *     "total": 6301,
     *     "data": [
     *     {
     *         "id": "10",
     *         "title": "爱得利PC自动03414小奶瓶",
     *         "brand_id": "37",
     *         "norms_id": "2",
     *         "price": "16.50",
     *         "detail": null,
     *         "add_time": "0",
     *         "add_ip": "0",
     *         "edit_time": "0",
     *         "edit_ip": "0",
     *         "status": "1",
     *         "number": "4711602134142",
     *         "description": "",
     *         "picture": "/Uploads/Pictures/Products/2015/6/18/image.jpg",
     *         "_brand": {
     *             "name": "爱得利",
     *             "logo": null
     *         },
     *         "_categorys": [
     *         {
     *             "id": "1",
     *             "title": "洗涤日化",
     *             "list_row": "10",
     *             "description": "",
     *             "icon": null,
     *             "level": "0"
     *         },
     *         {
     *             "id": "2",
     *             "title": "洗涤",
     *             "list_row": "10",
     *             "description": "",
     *             "icon": null,
     *             "level": "1"
     *         }
     *         ]
     *     }
     *     ]
     * }
     * ```
     */
    public function lists($categoryIds = null, $brandId = null, $normsIds = null, $title = null, $pagesize = 10, $status = ProductModel::STATUS_ACTIVE, $getCategorys = false, $getBrand = false)
    {
        $this->apiSuccess(ProductModel::getLists($categoryIds, $brandId, $normsIds, $status, $title, $pagesize, $getCategorys, $getBrand));
    }

    /**
     * @ignore
     * 根据ID查找单条记录
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id
     * @param bool|string|array $fields 要查询的字段
     * @return string
     * ``` JSON
     * {
     *      "success": true,
     *      "error_code": 0,
     *      "data": {
     *           "id": "1",
     *           "title": "妮维雅凝水活才保湿眼霜",
     *           "brand_id": "36",
     *           "price": "88.00",
     *           "detail": null,
     *           "add_time": "0",
     *           "add_ip": "0",
     *           "edit_time": "0",
     *           "status": "1"
     *       }
     * }
     * ```
     */
    public function find($id, $fields = true)
    {
        $this->apiSuccess(['data' => ProductModel::get($id, $fields)]);
    }

    /**
     * 应急用，暂时替代小聂的版本
     * @author WangJiang
     * @param $page
     * @param $pageSize
     * @return array
     */
    private function _get_order_list($uid,$status,$payStatus,$page,$pageSize){

        $model=new OrderModel();
        $where['sq_order.user_id']=$uid;

        if(!is_null($status)){
            if(is_array($status))
                $where['sq_order.status']=['in',$status];
            else
                $where['sq_order.status']=$status;
        }
        if(!is_null($payStatus))
            $where['sq_order.pay_status']=$payStatus;

        $data=$model
            ->field([
                'sq_order.id',
                'sq_order.order_code',
                'sq_order.pid',
                'sq_order.user_id',
                'sq_order.shop_id',
                'sq_order.price',
                'sq_order.status',
                'sq_order.pay_status',
                'sq_order.mobile',
                'sq_order.address',
                'sq_order.consignee',
                'sq_order.delivery_price',
                'sq_order.delivery_mode',
                'sq_order.delivery_time',
                'sq_order.update_time',
                'sq_order.add_time',
                'sq_order.pay_mode',
                'sq_appraise.id IS NOT NULL as appraised',
                'ifnull(sq_merchant_shop.phone_number,\'\') as shop_phone_number',
                'ifnull(sq_merchant_shop.title,\'\') as shop_title',
                'ifnull(sq_picture.path,\'\') as shop_picture'])
            ->join('left join sq_merchant_shop on sq_merchant_shop.id=shop_id')
            ->join('left join sq_picture on sq_picture.id=sq_merchant_shop.picture')
            ->join('left join sq_appraise on sq_appraise.order_id=sq_order.id')
            ->where($where)
            ->order('update_time desc,add_time desc')
            ->group('sq_order.id')
            ->page($page,$pageSize)
            //->fetchSql()
            ->select();

        //dump($data);die;

        foreach($data as $k=>&$order){
            $items=D('OrderItem')
                ->alias('oi')
                ->field(['oi.*',
                    'sq_product.title',
                    'ifnull(sq_picture.path,\'\') as picture',
                'ifnull(sq_norms.title,\'\') norms'])
                ->where(['order_id'=>$order['id']])
                ->join('join sq_product on sq_product.id=product_id')
                ->join('left join sq_picture on sq_picture.id=picture')
                ->join('left join sq_norms on sq_norms.id=sq_product.norms_id')
                ->select();
            $order['_products']=$items;
            if(empty($items))
                unset($data[$k]);
        }

        return ['data'=>$data];
    }

    /**
     * 返回所有用户订单，需要accesstoken
     * @author WangJiang
     * @param null $status 状态查询，0-待付款，1-待确认，2-已完成，不写-全部
     * @param int $page
     * @param int $pageSize
     */
    public function getOrderList($status=null,$page=1,$pageSize=10){

        //0-未分配，1-已分配，2-已接单，3-处理中，4-处理完，5-订单结束，6订单取消
        //0-取消的订单,1-等待商家确定,2-用户确定（商家的修改），3-正在配送,4-已经完成，5-申请退款,6-退款完成

        $orderStatuees=[['status'=>[OrderModel::STATUS_DELIVERY,OrderModel::STATUS_COMPLETE],'payStatus'=>0]
            ,['status'=>OrderModel::STATUS_USER_CONFIRM,'payStatus'=>null]
            ,['status'=>OrderModel::STATUS_COMPLETE,'payStatus'=>1]];

        $orderVehStatuses=[['status'=>OrderVehicleModel::STATUS_DONE,'payStatus'=>0]
            ,['status'=>-2,'payStatus'=>null]
            ,['status'=>OrderVehicleModel::STATUS_CLOSED,'payStatus'=>1]];

        $uid=$this->getUserId();
        $_GET['p']=$page;

        $statusOrder=(is_null($status) or $status=='' )? null :$orderStatuees[$status];
        $statusOrderVeh=(is_null($status) or $status=='')? null :$orderVehStatuses[$status];

        //var_dump($statusOrder);
        //var_dump($statusOrderVeh);

        $orders=$this->_get_order_list($uid, $statusOrder['status'], $statusOrder['payStatus'], $page,$pageSize);//(new OrderModel())->getLists(null, $uid, $statusOrder['status'], $statusOrder['payStatus'], null, true, false, true, $pageSize);
        $orders_veh=(new OrderVehicleModel())->getUserList($uid,$statusOrderVeh['status'], $statusOrderVeh['payStatus'],null,$page,$pageSize);

        $iter_order=(new \ArrayObject($orders['data']))->getIterator();
        $iter_order_veh=(new \ArrayObject($orders_veh))->getIterator();

        $data=[];
        while($iter_order->valid() and $iter_order_veh->valid()){
            $order=$iter_order->current();
            $order_veh=$iter_order_veh->current();

            if(empty($order) or empty($order_veh))
                break;
            $order['order_type']='shop';
            $order_veh['order_type']='vehicle';
            if($order['update_time']<$order_veh['update_time']){
                $data[]=$order;
                $data[]=$order_veh;
            }else{
                $data[]=$order_veh;
                $data[]=$order;
            }
            $iter_order->next();
            $iter_order_veh->next();
        }

        while($iter_order->valid()){

            $order=$iter_order->current();
            $order['order_type']='shop';
            $data[]=$order;
            $iter_order->next();
        }

        while($iter_order_veh->valid()){
            $order_veh=$iter_order_veh->current();
            $order_veh['order_type']='vehicle';
            $data[]=$order_veh;
            $iter_order_veh->next();
        }
        $this->apiSuccess(['data'=>$data],'');
    }


    /**
     * 评价，POST参数
     * <pre>
     * 参数
     * orderId 订单ID，必须
     * grade1 评分1
     * grade2 评分2
     * grade3 评分3
     * content 评价
     * anonymity 是否匿名　０－不是，１－是
     * </pre>
     * @author WangJiang
     * @return json
     */
    public function appraise(){
        try{
            if(!IS_POST)
                E('非法调用，请用POST调用');
            $oid=I('post.orderId');
            $grade1=I('post.grade1',0);
            $grade2=I('post.grade2',0);
            $grade3=I('post.grade3',0);
            $content=I('post.content');
            $anonymity=I('post.anonymity',0);
            if(empty($content))
                $content='该用户很深沉，什么也没说。';

            $m=new OrderModel();
            $data=$m->find($oid);

            AppraiseModel::addAppraise($oid,$data['shop_id'],$this->getUserId(),null,$content,$grade1,$grade2,$grade3,$anonymity);

            $this->apiSuccess(['data'=>[]],'成功');
        }catch (\Exception $ex) {
            $this->apiError(51023, $ex->getMessage());
        }
    }
}
