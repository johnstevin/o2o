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
     * @param null|string $categoryId
     * @param null|int $brandId
     * @param null|string $title
     * @param int $pagesize
     * @return json
     */
    public function lists($categoryId = null, $brandId = null, $title = null, $pagesize = 10)
    {
        $where = [];
        if (!empty($categoryId)) $where['cate_id'] = $categoryId;
        if (!empty($brandId)) $where['brand_id'] = $brandId;
        if (!empty($title)) $where['title'] = ['LIKE', $title];
        $where['status'] = ProductModel::STATUS_ACTIVE;
        $total = $this->model->where($where)->count();
        $pager = new Page($total, $pagesize);
        $this->response($this->model->cache(true)->where($where)->limit($pager->firstRow . ',' . $pager->listRows)->select(), $this->_type);
    }

    /**
     * 根据ID查找单条记录
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id
     */
    public function find($id)
    {
        $where = [];
        if (!empty($id)) $where['id'] = intval($id);
        $where['status'] = ProductModel::STATUS_ACTIVE;
        $this->response($this->model->cache(true)->find(['where' => $where]), $this->_type);
    }
}