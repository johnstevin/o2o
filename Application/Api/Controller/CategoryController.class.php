<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 15-5-20
 * Time: 下午5:17
 */

namespace Api\Controller;


class CategoryController extends ApiController {

    /**
     * 查询商品类别，由于品牌和类别的关系未确定，暂时不提供关于品牌的查询和返回
     * @param null|string|array ids id1,id2... 指定分组ID，该参数和其他查询参数不相容，该参数优先级最高

     * @param null|int pid 上级分组ID，限制查询只在该分组下进行
     * @param null|string|array words 关键字，w1,w2... 在title以及description字段中查找
     * @param string words_op  or|and，关键字组合方式
     * @param string return_mode list|tree，返回模式
     * @param string deep_fetch  true|false，是否返回所有子项
     * @return
            return_mode==list
            {
            "items":[
            {
            "id":<id>,
            "pid":<pid>,
            "title":"<标题>",
            "description":"<描述>",
            },
            ...
            ]
            }
            return_mode==tree
            {
            "items":[
            {
            "id":<id>,
            "pid":<pid>,
            "title":"<标题>",
            "description":"<描述>",
            "childs":[...]
            },
            ...
            ]
            }
     */
    public function read($ids=null,$pid=null,$words=null,$words_op='or',$return_mode='list',$deep_fetch='false'){

        if($this->_method=='get'){
            $ret=null;
            $map=null;
            if(!is_null($ids)){
                $ids=explode(',',$ids);
                $map['id']  = array('in',$ids);

            }else{
                if(!is_null($pid)){
                    $map['pid']  = array('eq',$pid);
                }

                if(!is_null($words))
                    build_words_query(explode(',',$words), $words_op, ['title','description'], $map);
            }

            //TODO:$deep_fetch

            if(is_null($map))
                $this->error('查询条件不能为空','',true);

            //TODO:在必要的时候，放到查询参数中
            $map['status']=1;

            $sql=D('Category')->where($map)->field(['id','pid','title','description','icon']);
            $ret=$sql->select();

            if(is_null($ret))
                $this->error('查询失败','',true);

            if($return_mode==='tree')
                $ret=$this->_mk_tree($ret);

            $this->response(array('items'=>$ret),'json');
        }else{
            $this->error('该访问被禁止','',true);
        }

    }

    private function _mk_tree($list){
        $tree=[];
        $temp=[];
        $i=null;
        while(($target=array_pop($list))){

            if(in_array($target,$temp))
                continue;

            $treated=false;
            foreach($list as &$cmp){
                //是否子节点
                $treated|=$this->_inst_as_child($cmp,$target);
                //是否父节点
                $this->_inst_as_parent($cmp,$target,$temp);
            }

            foreach($tree as &$cmp){
                $treated|=$this->_inst_as_child($cmp,$target);
            }

            if(!$treated)
                $tree[]=$target;
        }

        return $tree;
    }

    private function _inst_as_parent($cmp,&$target,&$temp){
        if($target['id']==$cmp['pid']){
            $target['childs'][] = $cmp;
            //该节点被处理过了
            $temp[]=$cmp;
            return true;
        }
        return false;
    }

    private function _inst_as_child(&$parent,$x){
        if($x['pid']==$parent['id']){
            $parent['childs'][] = $x;
            return true;
        }
        if(array_key_exists('childs',$parent)){
            foreach($parent['childs'] as &$i){
                if($x['pid']==$i['id']){
                    $i['childs'][] = $x;
                    return true;
                }
                if($this->_inst_as_child($i,$x))
                    return true;
            }
        }
        return false;
    }


}