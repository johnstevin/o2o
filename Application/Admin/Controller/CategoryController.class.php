<?php
namespace Admin\Controller;

class CategoryController extends AdminController
{

    //分类列表
    public function index()
    {
        //查询出所有的分类
        $tree = D('Category')->getTree(0, 'id,title,description,level,pid,status,sort');
        $this->assign('tree', $tree);
        $this->meta_title = '分类管理';
        $this->display();
    }

    //分类添加
    public function add($pid = 0)
    {
        $Category = D('Category');
        if (IS_POST) {
            if (false !== $Category->update()) {
                $this->success('新增成功！', U('index'));
            } else {
                $error = $Category->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $cate = array();
            if ($pid) {
                /* 获取上级分类信息 */
                $cate = $Category->info($pid, 'id,title,description,level,pid,status,sort');
                if (!($cate && 1 == $cate['status'])) {
                    $this->error('指定的上级分类不存在或被禁用！');
                }
                ++$cate['level'];
            }
            /* 获取分类信息 */
            $this->assign('info', null);
            $this->assign('category', $cate);
            $this->meta_title = '新增分类';
            $this->display('edit');
        }
    }

    //分类编辑
    public function edit($id = null, $pid = 0)
    {

        $Category = D('Category');
        if (IS_POST) { //提交表单
            if (false !== $Category->update()) {
                $this->success('编辑成功！', U('index'));
            } else {
                $error = $Category->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $cate = '';
            if ($pid) {
                /* 获取上级分类信息 */
                $cate = $Category->info($pid, 'id,title,description,level,pid,status,sort');
                if (!($cate && 1 == $cate['status'])) {
                    $this->error('指定的上级用户组不存在或被禁用！');
                }
            }
            ++$cate['level'];
            /* 获取分类信息 */
            $info = $id ? $Category->info($id) : '';
            $this->assign('info', $info);
            $this->assign('category', $cate);
            $this->meta_title = '编辑分类';
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
                $child = M('Category')->where(array('pid' => $_REQUEST['id'], 'status' => '1'))->select();
                if (!empty($child)) {
                    $this->error('请先删除该分类下的子分类');
                }
                $this->delete('Category');
                break;
            default:
                $this->error($method . '参数非法');
        }
    }

    /**
     * 关联品牌
     */
    public function relationBrand()
    {

        $id = I('id');
        $brands = I('brands');
        if (IS_POST) { //提交表单

            if (empty($id)) {
                $this->error('参数有误');
            }
            $Category = D('Category');
            $Brand = D('Brand');
            if (is_numeric($id)) {
                if (!$Category->where(array('id' => $id))->find()) {
                    $this->error('分类不存在');
                }
            }
            if ($brands && !$Brand->checkBrandId($brands)) {
                $this->error($Brand->error);
            }
            if ($Category->addCategoryBrand($id, $brands)) {
                $this->success('操作成功');
            } else {
                $this->error($Category->getError());
            }
        } else {
            /*获取所有品牌*/
            $brands = M('Brand')->field('id,title')->where(array('status' => '1'))->order('sort')->select();
            foreach($brands as $key=>&$val){
                $val['_title']= getfirstchar($val['title']);
            }

            usort($brands,function($a,$b){
                if($a['_title']==$b['_title']) return 0;
                return $a['_title']>$b['_title']?1:-1;
            });

            /*获取用户拥有的品牌*/
            $user_brands = M('CategoryBrandNorms')->field('brand_id')->where(array('category_id' => $id, 'norms_id' => 0))->select();
            $user_brands = array_unique(array_column($user_brands, 'brand_id'));

            $this->assign('_list', $brands);
            $this->assign('user_brands',   implode(',',(array)$user_brands));
            $this->meta_title = '关联品牌';
            $this->display();
        }
    }


    /**
     * 关联品牌
     */
    public function relationNorm()
    {
        $id = I('id');
        $norms = I('norms');
        if (IS_POST) { //提交表单
            if (empty($id)) {
                $this->error('参数有误');
            }
            $Category = D('Category');
            $Norm = D('Norms');
            if (is_numeric($id)) {
                if (!$Category->where(array('id' => $id))->find()) {
                    $this->error('分类不存在');
                }
            }
            if ($Category->addCategoryNorm($id, $norms)) {
                $this->success('操作成功');
            } else {
                $this->error($Category->getError());
            }

        } else {
            /*获取所有规格*/
            $norms = M('Norms')->field('id,title')->where(array('status' => '1'))->order('id')->select();
            /*获取用户拥有的品牌*/
            $user_brands=M()
                ->distinct(true)
                ->field('a.brand_id,a.norms_id,b.title')
                ->table('__CATEGORY_BRAND_NORMS__ a')
                ->join('__BRAND__ b ON  a.brand_id = b.id')
                ->where(array('category_id' => $id, 'norms_id' => 0))
                ->select();

            /*获取用户拥有的规格*/
            $user_norms = M('CategoryBrandNorms')->where(['category_id' => $id, 'norms_id' => array('NEQ',0)])->select();
            foreach ($user_norms as $no) {
                $hasnorms[$no['brand_id']][] = $no['norms_id'];
            }

            $this->assign('_list', $user_brands);
            $this->assign('_norms', $norms);
            $this->assign('user_norms', $hasnorms);
            $this->meta_title = '关联规格';
            $this->display();
        }
    }

}