<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 15-5-25
 * Time: 上午11:22
 */

namespace Common\Model;

use Think\Model\AdvModel;

/*
 * @author  WangJiang
 */
class MerchantShopModel extends AdvModel{

    /**
     * 查询商家门店
     * @author  WangJiang
     * @param double $lat 查询中心维度，必须是百度坐标
     * @param double $lng 查询中心经度，必须是百度坐标
     * @param int $range 查询半径，单位米，缺省100米
     * @param null|string|array words 关键字，w1,w2... 在title以及description字段中查找
     * @param string words_op  or|and，关键字组合方式
     * @param int $type 商家门店类型，可选0-所有类型，1-超市，2-生鲜，3-洗车，4-送水，缺省0
     * @return mixed
     */
    public function getList($lat, $lng, $range = 100,$words=null,$words_op='or',$type=0)
    {
        if (!is_numeric($lat) or !is_numeric($lng))
            //$this->error('坐标必须是数值', '', true);
            E('坐标必须是数值');

        if ($lat < -90 or $lat > 90 or $lng < -180 or $lng > 180)
            //$this->error('非法坐标', '', true);
            E('非法坐标');

        if (!is_numeric($range))
            //$this->error('查询范围必须是数值', '', true);
            E('查询范围必须是数值');

        //TODO：需要考虑最大查询范围
        if ($range < 0)
            //$this->error('非法查询范围', '', true);
            E('非法查询范围');

        $map['_string'] = 'ST_Distance_Sphere(lnglat,POINT(:lng,:lat))<:dist';

        $type=intval($type);

        if (!in_array($type, [0, 1, 2, 3, 4]))
            //$this->error('非法店面类型，可选项：0-所有类型，1-超市，2-生鲜，3-洗车，4-送水', '', true);
            E('非法店面类型，可选项：0-所有类型，1-超市，2-生鲜，3-洗车，4-送水');

        if ($type != 0)
            $map['type'] = $type;

        if (!empty($words))
            build_words_query(explode(',', $words), $words_op, ['title', 'description'], $map);

        $sql = $this->where($map)
            ->bind(':lng', $lng)
            ->bind(':lat', $lat)
            ->bind(':dist', $range)
            ->field(['id', 'title','ST_Distance_Sphere(lnglat,POINT(:lng,:lat)) as distance']);

        return $sql->select();
    }

    /**
     * 查询门店商品分类
     * @author  WangJiang
     * @param $group_id
     * @return mixed
     */
    public function shop_category($group_id){
        return M()->table('sq_merchant_depot_pro_category as a,sq_category as b')
            ->where('a.group_id=:group_id and a.category_id=b.id')
            ->field(['b.id','b.title'])
            ->select();
    }

    /**
     * 根据ID获取商家信息
     * @param int $id 商家ID
     * @return array|null
     */
    public function get($id)
    {
        $id = intval($id);
        if (!$id) return null;
        return $this->field(['id', 'title', 'description', 'type', 'open_status', 'open_time_mode'
            , 'begin_open_time', 'end_open_time', 'delivery_range', 'phone_number', 'address', 'group_id'])->find($id);
    }
}