<?php
// +----------------------------------------------------------------------
// | 商户管理模块
// +----------------------------------------------------------------------
// | Data:2015-5-20
// +----------------------------------------------------------------------
namespace Api\Controller;

class MerchantController extends ApiController
{


    /**
     * 查询商家
     * @param double $lat 查询中心维度，必须是百度坐标
     * @param double $lng 查询中心经度，必须是百度坐标
     * @param double $range 查询半径，单位米，缺省100米
     * @param null|string|array words 关键字，w1,w2... 在title以及description字段中查找
     * @param string words_op  or|and，关键字组合方式
     * @param int $type 商家类型，可选0-所有类型，1-超市，2-生鲜，3-洗车，4-送水，缺省0
     */
    public function read($lat, $lng, $range = 100,$words=null,$words_op='or',$type=0)
    {
        //因为MySql5.6不支持ST_Distance_Sphere，自己实线该函数，但是精度和百度计算的距离有0.15的误差
        $range = $range * 1.15;

        if ($this->_method == 'get') {

            $map['_string']='ST_Distance_Sphere(lnglat,POINT(:lng,:lat))<:dist';

            if(!is_null($words))
                build_words_query(explode(',',$words), $words_op, ['title','description'], $map);

            $sql = D('Merchant')->where($map)
                ->bind(':lng', $lng)
                ->bind(':lat', $lat)
                ->bind(':dist', $range)
                ->field(['id', 'title']);

            //TODO:完善商家查询，引入sql_ucenter_member关联表

            $ret = $sql->select();

            $this->response(array('items' => $ret), 'json');
        } else {
            $this->error('该访问被禁止', '', true);
        }
    }
}
