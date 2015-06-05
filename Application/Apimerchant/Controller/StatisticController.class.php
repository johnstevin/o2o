<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 15-6-3
 * Time: 下午4:20
 */

namespace Apimerchant\Controller;


class StatisticController extends ApiController
{

    /**
     * 按月统计销售额
     * @author WangJiang
     * @param int $shopId 店铺ID
     * @param int $beginYear 开始年份
     * @param null|int $endYear 结束年份，不提供则只按$beginYear一年统计
     * @return json
     * 样例 GET apimchant.php?s=Statistic/monthlySales/shopId/6/beginYear/2015
     * ``` json
     * {
     *
     *      "success": true,
     *      "error_code": 0,
     *      "data":
     *
     *      [
     *      {
     *      "shop_id": "6",
     *      "year": 2015,
     *      "monthly":
     *      [
     *
     *          {
     *              "month": 1,
     *              "sales": 0
     *          },
     *          {
     *              "month": 2,
     *              "sales": 0
     *          },
     *          {
     *              "month": 3,
     *              "sales": 0
     *          },
     *          {
     *              "month": 4,
     *              "sales": 0
     *          },
     *          {
     *              "month": 5,
     *              "sales": 0
     *          },
     *          {
     *              "month": 6,
     *              "sales": 8895
     *          },
     *          {
     *              "month": 7,
     *              "sales": 0
     *          },
     *          {
     *              "month": 8,
     *              "sales": 0
     *          },
     *          {
     *              "month": 9,
     *              "sales": 0
     *          },
     *          {
     *              "month": 10,
     *              "sales": 0
     *          },
     *          {
     *              "month": 11,
     *              "sales": 0
     *          },
     *          {
     *              "month": 12,
     *              "sales": 0
     *          }
     *          ]
     *          }
     *      ]
     *
     * }
     * ```
     */
    public function monthlySales($shopId, $beginYear, $endYear = null)
    {
        if (is_null($endYear))
            $endYear = $beginYear;

        $model = D('Order');
        for ($y = $beginYear; $y <= $endYear; ++$y) {
            for ($m = 1; $m <= 12; ++$m) {

                $time1 = strtotime($y . '/' . $m . '/1');
                if ($m < 12)
                    $time2 = strtotime($y . '/' . ($m + 1) . '/1');
                else
                    $time2 = strtotime(($y + 1) . '/1/1');

                $where['shop_id'] = $shopId;
                $where['add_time'] = ['between', $time1 . ',' . $time2];
                $sales = $model->where($where)->sum('price');
                #print_r($model->getLastSql());
                $monthly[] = ['month' => $m, 'sales' => $sales ? intval($sales) : 0];
            }
            $list[] = [ 'year' => intval($y), 'months' => $monthly];
        }
        $this->apiSuccess(['data' => ['shop_id' => $shopId,'years'=>$list]]);
    }

    /**
     * 按天统计销售量
     * @author WangJiang
     * @param int $shopId     商铺ID
     * @param int $year       年份
     * @param int $beginMonth 开始月份
     * @param null|int $endMonth 结束月份，为空的话只统计$beginMonth当月
     * @return json
     * ``` json
     * {
     *      "success": true,
     *      "error_code": 0,
     *      "data"{
     *          "shop_id":<shop id>,
     *          "year":<year,例如2015>,
     *          "months":[
     *              {
     *                  "month":<month,例如1>,
     *                  "sales":[
     *                      {
     *                          "day":<day,例如5>,
     *                          "sales":<销售额，单位元>
     *                      },...
     *                  ]
     *              },...
     *          ]
     *      }
     * }
     * ```
     * 样例 GET apimchant.php?s=Statistic/dailySales/shopId/6/year/2015/beginMonth/1/endMonth/12
     */
    public function dailySales($shopId, $year, $beginMonth, $endMonth = null)
    {
        if (is_null($endMonth))
            $endMonth = $beginMonth;

        $model = D('Order');
        for($m=$beginMonth;$m<=$endMonth;++$m){
            //计算天数
            if($m<12)
                $s=strtotime($year.'/'.($m+1).'/1')-strtotime($year.'/'.$m.'/1');
            else
                $s=strtotime(($year+1).'/1/1')-strtotime($year.'/'.$m.'/1');
            $days=$s/86400;
            $list=[];
            for($d=1;$d<=$days;++$d){
                $time1 = strtotime($year . '/' . $m . '/'.$d.' 00:00:00');
                if($d<$days)
                    $time2 = strtotime($year . '/' . $m . '/'.($d+1).' 00:00:00');
                else{
                    if($m<12)
                        $time2 = strtotime($year . '/' . ($m+1) . '/1 00:00:00');
                    else
                        $time2 = strtotime(($year+1) . '/1/1 00:00:00');
                }

                $where['shop_id'] = $shopId;
                $where['add_time'] = ['between', $time1 . ',' . $time2];
                $sales = $model->where($where)->sum('price');
                #print_r($model->getLastSql());
                $list[] = ['day' => $d, 'sales' => $sales ? intval($sales) : 0];
            }
            $months[]=['month'=>$m,'sales'=>$list];
        }

        $this->apiSuccess(['data' => ['shop_id' => $shopId,'year'=>$year,'months'=>$months]]);
    }
}