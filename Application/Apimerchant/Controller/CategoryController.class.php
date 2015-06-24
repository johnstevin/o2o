<?php
namespace Apimerchant\Controller;

use Common\Model\CategoryModel;

class CategoryController extends ApiController
{
    /**
     * ## 获得分类列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     *
     * @param null|int $pid 父级ID
     * @param null|string|array $level 层级
     * @param null|int $pageSize 分页大小，传null为不分页
     * @param string $fields 要查询的字段
     */
    public function lists($pid = null, $level = null, $pageSize = null, $fields = '*')
    {
        $this->apiSuccess(CategoryModel::getInstance()->getLists($pid, $level, $pageSize, $fields));
    }
}