<?php

namespace Common\Model;
use Think\Model;

/**
 * 用户组模型类
 * Class AuthGroupModel
 */
class RegionModel extends Model {

    protected $_validate = array(

    );

    /**
     * 获取区域信息
     */
   public function getChildRegion($pid=0,$where=array()){
    $map = array('status'=>1,'pid'=>$pid);
    $map = array_merge($map,$where);
    return $this->where($map)->select();
}
}