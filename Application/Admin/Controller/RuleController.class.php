<?php
namespace Admin\Controller;
use Admin\Model\AuthRuleModel;

/**
 * Class 权限节点控制器
 * @package Admin\Controller
 * @author liuhui
 */
class RuleController extends AdminController{

    /**
     * 用户组首页
     */
    public function index(){
        $tree = D('AuthRule')->getTree(0,'id,title,level,pid,status,url');
        $this->assign('_list', $tree);
        $this->meta_title = '用户组管理';
        // 记录当前列表页的cookie
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->display();
    }
    /**
     * 新增用户组
     */
    public function add($pid = 0)
    {

        $AuthRule = D('AuthRule');
        if (IS_POST) {

            $result=$AuthRule->update();

            if(false !== $result){

                //记录行为
                action_log('admin_add_menu','AuthRule',$result,UID,1);

                $this->success('新增成功！');
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

                //记录行为
                action_log('admin_update_menu','AuthRule',$id,UID,1);

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
                ++$cate['level'];
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
            case 'forbid':
                $this->forbid('AuthRule');
                break;
            case 'resume':
                $this->resume('AuthRule');
                break;
            case 'delete':
//                //判断该分类下有没有子分类，有则不允许删除
//                $child = M('AuthRule')->where(array('pid'=>$_REQUEST['id']))->field('id')->find();
//                if(!empty($child)){
//                    $this->error('请先删除该规则下的规则');
//                }
                $this->delete('AuthRule');
                break;
            default:
                $this->error($method . '参数非法');
        }
    }
}