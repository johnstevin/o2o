<?php

namespace Common\Model;
use Think\Model;

/**
 * 用户组模型类
 * Class AuthGroupModel
 * @author liuhui
 */
class RegionModel extends Model {

    protected $_validate = array(

    );

    /**
     * 获取区域信息 如果指定pid，返回所有pid等于pid的，不指定，则返回顶级
     */
   public function showChild($pid=0,$where=array()){
    $map = array('status'=>1,'pid'=>$pid);
    $map = array_merge($map,$where);
    return $this->where($map)->select();
}

    /**
     * 获取分类树，指定分类则返回指定分类极其子分类，不指定则返回所有分类树
     * @param  integer $id    分类ID
     * @param  boolean $field 查询字段
     * @return array          分类树
     */
    public function getTree($id = 0, $field = true){
        /* 获取当前分类信息 */
        if($id){
            $info = $this->info($id);
            $id   = $info['id'];
        }

        /* 获取所有分类 */
        $map  = array('status' => array('gt', -1));
        $list = $this->field($field)->where($map)->order('id')->select();
        $list = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = 'child', $root = $id);

        /* 获取返回数据 */
        if(isset($info)){ //指定分类则返回当前分类极其子分类
            $info['_'] = $list;
        } else { //否则返回所有分类
            $info = $list;
        }

        return $info;
    }
}