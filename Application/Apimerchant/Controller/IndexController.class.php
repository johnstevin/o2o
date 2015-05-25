<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Api\Controller;

class IndexController extends ApiController {

	//TODO
    public function index(){
    }

    /**
     * 查询商家门店
     * @param double $lat 查询中心维度，必须是百度坐标
     * @param double $lng 查询中心经度，必须是百度坐标
     * @param double $range 查询半径，单位米，缺省100米
     * @param null|string|array words 关键字，w1,w2... 在title以及description字段中查找
     * @param string words_op  or|and，关键字组合方式
     * @param int $type 商家门店类型，可选0-所有类型，1-超市，2-生鲜，3-洗车，4-送水，缺省0
     */
    public function shop($lat, $lng, $range = 100,$words=null,$words_op='or',$type='0')
    {
        //因为MySql5.6不支持ST_Distance_Sphere，自己实线该函数，但是精度和百度计算的距离有0.15的误差
        $range = $range * 1.15;

        if ($this->_method == 'get') {

            if (!is_numeric($lat) or !is_numeric($lng))
                $this->error('坐标必须是数值', '', true);

            if ($lat < -90 or $lat > 90 or $lng < -180 or $lng > 180)
                $this->error('非法坐标', '', true);

            if (!is_numeric($range))
                $this->error('查询范围必须是数值', '', true);

            //TODO：需要考虑最大查询范围
            if ($range < 0)
                $this->error('非法查询范围', '', true);

            $map['_string'] = 'ST_Distance_Sphere(lnglat,POINT(:lng,:lat))<:dist';

            if (!is_numeric($type))
                $this->error('坐标必须是数值', '', true);

            if (!in_array($type, ['0', '1', '2', '3', '4']))
                $this->error('非法店面类型，可选项：0-所有类型，1-超市，2-生鲜，3-洗车，4-送水', '', true);

            if ($type != '0')
                $map['type'] = $type;

            if (!empty($words))
                build_words_query(explode(',', $words), $words_op, ['title', 'description'], $map);

            $sql = M('MerchantShop')->where($map)
                ->bind(':lng', $lng)
                ->bind(':lat', $lat)
                ->bind(':dist', $range)
                ->field(['id', 'title', 'description', 'type', 'open_status', 'open_time_mode'
                    , 'begin_open_time', 'end_open_time', 'delivery_range', 'phone_number', 'address', 'group_id']);

            $ret = $sql->select();

            //print_r($map);
            //print_r($sql->getLastSql());

            $this->response(array('items' => $ret), 'json');
        } else {
            $this->error('该访问被禁止', '', true);
        }
    }

    /**
     * 查询门店商品分类
     * @param $group_id
     */
    public function shop_category($group_id){
        //TODO:还未测试
        if ($this->_method == 'get') {
            $this->response(array('items'=>M()->table('sq_merchant_depot_pro_category as a,sq_category as b')
                ->where('a.group_id=:group_id and a.category_id=b.id')
                ->field(['b.id','b.title'])
                ->select()),'json');
        } else {
            $this->error('该访问被禁止', '', true);
        }
    }
}
