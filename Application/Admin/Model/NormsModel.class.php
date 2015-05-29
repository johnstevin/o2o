<?php

namespace Admin\Model;
use Think\Model;

/**
 * 规格模型
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */

class NormsModel extends Model {

    /*获取规格详细信息
 @param      $id     分类id
 @param      $fields 查询字段*/
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
     * 更新规格信息
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
