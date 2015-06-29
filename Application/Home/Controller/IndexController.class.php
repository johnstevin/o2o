<?php

namespace Home\Controller;
use OT\DataDictionary;

/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class IndexController extends HomeController {

	//系统首页
    public function index(){
        echo '<a href="'.U('Index/login').'">login</a>';
    }

    public function login(){
        echo '<a href="'.U('Index/index').'">index</a>';
    }

    /**
     * 短信测试接口
     * @author WangJiang
     * @param $mobiles
     * @param $text
     */
    public function sms($mobiles,$text){
        \Addons\Sms\Common\send_code(explode(',',$mobiles),$text);
    }

    /**
     * 分词测试接口
     * @author WangJiang
     * @param $text
     */
    public function scws($text){
        $so = scws_new();
        $so->set_charset('utf8');
        $so->send_text($text);
        echo '<pre>';
        while ($tmp = $so->get_result())
        {
            foreach($tmp as $i){
                print_r($i['word']);echo '<br/>';
            }
            break;
        }
        $so->close();
    }

}