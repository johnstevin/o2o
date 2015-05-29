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
}