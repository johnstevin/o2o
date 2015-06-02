<?php
namespace Admin\Model;
use Think\Model;

class RegionModel extends Model{
    /*获取区域详细信息
      @param      $id     区域id
      @param      $fields 查询字段*/
    public function info($id,$field=true){
        $map=array();
        if(is_numeric($id)){//通过id来查询
            $map['id']=$id;
        }else{//通过标题来查询
            $map['name']=$id;
        }
        return $this->field($field)->where($map)->find();
    }

    /*获取区域树，指定区域则返回指定分类及其子区域，不指定则返回所有区域树
    @parm $id     区域ID
    @parm fields  查询字段
    @parm array   区域树*/

    public function getTree($id=0,$field=true){
        //获取当前区域信息
        if($id){
            $info=$this->info($id);
            $id=$info['id'];
        }
        //获取所有区域
        $map=array();
        $list=$this->field($field)->where($map)->select();
        $list=list_to_tree($list,$pk='id',$pid='pid',$child = '_child',$root=$id);
        //获取返回数据
        if(isset($info)){//指定区域则返回当前区域及其子区域
            $info['child']=$list;
        }else{//否则则返回所有区域
            $info=$list;
        }
        return $info;
    }

    /**
     * 更新区域信息
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


    /**
     * 获取区域信息 如果指定pid，返回所有pid等于pid的，不指定，则返回顶级
     * @author liuhui
     */
    public function showChild($pid=0,$where=array()){
        $map = array('status'=>1,'pid'=>$pid);
        $map = array_merge($map,$where);
        return $this->where($map)->select();
    }
}
