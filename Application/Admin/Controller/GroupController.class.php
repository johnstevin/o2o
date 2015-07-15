<?php
namespace Admin\Controller;

/**
 * Class 组织机构控制器
 * @package Admin\Controller
 * @author liuhui
 */
class GroupController extends AdminController
{

    /**
     * 组织机构首页
     */
    public function index()
    {

        $tree = D('AuthGroup')->getTree(0, 'id,group_code,title,description,level,pid,status,public,type');
        $this->assign('_list', $tree);
        $this->meta_title = '组织机构管理';
        $this->assign('http_host', $_SERVER['HTTP_HOST']);
        // 记录当前列表页的cookie
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->display();
    }

    /**
     * @param int $pid 上级pid，默认为顶级
     */
    public function showChild($pid)
    {
        if ($pid == 0) {
            $this->ajaxReturn("");
        }
        $region = D('Region')->showChild($pid);
        $this->ajaxReturn($region);
    }

    /**
     * 新增组织机构
     */
    public function add($pid = 0)
    {
        $AuthGroup = D('AuthGroup');

        M()->startTrans();
        if (IS_POST) {
            $result = $AuthGroup->update();
            if (false !== $result) {

                //商户组的不再保存区域
                if (C('AUTH_GROUP_ID')['MERCHANT_GROUP_ID'] == $pid) {
                    M()->commit();

                    //记录行为
                    action_log('admin_add_group','AuthGroup',$result,UID,1);

                    $this->success('新增成功！');
                }

                /* 添加或更新数据 */
                if (false !== $AuthGroup->saveRegion($result)) {
                    M()->commit();

                    //记录行为
                    action_log('admin_add_group','AuthGroup',$result,UID,1);

                    $this->success('新增成功！');
                } else {
                    M()->rollback();
                    $error = $AuthGroup->getError();
                    $this->error(empty($error) ? '未知错误' : $error);
                }
            } else {
                M()->rollback();
                $error = $AuthGroup->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $cate = array();

            $region = D('Region')->showChild();

            if ($pid) {
                /* 获取上级组织信息 */
                $cate = $AuthGroup->info($pid, 'id,level,title,status,type');
                if (!($cate && 1 == $cate['status'])) {
                    $this->error('指定的上级组织不存在或被禁用！');
                }
                ++$cate['level'];

                /*获取上一级一致的区域信息*/
            }

            /* 获取组织信息 */
            $this->assign('info', null);
            $this->assign('category', $cate);
            $this->assign('region', C('AUTH_GROUP_ID')['MERCHANT_GROUP_ID'] == $pid ? null : $region);
            $this->meta_title = '新增组织机构';
            $this->display('edit');
        }
    }

    /**
     * 编辑组织机构
     */
    public function edit($id = null, $pid = 0)
    {
        $AuthGroup = D('AuthGroup');
        //$Region=D('Region');
        if (IS_POST) { //提交表单
            if (false !== $AuthGroup->update()) {


                //记录行为
                action_log('admin_update_group','AuthGroup',$id,UID,1);

                $this->success('编辑成功！');
            } else {
                $error = $AuthGroup->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {

            /*非管理员判断权限是否越权*/
            if (!IS_ROOT) {
                $UserAuthGroup = $AuthGroup->UserAuthGroup();
                if (!in_array($id, $UserAuthGroup)) {
                    $this->error('权限不足,请联系管理员!');
                }
            }

            $cate = '';
            if ($pid) {
                /* 获取上级组织信息 */
                $cate = $AuthGroup->info($pid, 'id,level,title,status,type');
                if (!($cate && 1 == $cate['status'])) {
                    $this->error('指定的上级组织机构不存在或被禁用！');
                }
                ++$cate['level'];
            }

            /* 获取组织信息 */
            $info = $id ? $AuthGroup->info($id) : '';
            $this->assign('info', $info);
            $this->assign('category', $cate);
            $this->assign('region', null);
            $this->meta_title = '编辑组织机构';
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

        /*非管理员判断权限是否越权*/
        if (!IS_ROOT) {
            $AuthGroup = D('AuthGroup');
            $UserAuthGroup = $AuthGroup->UserAuthGroup();
            if (!in_array($id, $UserAuthGroup)) {
                $this->error('权限不足,请联系管理员!');
            }
        }

        switch (strtolower($method)) {
            case 'forbid':
                $this->forbid('AuthGroup');
                break;
            case 'resume':
                $this->resume('AuthGroup');
                break;
            case 'delete':

//                //判断该组织下有没有子组织，有则不允许删除
//                $child = M('AuthGroup')->where(array('pid'=>$_REQUEST['id']))->field('id')->find();
//                if(!empty($child)){
//                    $this->error('请先删除该组织下的组织');
//                }
                $this->delete('AuthGroup');
                break;
            default:
                $this->error($method . '参数非法');
        }
    }

}