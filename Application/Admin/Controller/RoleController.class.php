<?php

namespace Admin\Controller;
use Admin\Model\AuthRoleModel;
use Admin\Model\AuthGroupModel;


/**
 * 权限管理控制器
 * Class AuthController
 * @package Admin\Controller
 * @author liuhui
 */
class RoleController extends AdminController
{

    /**
     * 角色列表显示
     */
    public function index()
    {
        $list = $this->lists('AuthRole', array('module' => 'admin'), 'id asc');
        $groups = D('AuthGroup');
        foreach ($list as &$k) {
            $k['group'] = $groups->where(array('id' => $k['group_id']))->getField('title');
        }
        $list = int_to_string($list, array('status' => array(1 => '正常', -1 => '删除', 0 => '禁用', 2 => '未审核', 3 => '草稿'), 'public' => array(1 => '公共', 0 => '私有')));
        $this->assign('_list', $list);
        $this->assign('_use_tip', true);
        $this->meta_title = '权限管理';
        $this->display();
    }

    /**
     * 新增用户组
     */
    public function add()
    {

        $AuthGroup = D('AuthRole');
        if (IS_POST) {
            if (false !== $AuthGroup->update()) {
                $this->success('新增成功！', U('index'));
            } else {
                $error = $AuthGroup->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {

            /* 获取上级分类信息 */
            $cate = D('AuthGroup')->getGroups();
            /* 获取分类信息 */
            $this->assign('info', null);
            $this->assign('auth_group', $cate);
            $this->meta_title = '新增用户组';
            $this->display('edit');
        }
    }

    /**
     * 编辑用户组
     */
    public function edit($id = null, $pid = 0)
    {
        $AuthRole = D('AuthRole');

        if (IS_POST) { //提交表单
            if (false !== $AuthRole->update()) {
                $this->success('编辑成功！', U('index'));
            } else {
                $error = $AuthRole->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {

            /* 获取上级分类信息 */
            $cate = D('AuthGroup')->getGroups();
//            if(!($cate && 1 == $cate['status'])){
//                $this->error('指定的上级用户组不存在或被禁用！');
//            }
            /* 获取分类信息 */
            $info = $id ? $AuthRole->info($id) : '';

            $this->assign('info', $info);
            $this->assign('auth_group', $cate);
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
                $this->forbid('AuthRole');
                break;
            case 'resume':
                $this->resume('AuthRole');
                break;
            case 'delete':
                $this->delete('AuthRole');
                break;
            default:
                $this->error($method . '参数非法');
        }
    }

}
