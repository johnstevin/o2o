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
//        $list = $this->lists('AuthGroup', array('id' => array('neq', AuthGroupModel::GROUP_ADMIN)), 'id asc');
//        $list = int_to_string($list);
//        $this->assign('_list', $list);
//        $this->assign('_use_tip', true);
//        $this->meta_title = '权限管理';
//        $this->display();

        $tree = D('AuthGroup')->getTree(0, 'id,group_code,title,description,level,pid,status');
        $this->assign('_list', $tree);
//        echo"<pre>";
//        print_r($tree);
//        echo"</pre>";
//        C('_SYS_GET_CATEGORY_TREE_', true); //标记系统获取分类树模板
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

        if (IS_POST) {
            $result = $AuthGroup->update();
            if (false !== $result) {
                /*保存区域*/
                $region_id = I('level5') != 0 ? I('level5') : I('level4') != 0 ? I('level4') : I('level3') != 0 ? I('level3') : I('level2') != 0 ? I('level2') : I('level1') != 0 ? I('level1') : 0;
                if ($region_id == 0) {
                    $this->error('请选择区域');
                }
                $GroupRegion = M('AuthGroupRegion');
                $data['group_id'] = $result;
                $data['region_id'] = $region_id;
                /* 添加或更新数据 */
                if ($GroupRegion->add($data)) {
                    $this->success('新增成功！', U('index'));
                } else {
                    $error = $GroupRegion->getError();
                    $this->error(empty($error) ? '未知错误！' : $error);
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
                $this->delete('AuthGroup');
                break;
            default:
                $this->error($method . '参数非法');
        }
    }

}