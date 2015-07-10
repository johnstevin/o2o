<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 15-6-2
 * Time: 下午4:24
 */

namespace Apimerchant\Controller;


use Common\Model\RegionModel;

class RegionController extends ApiController
{

    /**
     * 获得区域
     * @author WangJiang
     * @param int $pid 上级区域ID，不提供则返回顶级区域
     * @return json
     *<pre>
     * 调用样例 GET apimchant.php?s=/Region/getList/pid/1
     * </pre>
     * ``` json
     * {
     *  "success": true,
     *  "error_code": 0,
     *  "data": [
     *      {
     *          "id": "3",
     *          "name": "成都市",
     *          "pid": "1",
     *          "level": "1",
     *          "status": "1"
     *      },
     *      {
     *          "id": "4",
     *          "name": "绵阳市",
     *          "pid": "1",
     *          "level": "1",
     *          "status": "1"
     *      },
     *      {
     *          "id": "5",
     *          "name": "乐山市",
     *          "pid": "1",
     *          "level": "1",
     *          "status": "1"
     *      }
     *  ]
     *
     * }
     * ```
     */
    public function getList($pid = 0)
    {
        try {
            $this->apiSuccess(['data' => (new RegionModel())->showChild($pid)]);
        } catch (\Exception $ex) {
            $this->apiError(50030, $ex->getMessage());
        }
    }

    /**
     * ## 添加小区
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param string $name 小区名称
     * @param int $pid 父级ID（应该是街道的ID）
     * @param float $lng 经度
     * @param float $lat 纬度
     */
    public function addCommunity($name, $pid, $lng, $lat)
    {
        $this->apiSuccess(['data' => RegionModel::getInstance()->addRegion($name, $pid, $lng, $lat)]);
    }

    /**
     * 根据一个区域ID倒推，返回一个二维数组
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $id 区域ID
     */
    public function pushDownById($id)
    {
        $this->apiSuccess(['data'=>RegionModel::getInstance()->getRegionPath($id)]);
    }
}