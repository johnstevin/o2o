<?php
namespace Admin\Controller;

use Think\Controller;

class RegionController extends AdminController
{
    //区域列表
    public function index()
    {
        //查询出所有的区域
        $tree = D('Region')->getTree(0, 'id,name,pid,level,status,astext(lnglat) as lnglat');
        $this->assign('tree', $tree);
        $this->meta_title = '区域管理';
        $this->display();
    }

    //区域添加
    public function add($pid = 0)
    {
        $Region = D('Region');
        if (IS_POST) {
            if (false !== $Region->update()) {
                $this->success('新增成功！', U('index'));
            } else {
                $error = $Region->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $Reg = array();
            if ($pid) {
                /* 获取上级区域信息 */
                $Reg = $Region->info($pid, 'id,level,name,status');
                if (!($Reg && 1 == $Reg['status'])) {
                    $this->error('指定的上级区域不存在或被禁用！');
                }
                ++$Reg['level'];
            }
            /* 获取区域信息 */
            $this->assign('info', null);
            $this->assign('region', $Reg);
            $this->meta_title = '增加区域';
            $this->display('edit');
        }
    }

    //区域编辑
    public function edit($id = null, $pid = 0)
    {

        $Region = D('Region');
        if (IS_POST) { //提交表单
            if (false !== $Region->update()) {
                $this->success('编辑成功！', U('index'));
            } else {
                $error = $Region->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $Reg = '';
            if ($pid) {
                /* 获取上级区域信息 */
                $Reg = $Region->info($pid, 'id,level,name,status');
                if (!($Reg && 1 == $Reg['status'])) {
                    $this->error('指定的上级区域不存在或被禁用！');
                }
                ++$Reg['level'];
            }
            /* 获取区域信息 */
            $info = $id ? $Region->info($id) : '';
            $this->assign('info', $info);
            $this->assign('region', $Reg);
            $this->meta_title = '编辑区域';
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
            case 'forbidregion':
                $this->forbid('Region');
                break;
            case 'resumeregion':
                $this->resume('Region');
                break;
            case 'deleteregion':
                //判断该区域下有没有子区域，有则不允许删除
                $child = M('Region')->where(array('pid' => $_REQUEST['id'], 'status' => '1'))->select();
                if (!empty($child)) {
                    $this->error('请先删除该区域下的子区域');
                }
                $this->delete('Region');
                break;
            default:
                $this->error($method . '参数非法');
        }
    }

    /**
     * 保存百度坐标
     */
    public function lnglat()
    {
        if (IS_POST) {
            $Region = D('Region');
            if (false !== $Region->savelnglat()) {
                $this->success('刷新坐标成功！');
            } else {
                $error = $Region->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $this->error('非法请求');
        }
    }

    /**
     * 得到city
     */
    public function MapCity($id)
    {
        if (IS_POST) {
            $Region = D('Region');
            /* 获取区域信息 */
            $info = $id ? $Region->info($id) : '-1';

            switch ($info['level']) {
                case 0:
                    $city = $info['name'];
                    break; //系统级别禁用
                case 1:
                    $city =$info['name'];
                    break;
                case 2:
                    $info2=$Region->info($info['pid']);
                    $city = $info2['name'];
                    break;
                case 3:
                    $info2=$Region->info($info['pid']);
                    $info3=$Region->info($info2['pid']);
                    $city = $info3['name'];
                    break;
                case 4:
                    $info2=$Region->info($info['pid']);
                    $info3=$Region->info($info2['pid']);
                    $info4=$Region->info($info3['pid']);
                    $city = $info4['name'];
                    break;
                case 5:
                    $info2=$Region->info($info['pid']);
                    $info3=$Region->info($info2['pid']);
                    $info4=$Region->info($info3['pid']);
                    $info5=$Region->info($info4['pid']);
                    $city = $info5['name'];
                    break;
                default:
                    $city = '-1';
                    break; // 0-接口参数错误（调试阶段使用）
            }

            echo $city;
        } else {
            echo "-1";
        }
    }

}

