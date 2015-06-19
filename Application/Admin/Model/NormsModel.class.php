<?php

namespace Admin\Model;
use Think\Model;

/**
 * 规格模型
 */

class NormsModel extends Model {

    /**
     * 获取规格详细信息
     * @param  int $id 分类id
     * @param bool $field 查询字段
     * @return mixed
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
     * 更新规格信息
     * @return boolean 更新状态
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

    /**
     * 检查id是否全部存在
     * @param $modelname
     * @param $mid
     * @param string $msg
     * @return bool
     * @author liu hui
     */
    public function checkId($modelname,$mid,$msg = '以下id不存在:'){
        if(is_array($mid)){
            $count = count($mid);
            $ids   = implode(',',$mid);
        }else{
            $mid   = explode(',',$mid);
            $count = count($mid);
            $ids   = $mid;
        }

        $s = M($modelname)->where(array('id'=>array('IN',$ids)))->getField('id',true);
        if(count($s)===$count){
            return true;
        }else{
            $diff = implode(',',array_diff($mid,$s));
            $this->error = $msg.$diff;
            return false;
        }
    }

    /**
     * 检查用户组是否全部存在
     * @param $brands
     * @return bool
     * @author liu hui
     */
    public function checkNormsId($norms){
        return $this->checkId('Norms',$norms, '以下用户组id不存在:');
    }

}
