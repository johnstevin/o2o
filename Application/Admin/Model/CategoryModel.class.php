<?php
namespace Admin\Model;
use Think\Model;

class CategoryModel extends Model{
    /*获取分类详细信息
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

    /*获取分类树，指定分类则返回指定分类及其子分类，不指定则返回所有分类树
    @parm $id     分类ID
    @parm fields  查询字段
    @parm array   分类树*/

    public function getTree($id=0,$field=true){
    //获取当前分类信息
    if($id){
        $info=$this->info($id);
        $id=$info['id'];
    }
        //获取所有分类
        $map=array();
        $list=$this->field($field)->where($map)->order('sort')->select();
        $list=list_to_tree($list,$pk='id',$pid='pid',$child = '_child',$root=$id);
        //获取返回数据
        if(isset($info)){//指定分类则返回当前分类及其子分类
            $info['child']=$list;
        }else{//否则则返回所有分类
            $info=$list;
        }
        return $info;
    }



}
