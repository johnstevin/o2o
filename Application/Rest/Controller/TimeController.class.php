<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/5/15
 * Time: 11:51
 */

namespace Home\Controller;
use Think\Controller\RestController;
use Think\Log;

class TimeController extends RestController{

    protected $allowMethod    = array('get','post','put');

    public function _empty(){
        $this->redirect('Index/index');
    }

    public function date_get_json(){
        $this->response(array('date'=>date('Y-m-d')),'json');
    }

    /*
     * 调用参数
     */
    public function plus($x,$y){
        Log::record($x+$y,Log::INFO);
        $this->response(array('zz'=>$x+$y),'json');


    }

    public function cats_get_json(){
        $cat=D('Category');
        $this->response($cat->select(),'json');
    }

    public function cats_json(){
        $cat=D('Category');
        $this->response($cat->select(),'json');
    }

    /*
     * 缓存测试
     */
    public function ccats_get_json(){
        $data=S('all_cats1');
        if(!$data){
            $data=D('Category')->select();
            S('all_cats1',$data,array('expire'=>1));
        }else{
            $data['cached']=true;
        }
        $this->response($data,'json');
    }

    /*
     * 算法测试
     */
    public  function alg1_get_json(){
        $data='hello123';
        $key='world';

        $x    = 0;
        $len  = strlen($data);
        $l    = strlen($key);
        $char =  '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x=0;
            $char  .= substr($key, $x, 1);
            $x++;
        }
        $str = sprintf('%010d', 0);
        for ($i = 0; $i < $len; $i++) {
            $str .= substr($data,$i,1).substr($char,$i,1);//chr(ord(substr($data,$i,1)) + (ord(substr($char,$i,1)))%256);
        }
        $this->response(array('$str'=>$str,'$char'=>$char),'json');
    }

}
