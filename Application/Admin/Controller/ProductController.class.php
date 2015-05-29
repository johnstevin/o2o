<?php

namespace Admin\Controller;
use Admin\Model\ProductModel;

/**
 * 产品管理控制器
 * Class AuthController
 * @package Admin\Controller
 * @author liuhui
 */
class ProductController extends AdminController
{
    /**
     * 产品显示列表
     */
    public function index()
    {
        $list = $this->lists('Product','', 'edit_time desc');
        $Brands = D('Brand');
        $norms = D('Norms');
        foreach ($list as &$v) {
            $v['brand'] = $Brands->where(array('id' => $v['brand_id']))->getField('title');
            $v['norm'] = $norms->where(array('id' => $v['norms_id']))->getField('title');
        }
        $list = int_to_string($list, array('status' => array(1 => '正常', -1 => '删除', 0 => '禁用', 2 => '未审核', 3 => '草稿')));
        $this->assign('_list', $list);
        $this->meta_title = '商品管理';
        $this->display();
    }

    /**
     * 添加商品
     */
    public function add()
    {
        $Product = D('Product');
        if (IS_POST) {
            if (false !== $Product->update()) {
                $this->success('新增成功！', U('index'));
            } else {
                $error = $Product->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {

            /* 获取品牌信息 */
            $brands = M('Brand')->where(array('status' => array('egt', 1)))->order('title asc')->select();
            /*获取规格信息*/
            $norms=M('Norms')->select();
            /* 获取分类信息 */
            $this->assign('info', null);
            $this->assign('list_brand', $brands);
            $this->assign('list_norm',$norms);
            $this->meta_title = '新增用户组';
            $this->display('edit');
        }
    }

    /**
     *编辑商品
     */
    public function edit($id = null)
    {
        $Product = D('Product');
        if (IS_POST) { //提交表单
            if (false !== $Product->update()) {
                $this->success('编辑成功！', U('index'));
            } else {
                $error = $Product->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {

            /* 获取品牌信息 */
            $brands = M('Brand')->where(array('status' => array('egt', -1)))->order('title asc')->select();
            /*获取规格信息*/
            $norms=M('Norms')->select();
            /* 获取商品信息 */
            $info = $id ? $Product->info($id) : '';

            $this->assign('info', $info);
            $this->assign('list_brand', $brands);
            $this->assign('list_norm',$norms);
            $this->meta_title = '编辑用户组';
            $this->display();
        }
    }

    /**
     * 状态修改
     */
    public function changeStatus($method = null)
    {
        if (empty($_REQUEST['id'])) {
            $this->error('请选择要操作的数据!');
        }
        switch (strtolower($method)) {
            case 'forbid':
                $this->forbid('Product');
                break;
            case 'resume':
                $this->resume('Product');
                break;
            case 'delete':
                $this->delete('Product');
                break;
            default:
                $this->error($method . '参数非法');
        }
    }
}