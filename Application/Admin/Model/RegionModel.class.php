<?php
namespace Admin\Model;

use Think\Model;

class RegionModel extends Model
{

    protected $_validate = array(
        array('name', 'require', '区域名称是必填的！', Model::MUST_VALIDATE, 'regex', Model::MODEL_INSERT),
    );

    /**
     * 获取区域详细信息
     * @param  int $id 区域id
     * @param bool $field 查询字段
     * @return mixed
     */
    public function info($id, $field = true)
    {
        $map = array();
        if (is_numeric($id)) {//通过id来查询
            $map['id'] = $id;
        } else {//通过标题来查询
            $map['name'] = $id;
        }
        return $this->field($field)->where($map)->find();
    }


    /**
     * 获取区域树，指定区域则返回指定分类及其子区域，不指定则返回所有区域树
     * @param int $id 区域ID
     * @param bool $field 查询字段
     * @return array|mixed 区域树
     */
    public function getTree($id = 0, $field = true)
    {
        //获取当前区域信息
        if ($id) {
            $info = $this->info($id);
            $id = $info['id'];
        }
        //获取所有区域
        $map = array('status' => array('gt', -1));
        $list = $this->field($field)->where($map)->order('id')->select();
        $list = int_to_string($list);
        $list = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = $id);

        //获取返回数据
        if (isset($info)) {//指定区域则返回当前区域及其子区域
            $info['_child'] = $list;
        } else {//否则则返回所有区域
            $info = $list;
        }
        return $info;
    }

    /**
     * 更新区域信息
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
     * 获取区域信息 如果指定pid，返回所有pid等于pid的，不指定，则返回顶级
     * @author liuhui
     */
    public function showChild($pid = 0, $where = array())
    {
        $map = array('status' => 1, 'pid' => $pid);
        $map = array_merge($map, $where);
        return $this->where($map)->select();
    }

    /**
     * 获取当前用户下级区域
     * @return array
     * @author liuhui
     */
    public function subordinate()
    {
        /*获取当前用户所拥有的组织*/
        $AuthAccess = D('AuthAccess');
        $userGroup = $AuthAccess->getUserGroup(UID, 1);
        $groupIds = array();
        if (!empty($userGroup)) {
            foreach ($userGroup as $ug) {
                $groupIds[] = $ug['group_id'];
            }
        }

        /*获取用户所拥有的区域*/
        $GroupRegions = M('AuthGroupRegion')->field('region_id')->where(array('group_id' => array('in', $groupIds)))->select();
        $ids = array();

        /* 获取所有区域 */
        $map = array('status' => array('gt', -1));
        $list = $this->field('id,pid')->where($map)->order('id')->select();

        /*获取区域对应的所有下级*/
        $region_list = array();
        foreach ($GroupRegions as $gr) {
            $ids[] = $gr['region_id'];
            $temp = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $gr['region_id']);
            $temp = tree_to_list($temp, $child = '_child', $order = 'id');
            $region_list = array_merge($region_list, $temp);
        }

//        echo "<pre>";
//        print_r($region_list);
//        echo "</pre>";

//        $region_arr = array();
//        foreach ($region_list as $item) {
//            $region_arr[] = $item['id'];
//            $region_arr[]=$item['pid'];
//        }


        /*从而为数组中取出id,合并pid,去重*/
        return array_unique(array_merge(array_column($region_list, 'id'), $ids));
    }

    /**
     * 保存坐标
     * @return bool|string
     */
    public function savelnglat()
    {
        $region_id = I('id');
        $lnglat = I('lnglat');
        if(!$region_id){
            return $this->error="参数非法";
        }
       // $Model = new \Think\Model();
        return  $this->execute("update __REGION__ set lnglat=point($lnglat) where id=$region_id");
        // 要修改的数据对象属性赋值
//        $data['lnglat'] = $lnglat;
//        return  $this->where(array('id'=>$region_id))->save($data);

    }

}
