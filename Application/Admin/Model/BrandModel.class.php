<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Model;
use Think\Model;

/**
 * 品牌模型
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */

class BrandModel extends Model {

    /*获取品牌详细信息
     @param      $id     分类id
     @param      $fields 查询字段
    */
    public function info($id,$field=true){
        $map=array();
        if(is_numeric($id)){//通过id来查询
            $map['id']=$id;
        }else{//通过标题来查询
            $map['title']=$id;
        }
        return $this->field($field)->where($map)->find();
    }

    /**
     * 更新品牌信息
     * @return boolean 更新状态
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function update(){
        $data = $this->create();
        if(!$data){ //数据对象创建错误
            return false;
        }
        /* 添加或更新数据 */
        if(empty($data['id'])){
            $res = $this->add();
        }else{
            $res = $this->save();
        }
        return $res;
    }

}
