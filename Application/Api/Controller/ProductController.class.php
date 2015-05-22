<?php
namespace Api\Controller;

use Common\Model\ProductModel;
use Think\Model;
use Think\Page;

/**
 * Product Api
 * @package Api\Controller
 * @author Fufeng Nie <niefufeng@gmail.com>
 */
class ProductController extends ApiController
{
    protected $model = null;//Product Model

    protected function _initialize()
    {
        parent::_initialize();
        if (!($this->model instanceof Model)) {
            $this->model = new ProductModel();
        }
    }

    /**
     * 活取列表
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param null|string $categoryId 分类ID
     * @param null|int $brandId 品牌ID
     * @param null|string $title 商品标题（模糊查询）
     * @param int $pagesize 页面大小
     * @param int|null $status 状态
     * @return json
     */
    public function lists($categoryId = null, $brandId = null, $title = null, $pagesize = 10, $status = ProductModel::STATUS_ACTIVE)
    {
        $this->response(ProductModel::getLists($categoryId, $brandId, $status, $title, $pagesize)['data'], $this->_type);
    }

    /**
     * 根据ID查找单条记录
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id
     */
    public function find($id)
    {
        $where = [];
        $where['id'] = intval($id);
        $where['status'] = ProductModel::STATUS_ACTIVE;
        $this->response($this->model->cache(true)->find(['where' => $where]), $this->_type);
    }
}