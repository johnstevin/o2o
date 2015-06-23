<?php
namespace Admin\Controller;

use Think\Controller;

class RegionController extends AdminController
{
    //区域列表
    public function index($id=0)
    {
        //查询出所有的区域
        //$tree = D('Region')->getTree(0, 'id,name,pid,level,status,astext(lnglat) as lnglat');
        $map=array('pid' => $id);
        $where=array('status' => array('gt', -1));
        $map=array_merge($map,$where);
        $list = $this->lists('Region',$map,'id asc','id,name,pid,level,status,astext(lnglat) as lnglat');
        $list = int_to_string($list);
        $this->assign('_list', $list);
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
            $data=array();
            switch ($info['level']) {
                case 0://省
                    $data['city'] = $info['name'];
                    $data['address']=$info['name'];
                    break; //系统级别禁用
                case 1://市
                    $data['city'] =$info['name'];
                    $data['address']=$info['name'];
                    break;
                case 2://区
                    $info2=$Region->info($info['pid']);
                    $data['city']  = $info2['name'];
                    $data['address']=$info2['name'].$info['name'];
                    break;
                case 3://商圈
                    $info2=$Region->info($info['pid']);
                    $info3=$Region->info($info2['pid']);
                    $data['city'] = $info3['name'];
                    $data['address']=$info3['name'].$info2['name'].$info['name'];
                    break;
                case 4://街道
                    $info2=$Region->info($info['pid']);
                    $info3=$Region->info($info2['pid']);
                    $info4=$Region->info($info3['pid']);
                    $data['city'] = $info4['name'];
                    //$data['address']=$info4['name'].$info3['name'].$info2['name'].$info['name'];
                    $data['address']=$info4['name'].$info3['name'].$info['name'];
                    break;
                case 5://小区
                    $info2=$Region->info($info['pid']);
                    $info3=$Region->info($info2['pid']);
                    $info4=$Region->info($info3['pid']);
                    $info5=$Region->info($info4['pid']);
                    $data['city'] = $info5['name'];
                    //$data['address']=$info5['name'].$info4['name'].$info3['name'].$info2['name'].$info['name'];
                    $data['address']=$info5['name'].$info4['name'].$info2['name'].$info['name'];
                    break;
                default:
                    break; // 0-接口参数错误（调试阶段使用）
            }
            $this->success($data);
        } else {
            $this->error('非法请求');
        }
    }

}

