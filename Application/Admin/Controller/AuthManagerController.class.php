<?php

namespace Admin\Controller;
use Common\Model\AuthRoleModel;
use Common\Model\AuthGroupModel;


/**
 * 权限管理控制器
 * Class AuthController
 * @package Admin\Controller
 */
class AuthManagerController extends AdminController
{

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
            case 'forbidrole':
                $this->forbid('AuthRole');
                break;
            case 'resumerole':
                $this->resume('AuthRole');
                break;
            case 'deleterole':
                $this->delete('AuthRole');
                break;
            default:
                $this->error($method . '参数非法');
        }
    }

    /**
     *将用户添加到角色的编辑页面
     */
    public function role()
    {
        $uid = I('uid');

        /*获取组织*/
        $auth_Groups = D('AuthGroup')->getGroups();
        $userAccess = M('auth_access')->where(['uid' => $uid, 'status' => 1])->select();
        foreach ($userAccess as $access) {
            $hasAccess[$access['group_id']][] = $access['role_id'];
        }
        /*获取组织下的所有的角色*/
        $AuthRole = M('AuthRole');
        foreach ($auth_Groups as &$key) {
            $key['_roles'] = $AuthRole->where(array('group_id' => $key['id'], 'status' => '1'))->select();
        }
//        $AuthAccess = M('AuthAccess');
//        $user_roles = $AuthAccess->field('uid,group_id,role_id')->where(array('uid' => $uid))->group('group_id')->select();
//        $roles = array();
//        foreach ($user_roles as $val) {
//            $roles[$val['group_id']] = $AuthAccess->field('role_id')->where(array('group_id' => $val['group_id']))->select();
//        }
//        echo "<pre>";
//        print_r($roles);
//        echo '</pre>';


//        $nickname = D('Member')->getNickName($uid);
//        $this->assign('nickname', $nickname);
//        $this->assign('auth_roles', $auth_roles);
        $this->assign('user_roles', $hasAccess);
        $this->assign('node_list', $auth_Groups);
        $this->meta_title = '用户授权';
        $this->display();
    }

    /**
     * 将用户添加到角色组,入参uid,role_id
     */
    public function addToRole()
    {

        $uid = I('uid');

        $gid = I('group_role');

        if (empty($uid)) {
            $this->error('参数有误');
        }
        $AuthRole = D('AuthRole');
        if (is_numeric($uid)) {
            if (is_administrator($uid)) {
                $this->error('该用户为超级管理员');
            }
            if (!M('Member')->where(array('uid' => $uid))->find()) {
                $this->error('用户不存在');
            }
        }

        foreach ($gid as $val) {

            if ($gid && !$AuthRole->checkRoleId($val)) {
                $this->error($AuthRole->error);
            }

        }
        if ($AuthRole->addToRole($uid, $gid)) {
            $this->success('操作成功', U('User/index'));
        } else {
            $this->error($AuthRole->getError());
        }
    }

    /**
     * 访问授权页面
     */
    public function access()
    {
        //$this->updateRules();
        $auth_group = M('AuthRole')->where(array('status' => array('egt', '0'), 'module' => 'admin', 'type' => AuthRoleModel::TYPE_ADMIN))
            ->getfield('id,title,group_id');
//        //$node_list   = $this->returnNodes();
//        $map = array('module' => 'admin', 'type' => AuthRuleModel::RULE_MAIN, 'status' => 1);
//        $main_rules = M('AuthRule')->where($map)->getField('id');
//        $map = array('module' => 'admin', 'type' => AuthRuleModel::RULE_URL, 'status' => 1);
//        $child_rules = M('AuthRule')->where($map)->getField('id');
//
        /*获取rule*/
        $tree = D('AuthRule')->getTree(0, 'id,title,level,pid,status');

        /*获取之前用户已经存在的*/
        $child_list = M('AuthRoleRule')->where(array('role_id' => (int)I('group_id')))->select();
        $child_tree = array();
        foreach ($child_list as &$value) {
            $child_tree[] = $value['rule_id'];
        }
        $child_tree = is_array($child_tree) ? implode(',', $child_tree) : trim($child_tree, ',');
//        echo"<pre>";
//        print_r($tree);
//        echo"</pre>";

//        $this->assign('main_rules', $main_rules);
//        $this->assign('auth_rules', $child_rules);
        $this->assign('node_list', $tree);
        $this->assign('child_list', $child_tree);
        $this->assign('auth_group', $auth_group);
        $this->assign('this_group', $auth_group[(int)$_GET['group_id']]);
        $this->meta_title = '访问授权';
        $this->display();
    }

    /**
     * 将角色添加到规则组,入参role,rule
     */
    public function addToRule()
    {
        $role = I('role');
        $rule = I('rules');
        if (empty($role)) {
            $this->error('参数有误');
        }
        $AuthRule = D('AuthRule');
        if (is_numeric($role)) {
            if (!M('AuthRole')->where(array('id' => $role))->find()) {
                $this->error('角色不存在');
            }
        }
        if ($rule && !$AuthRule->checkRuleId($rule)) {
            $this->error($AuthRule->error);
        }
        if ($AuthRule->addToRule($role, $rule)) {
            $this->success('操作成功', U('index'));
        } else {
            $this->error($AuthRule->getError());
        }
    }
}
