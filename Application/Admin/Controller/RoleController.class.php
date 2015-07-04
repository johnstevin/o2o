<?php

namespace Admin\Controller;
use Admin\Model\AuthRoleModel;
use Admin\Model\AuthGroupModel;


/**
 * 角色管理控制器
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
        //TODO liu hui where
        $group_id       =   I('group_id');
        $map['status']  =   array('egt',0);
        if(!empty($group_id)){
            $map['group_id']=   array('EQ',$group_id);
        }
        /*非管理员判断权限*/
        if (!IS_ROOT) {
            $AuthGoup = D('AuthGroup');
            $UserAuthGroup = $AuthGoup->UserAuthGroup();
            $map['group_id']=   array('in',$UserAuthGroup);
        }

        $list = $this->lists('AuthRole',$map , 'id asc');

        $groups = D('AuthGroup');
        $cate=$groups-> UserGroupFormat();

        foreach ($list as &$k) {
            $k['group'] = $groups->where(array('id' => $k['group_id']))->getField('title');
        }
        $list = int_to_string($list, array('status' => array(1 => '正常', -1 => '删除', 0 => '禁用', 2 => '未审核', 3 => '草稿'), 'public' => array(1 => '公共', 0 => '私有')));


        $cate = array_merge(array(0=>array('id'=>0,'title_show'=>'请选择组织')), $cate);

        $this->assign('_list', $list);
        $this->assign('_use_tip', true);
        $this->assign('auth_group', $cate);
        $this->assign('selected_group', $group_id);
        $this->meta_title = '角色管理';
        // 记录当前列表页的cookie
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->display();

    }

    /**
     * 新增角色
     */
    public function add()
    {

        $AuthRole = D('AuthRole');
        if (IS_POST) {

            $result=$AuthRole->update();
            if (false !== $result) {

                //记录行为
                action_log('admin_add_role','AuthRole',$result,UID,1);

                $this->success('新增成功！', U('index'));
            } else {
                $error = $AuthRole->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {

            /* 获取上级有权限的组织信息 */
            $groups = D('AuthGroup');
            $cate=$groups-> UserGroupFormat();

            /* 获取分类信息 */
            $this->assign('info', null);
            $this->assign('auth_group', $cate);
            $this->meta_title = '新增角色';
            $this->display('edit');
        }
    }

    /**
     * 编辑角色
     */
    public function edit($id = null, $pid = 0)
    {
        $AuthRole = D('AuthRole');

        if (IS_POST) { //提交表单
            if (false !== $AuthRole->update()) {

                //记录行为
                action_log('admin_update_role','AuthRole',$id,UID,1);

                $this->success('编辑成功！', U('index'));
            } else {
                $error = $AuthRole->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {

            /* 获取上级组织信息 */
            $groups = D('AuthGroup');
            $cate=$groups-> UserGroupFormat();

            /*非管理员判断权限*/
            if (!IS_ROOT) {
                $UserAuthRole = $AuthRole->UserAuthRole();
                if (!in_array($id, $UserAuthRole)) {
                    $this->error('权限不足,请联系管理员!');
                }
            }

//            if(!($cate && 1 == $cate['status'])){
//                $this->error('指定的上级角色不存在或被禁用！');
//            }
            /* 获取分类信息 */
            $info = $id ? $AuthRole->info($id) : '';

            $this->assign('info', $info);
            $this->assign('auth_group', $cate);
            $this->meta_title = '编辑角色';
            $this->display();
        }
    }

    /**
     * 状态修改
     */
    public function changeStatus($method = null)
    {
        $id = $_REQUEST['id'];
        if (empty($id)) {
            $this->error('请选择要操作的数据!');
        }
        /*非管理员判断权限*/
        if (!IS_ROOT) {
            $AuthRole = D('AuthRole');
            $UserAuthRole = $AuthRole->UserAuthRole();
            if (!in_array($id, $UserAuthRole)) {
                $this->error('权限不足,请联系管理员!');
            }
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
