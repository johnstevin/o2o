<?php
namespace Admin\Controller;

use Common\Model\AuthGroupModel;

/**
 * Class 用户组控制器
 * @package Admin\Controller
 * @author liuhui
 */
class GroupController extends AdminController
{

    /**
     * 用户组首页
     */
    public function index()
    {

        $tree = D('AuthGroup')->getTree(0, 'id,group_code,title,description,level,pid,status');
        $this->assign('_list', $tree);
        $this->meta_title = '用户组管理';
        $this->display();
    }

    /**
     * @param int 上级pid，默认为顶级
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
     * 新增用户组
     */
    public function add($pid = 0)
    {
        $AuthGroup = D('AuthGroup');
         //TODO  事物控制
        if (IS_POST) {
            $result = $AuthGroup->update();
            if (false !== $result) {

                /* 添加或更新数据 */
                if ($AuthGroup->saveRegion($result)) {
                    $this->success('新增成功！', U('index'));
                } else {
                    $error = $AuthGroup->getError();
                    $this->error(empty($error) ? '请选择区域' : $error);
                }
            } else {
                $error = $AuthGroup->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $cate = array();
            //$Region=D('Region');
            $region = D('Region')->showChild();
//            echo'<pre>';)//            print_r($Region);die;);
            if ($pid) {
                /* 获取上级分类信息 */
                $cate = $AuthGroup->info($pid, 'id,level,title,status');
                if (!($cate && 1 == $cate['status'])) {
                    $this->error('指定的上级分类不存在或被禁用！');
                }
                ++$cate['level'];

                /*获取上一级一致的区域信息*/
            }

            /* 获取分类信息 */
            $this->assign('info', null);
            $this->assign('category', $cate);
            $this->assign('region', $region);
            $this->meta_title = '新增用户组';
            $this->display('edit');
        }
    }

    /**
     * 编辑用户组
     */
    public function edit($id = null, $pid = 0)
    {
        $AuthGroup = D('AuthGroup');
        //$Region=D('Region');
        if (IS_POST) { //提交表单
            if (false !== $AuthGroup->update()) {
                $this->success('新增成功！', U('index'));
            } else {
                $error = $AuthGroup->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $cate = '';
            if ($pid) {
                /* 获取上级分类信息 */
                $cate = $AuthGroup->info($pid, 'id,level,title,status');
                if (!($cate && 1 == $cate['status'])) {
                    $this->error('指定的上级用户组不存在或被禁用！');
                }
            }

            /* 获取分类信息 */
            $info = $id ? $AuthGroup->info($id) : '';

            $this->assign('info', $info);
            $this->assign('category', $cate);
            $this->assign('region', null);
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
            case 'forbidgroup':
                $this->forbid('AuthGroup');
                break;
            case 'resumegroup':
                $this->resume('AuthGroup');
                break;
            case 'deletegroup':

//                //判断该分类下有没有子分类，有则不允许删除
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