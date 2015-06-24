<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 5/28/15
 * Time: 4:05 PM
 */
namespace Admin\Model;

use \Think\Model;

class ProductModel extends Model
{

    const BRAND = 'brand'; // 品牌关系表
    const NORMS = 'norms'; // 规格关系表
    const CATEGORY_BRAND_NORMS = 'category_brand_norms';//商品品牌规格关系表
    const PRODUCT_CATEGORY = 'product_category';// 商品分类关联表
    const CATEGORY = 'category';//分类表
    /**
     * 自动验证
     * @var array
     */
    protected $_validata = array(

        array('number', '', '该商品的已经存在', self::MUST_VALIDATE, 'unique', self::MODEL_INSERT),
        array('title', 'require', '必须设置商品标题', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        array('price', 'currency', '商品价格必须是货币形式', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
    );

    /**
     * 自动验证
     * @var array
     */
    protected $_auto = array(
        array('add_time', NOW_TIME, self::MODEL_INSERT),
        array('add_ip', 'get_client_ip', self::MODEL_INSERT, 'function', 1),
        array('edit_time', NOW_TIME),
    );

    /**
     * 关联模型
     * @var array
     */
    protected $_link = array(
        'brand' => array(
            'class_name' => 'Brand',
            'parent_key' => 'brand_id',
            'mapping_name' => '_brand',
            'mapping_order' => 'sort desc',
        ),
        'norms' => array(
            'class_name' => 'norms',
            'parent_key' => 'norms_id',
            'mapping_name' => '_norms',
        ),
    );

    /**
     * 获取商品列表
     */
    public function lists($where, $order, $field)
    {

        return $this->field($field)->select();
    }

    /**
     * 获取用户组详细信息
     * @param  milit $id 分类ID或标识
     * @param  boolean $field 查询字段
     * @return array     分类信息
     */
    public function info($id, $field = true)
    {
        /* 获取分类信息 */
        $map = array();
        if (is_numeric($id)) { //通过ID查询
            $map['id'] = $id;
        } else { //通过标识查询
            $map['name'] = $id;
        }
        $Product = $this->field($field)->where($map)->find();
        $Product['_picture'] = M('Picture')->where(array('id' => $Product['picture']))->find();
        return $Product;
    }

    /**
     * 更新信息
     * @return boolean 更新状态
     */
    public function update()
    {

        $data = $this->create();
        if (!$data) { //数据对象创建错误
            return false;
        }

        /* 添加或更新数据 */
        if (empty($data['id'])) {
            $res = $this->add();
        } else {
            $res = $this->save();
        }

        return $res;
    }

    /**
     * 返回下一级数据
     * @param array $pid 上级的id数组
     * @param array $where 查询条件
     * @return mixed
     */
    public function showChild($pid = array(0), $where = array())
    {
        $pid = is_array($pid) ? implode(',', $pid) : trim($pid, ',');
        $map = array('status' => 1, 'pid' => array('in', $pid));
        $map = array_merge($map, $where);
        return $this->where($map)->select();
    }

    /**
     * @param array $category_id 分类id（数组）
     * @param array $where $where 查询条件
     * @return mixed
     */
    public function getCategoryChild($category_id = array(0), $where = array())
    {
        $category_id = is_array($category_id) ? implode(',', $category_id) : trim($category_id, ',');
        $map = array('q.pid' => array('in', $category_id), 'q.status' => '1');
        $map = array_merge($map, $where);
        $prefix = C('DB_PREFIX');
        $result = array();
        $BrandNorms = M()
            ->field('q.pid,q.id,q.title')
            ->table($prefix . self::CATEGORY . ' q')
            //->join ($prefix.self::CATEGORY_BRAND_NORMS." a on q.id = a.category_id" )
//            ->join ($prefix.self::BRAND." g on a.brand_id=g.id")
//            ->join ($prefix.self::NORMS." m on a.norms_id=m.id")
            ->where($map)
            ->select();
        $result['data'] = $BrandNorms;
        $result['other'] = $this->getBrand($category_id);
        return $result;
    }

    public function getBrand($category_id = array(0))
    {
        $category_id = is_array($category_id) ? implode(',', $category_id) : trim($category_id, ',');
        $prefix = C('DB_PREFIX');
        $result = array();
        $result['Brand'] = M()
            ->field('a.brand_id,g.title brand_title')
            ->table($prefix . self::CATEGORY_BRAND_NORMS . ' a')
            ->join($prefix . self::BRAND . " g on a.brand_id=g.id")
            ->where(array('a.category_id' => array('in', $category_id), 'g.status' => '1'))
            ->distinct(true)
            ->select();
//        $result['Norms']=M()
//            ->field('a.norms_id,m.title norms_title')
//            ->table($prefix.self::CATEGORY_BRAND_NORMS.' a')
//            ->join ($prefix.self::NORMS." m on a.norms_id=m.id")
//            ->where(array('a.category_id'=> array('in', $category_id)))
//            ->distinct(true)
//            ->select();
        return $result;
    }

    public function getNorms($category_id = array(0), $brand)
    {
        $category_id = is_array($category_id) ? implode(',', $category_id) : trim($category_id, ',');
        $prefix = C('DB_PREFIX');
        $result = M()
            ->field('a.norms_id,m.title norms_title')
            ->table($prefix . self::CATEGORY_BRAND_NORMS . ' a')
            ->join($prefix . self::NORMS . " m on a.norms_id=m.id")
            ->where(array('a.category_id' => array('in', $category_id), 'a.brand_id' => array('eq', $brand)))
            ->distinct(true)
            ->select();
        return $result;
    }

    /**
     * 保存分类
     */
    public function saveCategory($result)
    {

        /*接收分类*/
        $category_id = 0;
        for ($i = 3; $i > 0; $i--) {
            $temp = I('category' . $i);
            if ($temp != 0 && !empty($temp)) {
                $category_id = $temp;
                break;
            }
        }
        if ($category_id == 0 || empty($category_id)) {
            $this->error = '请选择分类';
            return false;
        }
        /*保存分类*/
        $result = is_array($result) ? implode(',', $result) : trim($result, ',');
        $category_id = is_array($category_id) ? $category_id : explode(',', trim($category_id, ','));
        $Product = M(self::PRODUCT_CATEGORY);
        //先删除旧数据
        $del = $Product->where(array('product_id' => array('in', $result)))->delete();
        $result_arr = explode(',', $result);
        $add = array();
        if ($del !== false) {
            foreach ($result_arr as $u) {
                //判断用户id是否合法
                if (M('Product')->getFieldByUid($u, 'id') == false) {
                    $this->error = "编号为{$u}的商品不存在！";
                    return false;
                }
                foreach ($category_id as $g) {
                    if (is_numeric($u) && is_numeric($g)) {
                        $add[] = array('category_id' => $g, 'product_id' => $u);
                    }
                }
            }
            $Product->addAll($add);
        }
        if ($Product->getDbError()) {
            if (count($result_arr) == 1 && count($category_id) == 1) {
                //单个添加时定制错误提示
                $this->error = "不能重复添加";
            }
            return false;
        } else {
            return true;
        }

    }

    /**
     * 获取商品所属分类信息
     * @param  产品id
     * @return array
     */
    public function CategoryInfo($id)
    {
        $category = array();
        $product_category = M(self::PRODUCT_CATEGORY)->where(array('product_id' => $id))->select();
        $category_arr = array();
        foreach ($product_category as &$v) {
            $category_arr[] = $v['category_id'];
        }
        //TODO  待优化
        $category['level2'] = array();
        $category['level3'] = array();
        $Category = M('Category');
        $info = $Category->field('id,level,pid')->where(array('id' => array('in', $category_arr)))->select();
        $pids = array();
        $ids = array();
        foreach ($info as &$k) {
            $pids[] = $k['pid'];
            $ids[] = $k['id'];
        }
        $pids_str = is_array($pids) ? implode(',', $pids) : trim($pids, ',');
        $ids_str = is_array($ids) ? implode(',', $ids) : trim($ids, ',');
        if ($info[0]['level'] == 0) {
            $category['level2'] = $Category->field('id,title')->where(array('pid' => array('in', $ids_str)))->select();
            $lthreepids = array();
            foreach ($category['level2'] as &$k) {
                $lthreepids[] = $k['id'];
            }
            $lthreepids = is_array($lthreepids) ? implode(',', $lthreepids) : trim($lthreepids, ',');
            if (!empty($lthreepids)) {
                $category['level3'] = $Category->field('id,title')->where(array('pid' => array('in', $lthreepids)))->select();
            } else {
                $category['level3'] = array();
            }


        }
        if ($info[0]['level'] == 1) {
            $pids_arrs = is_array($pids_str) ? $pids_str : explode(',', trim($pids_str, ','));
            $category_arr = array_merge($category_arr, $pids_arrs);
            $category['level2'] = $Category->field('id,title')->where(array('pid' => array('in', $pids_str)))->select();
            $lthreepids = array();
            foreach ($category['level2'] as &$k) {
                $lthreepids[] = $k['id'];
            }
            $lthreepids = is_array($lthreepids) ? implode(',', $lthreepids) : trim($lthreepids, ',');
            $category['level3'] = $Category->field('id,title')->where(array('pid' => array('in', $lthreepids)))->select();

        }
        if ($info[0]['level'] == 2) {
            $pid = $Category->field('id,pid')->where(array('id' => array('in', $pids_str)))->select();
            $two = array();
            foreach ($pid as &$k) {
                $two[] = $k['pid'];
                $category_arr[] = $k['id'];
            }
            $twos = is_array($two) ? implode(',', $two) : trim($two, ',');
            $one = $Category->field('id,title')->where(array('id' => array('in', $twos)))->select();
            foreach ($one as &$k) {
                $category_arr[] = $k['id'];
            }
            $category['level2'] = $Category->field('id,title')->where(array('pid' => array('in', $twos)))->select();
            //print_r($category['level2']);die;
            $category['level3'] = $Category->field('id,title')->where(array('pid' => array('in', $pids_str)))->select();

        }
        $category_str = is_array($category_arr) ? implode(',', $category_arr) : trim($category_arr, ',');
        $category['selected'] = $category_str;
        return $category;
    }

    /**
     * 审核商品
     * @param $product_id 商品id
     * @return bool
     */
    public function auditProduct($product_id)
    {
        //检查商品是否存在
        $info = $this->info($product_id);
        if (empty($info)) {
            $this->error = '没有此商品';
            return false;
        }

        $info['status'] = 1;
        if (false !== $this->save($info)) {
            D('MerchantDepot')->where(array('product_id' => $product_id))->setField('status', 1);
            return true;
        } else {
            $this->error = '审核失败 ';
            return false;
        }
    }


    /**
     * @param int $product_id 产品id
     * @return bool|void
     */
    public function saveUnPassReason($product_id)
    {
        //修改状态
        $data['id'] = $product_id;
        $data['message'] = I('reason');
        $data['status'] = 3;
        return $this->save($data);
    }
}