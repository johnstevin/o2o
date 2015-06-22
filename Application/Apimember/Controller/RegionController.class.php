<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 15-6-2
 * Time: 下午4:29
 */

namespace Apimember\Controller;


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
     * ## 获取区域列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param null|integer $pid 父级ID
     * @param null|array|string $level 要获取的层级，比如【0,1,2】表示获取省市区三级，以此类推
     * @param null|int $status 状态，默认为获取正常状态的区域
     * @param int|null $pageSize 分页大小，如果传【null】表示获取所有数据
     * @param string $fileds 要读取的字段，可传数组 || 字符串
     */
    public function lists($pid = null, $level = null, $status = null, $pageSize = 20, $fileds = '*')
    {
        $this->apiSuccess(RegionModel::getInstance()->getLists($pid, $level, $status, $pageSize, $fileds));
    }

    /**
     * ## 获取区域的树状结构
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param int $pid 父级ID
     * @param null|string|array $level `要获取的层级`，比如【0,1,2】表示获取省市区三级，以此类推
     * @param null|int $status 状态
     * @param string $fileds 要查询的字段，注意，`id`、`pid`为必查字段
     */
    public function tree($pid = 0, $level = null, $status = null, $fileds = '*')
    {
        $this->apiSuccess(RegionModel::getInstance()->getTree($pid, $level, $status, null, $fileds));
    }
}