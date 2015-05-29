<?php
namespace Admin\Controller;

class CategoryController extends AdminController {

    //分类列表
    public function index(){
    //查询出所有的分类
        $tree=D("Category")->getTree(0,'id,sort,title,pid,status');
        $this->assign('tree',$tree);
        $this->display();
    }

    //分类添加
    public function add($pid = 0){
        $Category = D('Category');
        if (IS_POST) {
            if(false !== $Category->update()){
                $this->success('新增成功！', U('index'));
            } else {
                $error = $Category->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $cate = array();
            if($pid){
                /* 获取上级分类信息 */
                $cate = $Category->info($pid, 'id,level,title,status');
                if(!($cate && 1 == $cate['status'])){
                    $this->error('指定的上级分类不存在或被禁用！');
                }
                ++$cate['level'];
            }
            /* 获取分类信息 */
            $this->assign('info', null);
            $this->assign('category', $cate);
            $this->display('edit');
        }
    }

    //分类编辑
    public function edit($id = null, $pid = 0){

        $Category = D('Category');
        if(IS_POST){ //提交表单
            if(false !== $Category->update()){
                $this->success('编辑成功！', U('index'));
            } else {
                $error = $Category->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $cate = '';
            if($pid){
                /* 获取上级分类信息 */
                $cate = $Category->info($pid, 'id,level,title,status');
                if(!($cate && 1 == $cate['status'])){
                    $this->error('指定的上级用户组不存在或被禁用！');
                }
            }
            /* 获取分类信息 */
            $info = $id ? $Category->info($id) : '';
            $this->assign('info',$info);
            $this->assign('category',$cate);
            $this->display('edit');
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
            case 'forbidcategory':
                $this->forbid('Category');
                break;
            case 'resumecategory':
                $this->resume('Category');
                break;
            case 'deletecategory':
                //判断该分类下有没有子分类，有则不允许删除
                $child = M('Category')->where(array('pid'=>$_REQUEST['id'],'status'=>'1'))->select();
                if(!empty($child)){
                    $this->error('请先删除该分类下的子分类');
                }
                $this->delete('Category');
                break;
            default:
                $this->error($method . '参数非法');
        }
    }

}