<?php
namespace Admin\Controller;

use Admin\Model\AuthRoleModel;


/**
 * 权限管理控制器
 * Class AuthController
 * @package Admin\Controller
 * @author liuhui
 */
class AccessController extends AdminController
{


    /**
     * 访问授权页面
     * @author liuhui
     */
    public function index()
    {
//        //$this->updateRules();
//        $auth_group = M('AuthRole')->where(array('status' => array('egt', '0'), 'module' => 'admin', 'type' => AuthRoleModel::TYPE_ADMIN))
//            ->getfield('id,title,group_id');

        /*获取rule*/
        $tree = D('AuthRule')->getTree(0, 'id,title,level,pid,status');

        /*获取之前用户已经存在的*/
        $child_list = M('AuthRoleRule')->where(array('role_id' => (int)I('group_id')))->select();
        $child_tree = array_column($child_list, 'rule_id');

        $child_tree = is_array($child_tree) ? implode(',', $child_tree) : trim($child_tree, ',');
        $this->assign('node_list', $tree);
        $this->assign('child_list', $child_tree);
        //$this->assign('auth_group', $auth_group);
//        $this->assign('this_group', I('group_id'));
        $this->meta_title = '访问授权';
        $this->display();
    }

    /**
     *将用户添加到角色的编辑页面
     * @author liuhui
     */
    public function authorize()
    {
        $uid = I('uid');


        /*根据用户type判断是组织类型*/
        $_type = I('_type');
        $map = array();
        if (!IS_ROOT) {
            switch (strtolower($_type)) {
                case '1':
                    $map = array('type' => C('AUTH_GROUP_TYPE')['ADMIN']);
                    break;
                case'3':
                    $map = array('type' => C('AUTH_GROUP_TYPE')['MEMBER']);
                    break;
                case'2':
                    $map = array('type' => C('AUTH_GROUP_TYPE')['MERCHANT']);
                    break;
                default:
                    $this->error('参数错误');
                    break;
            }
        }
        /*获取组织*/
        $AuthGroup = D('AuthGroup');
        $auth_Groups = $AuthGroup->getGroups($map, 'id,pid,title');

        /*用户已经拥有的角色*/
        $userAccess = M('auth_access')->where(['uid' => $uid, 'status' => 1])->select();
        foreach ($userAccess as $access) {
            $hasAccess[$access['group_id']][] = $access['role_id'];
        }


        /*获取组织下的所有的角色*/
        $AuthRole = M('AuthRole');
        foreach ($auth_Groups as &$key) {
            $key['_roles'] = $AuthRole->field('id,title,group_id')->where(array('group_id' => $key['id'], 'status' => '1'))->select();
        }

        /*格式化数据*/
        $Tree = D('Tree');
        $auth_Groups = $Tree->toFormatTree($auth_Groups);
//      $auth_Groups = $Tree->toTree($auth_Groups, $pk = 'id', $pid = 'pid', $child = '_child');


        /*非超管级管理员只列出拥有权限的组织*/
        if (!IS_ROOT) {
            /*获取当前用户所拥有的组织*/
            foreach ($auth_Groups as $key => $data) {
                if (!in_array($data['id'], $AuthGroup->UserAuthGroup())) {
                    unset($auth_Groups[$key]);
                    continue;
                }
            }
        }

//        echo "<pre>";
//        print_r($auth_Groups);
//        echo "</pre>";

        $this->assign('user_roles', $hasAccess);
        $this->assign('node_list', $auth_Groups);
        $this->meta_title = '用户授权';
        $this->display();
    }

    /**
     * 将用户添加到角色组,入参uid,role_id
     * @author liuhui
     */
    public function addToRole()
    {

        $uid = I('uid');

        $gid = I('group_role');

        if (empty($uid)) {
            $this->error('参数有误');
        }
        $AuthAccess = D('AuthAccess');
        $AuthRole = D('AuthRole');
        if (is_numeric($uid)) {
            if (is_administrator($uid)) {
                $this->error('该用户为超级管理员');
            }
//            if (!M('UcenterMember')->where(array('uid' => $uid))->find()) {
//                $this->error('用户不存在');
//            }
        }

        foreach ($gid as $val) {
            if ($gid && !$AuthRole->checkRoleId($val)) {
                $this->error($AuthRole->error);
            }

        }

        if ($AuthAccess->addToRole($uid, $gid)) {


            //记录行为
            action_log('admin_authorize', 'AuthAccess', $uid, UID, 1);


            $this->success('操作成功');
        } else {
            $this->error($AuthAccess->getError());
        }
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

            //记录行为
            action_log('admin_add_rule', 'AuthRoleRule', $role, UID, 1);

            $this->success('操作成功');
        } else {
            $this->error($AuthRule->getError());
        }
    }
}