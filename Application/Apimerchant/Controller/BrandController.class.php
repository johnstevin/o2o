<?php
namespace Apimerchant\Controller;

use Common\Model\BrandModel;

class BrandController extends ApiController
{
    /**
     * 获取品牌列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param null|int|array|string $categoryIds 分类ID，可选参数。可传多个ID值
     * @param null|int $pageSize 分页大小
     */
    public function lists($categoryIds = null, $pageSize = null)
    {
        $this->apiSuccess(BrandModel::getInstance()->getLists($categoryIds, $pageSize));
    }
}