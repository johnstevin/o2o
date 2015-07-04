<?php
namespace Admin\Model;

use Think\Model;

class CategoryModel extends Model
{

    const CATEGORY_BRAND_NORMS = 'category_brand_norms'; // 关系表表名

    /**
     * *获取分类详细信息
     * @param int $id 分类id
     * @param bool $field 查询字段
     * @return mixed
     */
    public function info($id, $field = true)
    {
        $map = array();
        if (is_numeric($id)) {//通过id来查询
            $map['id'] = $id;
        } else {//通过标题来查询
            $map['title'] = $id;
        }
        return $this->field($field)->where($map)->find();
    }

    /**
     * *获取分类树，指定分类则返回指定分类及其子分类，不指定则返回所有分类树
     * @param int $id 分类ID
     * @param bool $field 查询字段
     * @return array|mixed 分类树
     */
    public function getTree($id = 0, $field = true)
    {
        //获取当前分类信息
        if ($id) {
            $info = $this->info($id);
            $id = $info['id'];
        }
        //获取所有分类
        $map = array('status' => array('gt', -1));
        $list = $this->field($field)->where($map)->order('sort')->select();
        $list = int_to_string($list, array('status' => array(1 => '正常', -1 => '删除', 0 => '禁用')));
        $list = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = $id);
        //获取返回数据
        if (isset($info)) {//指定分类则返回当前分类及其子分类
            $info['child'] = $list;
        } else {//否则则返回所有分类
            $info = $list;
        }
        return $info;
    }

    /**
     * 更新分类信息
     * @return boolean 更新状态
     * @author liu hui
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
     * 分类关联品牌
     * @param $id
     * @param $brands
     * @return bool
     * @author liu hui
     */
    public function addCategoryBrand($id, $brands)
    {

        $id = is_array($id) ? implode(',', $id) : trim($id, ',');
        $brands = is_array($brands) ? $brands : explode(',', trim($brands, ','));

        $BranNorms = M(self::CATEGORY_BRAND_NORMS);

        //先删除旧数据
        $del = $BranNorms->where(array('category_id' => array('in', $id), 'norms_id' => '0'))->delete();

        $id_arr = explode(',', $id);
        $add = array();
        if ($del !== false) {
            foreach ($id_arr as $u) {
                foreach ($brands as $g) {
                    if (is_numeric($u) && is_numeric($g)) {
                        $add[] = array('brand_id' => $g, 'category_id' => $u, 'norms_id' => '0');
                    }
                }
            }
            $BranNorms->addAll($add);
        }
        if ($BranNorms->getDbError()) {
            if (count($id_arr) == 1 && count($brands) == 1) {
                //单个添加时定制错误提示
                $this->error = "不能重复添加";
            }
            return false;
        } else {
            return true;
        }
    }


    /**
     * 分类关联规格
     * @param $id
     * @param $norms
     * @return bool
     * @author liu hui
     */
    public function addCategoryNorm($id, $norms)
    {

        $id = is_array($id) ? implode(',', $id) : trim($id, ',');
        $norms = is_array($norms) ? $norms : explode(',', trim($norms, ','));

        $BranNorms = M(self::CATEGORY_BRAND_NORMS);

        //先删除旧数据
        $del = $BranNorms->where(array('category_id' => array('in', $id), 'norms_id' => array('NEQ',0)))->delete();

        $id_arr = explode(',', $id);
        $add = array();
        if ($del !== false) {
            foreach ($id_arr as $u) {
                foreach ($norms as $k => $val) {
                    foreach ($val as $g) {

                        if (is_numeric($u) && is_numeric($g)) {
                            $add[] = array('brand_id' =>$k , 'category_id' => $u, 'norms_id' =>$g );
                        }
                    }
                }
            }
            $BranNorms->addAll($add);
        }
        if ($BranNorms->getDbError()) {
            return false;
        } else {
            return true;
        }
    }

}
