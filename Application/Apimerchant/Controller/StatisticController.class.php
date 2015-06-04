<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 15-6-3
 * Time: 下午4:20
 */

namespace Apimerchant\Controller;


class StatisticController extends ApiController {

    /**
     * 按月统计销售额
     * @author WangJiang
     * @param int $shopId 店铺ID
     * @param int $beginYear 开始年份
     * @param null|int $endYear 结束年份，不提供则只按$beginYear一年统计
     * @return json
     */
    public function monthlySales($shopId,$beginYear,$endYear=null){
        if(is_null($endYear))
            $endYear=$beginYear;

        $model=D('Order');
        for($y=$beginYear;$y<=$endYear;++$y){
            for($m=1;$m<=12;++$m){
                //计算某年月有多少天的例子
//                if($m<12)
//                    $s=strtotime($y.'/'.($m+1).'/1')-strtotime($y.'/'.$m.'/1');
//                else
//                    $s=strtotime(($y+1).'/1/1')-strtotime($y.'/'.$m.'/1');
//                $days=$s/86400;

                $time1=strtotime($y.'/'.$m.'/1');
                if($m<12)
                    $time2=strtotime($y.'/'.($m+1).'/1');
                else
                    $time2=strtotime(($y+1).'/1/1');

                $where['shop_id']=$shopId;
                $where['add_time']=[];
                $sales=$model->where($where)->sum('price');
                $monthly[]=$sales;
            }
            $ret[]=['shop_id'=>$shopId,'monthly'=>$monthly];
        }
        $this->apiSuccess(['data'=>$ret]);
    }

    public function dailySales($shopId,$year,$beginMonth,$endMonth){

    }
}