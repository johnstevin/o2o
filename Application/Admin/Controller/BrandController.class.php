<?php

namespace Admin\Controller;

use Think\Controller;

class BrandController extends AdminController
{
    /**
     * 品牌管理首页
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function index()
    {
        //查询出所有的分类
        $list = $this->lists('Brand');
        int_to_string($list);
        $this->assign('_list', $list);
        $this->display();
    }

    //品牌添加
    public function add()
    {
        $Brand = D('Brand');
        if (IS_POST) {
            if (false !== $Brand->update()) {
                $this->success('新增成功！', U('index'));
            } else {
                $error = $Brand->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        }
        $this->assign('brand');
        $this->display('edit');
    }

    //品牌编辑
    public function edit($id = null)
    {
        $Brand = D('Brand');
        if (IS_POST) { //提交表单
            if (false !== $Brand->update()) {
                $this->success('编辑成功！', U('index'));
            } else {
                $error = $Brand->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        }
        /* 获取分类信息 */
        $info = $id ? $Brand->info($id) : '';
        $this->assign('info', $info);
        $this->assign('brand');
        $this->display('edit');

    }

    /**
     * 会员状态修改
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function changeStatus($method = null)
    {
        if (empty($_REQUEST['id'])) {
            $this->error('请选择要操作的数据!');
        }
        switch (strtolower($method)) {
            case 'forbidbrand':
                $this->forbid('Brand');
                break;
            case 'resumebrand':
                $this->resume('Brand');
                break;
            case 'deletebrand':
                $this->delete('Brand');
                break;
            default:
                $this->error('参数非法');
        }
    }

    //根据选择的分类增加所属品牌
    public function bindBrand()
    {
        if (IS_POST) {
            //一、根据选择的分类绑定品牌，关联成功跳转到'Norms/bindNorms'方法指定的页面，关联失败重新回到"bindBrand_index"页面
            $abnmodel = M("Category_brand_norms");
            $categoryId = "";
            //1.判断并确定三个下拉框中的分类id值并对应其标题（当二三级分类不存在的时候、默认选择一级分类；当三级分类不存在的时候、默认选择三级分类）
            if ($_POST['category3'] != 0 & $_POST['category2'] != 0  & $_POST['category1'] != 0) {
                $categoryId = $_POST['category3'];
            } else if ($_POST['category2'] != 0  & $_POST['category1'] != 0) {
                $categoryId = $_POST['category2'];
            } else if ($_POST['category1'] != 0) {
                $categoryId = $_POST['category1'];
            }
            //2.对选择的分类顺序合理性的判断
            if ($_POST['category1'] ==0 and $_POST['category2'] ==0 and $_POST['category3'] ==0) {
                $this->error('请重新选择分类', U(Brand / bindBrand));
            } elseif ($_POST['category1'] ==0 and $_POST['category2'] !=0) {
                $this->error('请选择一级分类', U(Brand / bindBrand));
            } elseif ($_POST['category1'] ==0 and $_POST['category2'] ==0 and $_POST['category3'] !=0) {
                $this->error('请选择二级分类', U(Brand / bindBrand));
            }
            //3.通过遍历将一对多的数组转换成多对多的数组
            foreach ($_POST['brand_id'] as $brand) {
                $data[] = [
                    'category_id' => $categoryId,
                    'brand_id' => $brand,
                ];
            }
            $result = $abnmodel->addAll($data);
            //4.以数组的方式传递三级分类及关联品牌的数据到bindNorms_index页面
            $bdata=array(
                'category_1'=>$_POST['category1'],
                'category_2'=>!empty($_POST['category2'])?$_POST['category2']:'无',
                'category_3'=>!empty($_POST['category3'])?$_POST['category3']:'无',
                'brand'=>$_POST['brand_id'],
            );
            if (is_int($result)) {
                $this->redirect('Norms/bindNorms', $bdata, 3, '关联成功,页面跳转中...');
            } else {
                $this->error('对不起，关联失败！', U('bindBrand'));
            }
        } else {
            //二、分类的三级联动下拉选择菜单
            $tree = D("Category")->getTree(0, 'id,sort,title,pid,status');
            $d1 = [];
            $d2 = [];
            $d3 = [];
            foreach ($tree as $key => $value) {
                $d1[] = [
                    'id' => $value['id'],
                    'title' => $value['title']
                ];
                foreach ($value['_child'] as $ke => $va) {
                    $d2[] = [
                        'id' => $va['id'],
                        'title' => $va['title'],
                        'pid' => $va['pid']
                    ];
                    foreach ($va['_child'] as $k => $v) {
                        $d3[] = [
                            'id' => $v['id'],
                            'title' => $v['title'],
                            'pid' => $v['pid']
                        ];
                    }
                }
            }
            $this->assign('category_1', $d1);
            $this->assign('category_2', $d2);
            $this->assign('category_3', $d3);
            //三、品牌列表显示
            $list = $this->lists('Brand');
            int_to_string($list);
            $this->assign('_list', $list);
            $this->display("bindBrand_index");
        }
    }
}
