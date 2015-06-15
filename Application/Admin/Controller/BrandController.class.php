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
            if ($_POST['category3'] != 0 && $_POST['category2'] != 0  && $_POST['category1'] != 0) {
                $categoryId = $_POST['category3'];
            } else if ($_POST['category2'] != 0  && $_POST['category1'] != 0) {
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

            //在categorybrandnorms表中查询出所有的字段
            $both_id=$abnmodel->select();
            //在categorybrandnorms表中根据category_id查询出所有的品牌id值
            $b_id=$abnmodel->field('brand_id')->where(array('category_id'=>array('IN',$categoryId)))->select();
            //将category_id和brand_id字段取出来形成一个单独集合
            $cid=array_column($both_id,'category_id');
            $bid=array_column($b_id,'brand_id');
            //判断发送的分类id值（一、二、三级）是否在category_id数组集合中
            $cate1=in_array($_POST['category1'],$cid);
            $cate2=in_array($_POST['category2'],$cid);
            $cate3=in_array($_POST['category3'],$cid);
            //根据发送的品牌id在 根据分类id查询出的品牌id集合中进行比较,如果存在提示用户：有相同记录,否则存入用户.
            $b=$_POST[brand_id];
            $diff=array_diff($b,$bid);
            $empty=empty($diff);

            //3.通过遍历将一对多的数组转换成多对多的数组
            foreach ($diff as $brand) {
                $data[] = [
                    'category_id' => $categoryId,
                    'brand_id' => $brand,
                ];
            }

            //4.以数组的方式传递三级分类及关联品牌的数据到bindNorms_index页面
            $bdata=array(
                'category_1'=>$_POST['category1'],
                'category_2'=>!empty($_POST['category2'])?$_POST['category2']:'无',
                'category_3'=>!empty($_POST['category3'])?$_POST['category3']:'无',
                'brand'=>$diff,
            );

            //判断一、二、三级分类及绑定的品牌在中间表中是否又相同记录,如果有提示用户,否则插入数据
            if($cate1==false){
                $result = $abnmodel->addAll($data);
                if (is_int($result)) {
                    $this->redirect('Norms/bindNorms', $bdata, 3, '关联成功,页面跳转中...');
                }
            }elseif(($cate1==true) && ($empty==false)){
                $result = $abnmodel->addAll($data);
                if (is_int($result)) {
                    $this->redirect('Norms/bindNorms', $bdata, 3, '关联成功,页面跳转中...');
                }
            }else{
                $this->error('对不起、有相同记录,请重新关联！', U('bindBrand'));
            }

            if($cate2==false){
                $result = $abnmodel->addAll($data);
                if (is_int($result)) {
                    $this->redirect('Norms/bindNorms', $bdata, 3, '关联成功,页面跳转中...');
                }
            }elseif(($cate2==true) && ($empty==false)){
                $result = $abnmodel->addAll($data);
                if (is_int($result)) {
                    $this->redirect('Norms/bindNorms', $bdata, 3, '关联成功,页面跳转中...');
                }
            }else{
                $this->error('对不起、有相同记录,请重新关联！', U('bindBrand'));
            }

            if($cate3==false){
                $result = $abnmodel->addAll($data);
                if (is_int($result)) {
                    $this->redirect('Norms/bindNorms', $bdata, 3, '关联成功,页面跳转中...');
                }
            }elseif(($cate3==true) && ($empty==false)){
                $result = $abnmodel->addAll($data);
                if (is_int($result)) {
                    $this->redirect('Norms/bindNorms', $bdata, 3, '关联成功,页面跳转中...');
                }
            }else{
                $this->error('对不起、有相同记录,请重新关联！', U('bindBrand'));
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
            $this->display("bindbrandindex");
        }
    }
}
