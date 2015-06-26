<?php
namespace Apimerchant\Controller;

use Common\Model\NormsModel;

class NormsController extends ApiController
{
    /**
     * 获取规格列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param null|string|array $categoryIds 分类ID，可选
     * @param null|string|array $brandIds 品牌ID，可选
     * @param null|int $pageSize 分页大小，可选
     * @param null|int $status 状态，可选
     */
    public function lists($categoryIds = null, $brandIds = null, $pageSize = null, $status = null)
    {
        $this->apiSuccess(NormsModel::getInstance()->getLists($categoryIds, $brandIds, $status, $pageSize));
    }
}