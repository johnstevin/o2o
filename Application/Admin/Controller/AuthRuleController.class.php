<?php
namespace Admin\Controller;
use Common\Model\AuthRuleModel;

/**
 * Class 权限节点控制器
 * @package Admin\Controller
 * @author liuhui
 */
class AuthRuleController extends AdminController{

    /**
     * 用户组首页
     */
    public function index(){
//        $list = $this->lists('AuthRule', array(''=>''), 'id asc');
//        $list = int_to_string($list);
//        $this->assign('_list', $list);
//        $this->assign('_use_tip', true);
//        $this->meta_title = '权限管理';
//        $this->display();

        $tree = D('AuthRule')->getTree(0,'id,title,level,pid,status,url');
        $this->assign('_list', $tree);
//        echo"<pre>";
//        print_r($tree);
//        echo"</pre>";
        C('_SYS_GET_CATEGORY_TREE_', true); //标记系统获取分类树模板
        $this->meta_title = '用户组管理';
        $this->display();
    }
    /**
     * 新增用户组
     */
    public function add($pid = 0)
    {

        $AuthRule = D('AuthRule');
        if (IS_POST) {
            if(false !== $AuthRule->update()){
                $this->success('新增成功！', U('index'));
            } else {
                $error = $AuthRule->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $cate = array();
            if($pid){
                /* 获取上级分类信息 */
                $cate = $AuthRule->info($pid, 'id,level,title,status');
                if(!($cate && 1 == $cate['status'])){
                    $this->error('指定的上级分类不存在或被禁用！');
                }
                ++$cate['level'];
            }
            /* 获取分类信息 */
            $this->assign('info',       null);
            $this->assign('category', $cate);
            $this->meta_title = '新增用户组';
            $this->display('edit');
        }
    }

    /**
     * 编辑用户组
     */
    public function edit($id = null, $pid = 0){
        $AuthRule = D('AuthRule');

        if(IS_POST){ //提交表单
            if(false !== $AuthRule->update()){
                $this->success('编辑成功！', U('index'));
            } else {
                $error = $AuthRule->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $cate = '';
            if($pid){
                /* 获取上级分类信息 */
                $cate = $AuthRule->info($pid, 'id,level,title,status');
                if(!($cate && 1 == $cate['status'])){
                    $this->error('指定的上级用户组不存在或被禁用！');
                }
            }

            /* 获取分类信息 */
            $info = $id ? $AuthRule->info($id) : '';

            $this->assign('info',       $info);
            $this->assign('category',   $cate);
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
            case 'forbidrule':
                $this->forbid('AuthRule');
                break;
            case 'resumerule':
                $this->resume('AuthRule');
                break;
            case 'deleterule':
                $this->delete('AuthRule');
                break;
            default:
                $this->error($method . '参数非法');
        }
    }
}