<?php

namespace Admin\Controller;

/**
 * 反馈控制器
 * @author liuhui
 */
class FeedbackController extends AdminController
{
    /**
     * 工单管理
     */
    public function index()
    {

        $name = I('name');
        $map['status'] = array('egt', 0);
        if (is_numeric($name)) {
            $map['id|title'] = array(intval($name), array('like', '%' . $name . '%'), '_multi' => true);
        } else {
            $map['title'] = array('like', '%' . (string)$name . '%');
        }
        $list = $this->lists('Feedback', $map);
        int_to_string($list, array('status' => array(-1 => '删除', 0 => '待处理', 1 => '处理中', 2 => '处理结束')));
        $this->assign('_list', $list);
        $this->meta_title = '用户信息';
        $this->display();
    }

    /**
     * 新增
     */
    public function add()
    {
        if (IS_POST) {
            $Feedback = D('Feedback');
            if (false !== $Feedback->update()) {
                $this->success('新增成功！', U('index'));
            } else {
                $error = $Feedback->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $this->meta_title = '提交工单';
            $this->assign('info', null);
            $this->display('edit');
        }
    }

    /**
     * 编辑
     */
    public function edit($id = 0)
    {
        if (IS_POST) {
            $Feedback = D('Feedback');
            $data = $Feedback->create();
            if ($data) {
                if ($Feedback->save()) {
                    S('DB_CONFIG_DATA', null);
                    //记录行为
                    action_log('update_config', 'config', $data['id'], UID);
                    $this->success('更新成功', Cookie('__forward__'));
                } else {
                    $this->error('更新失败');
                }
            } else {
                $this->error($Feedback->getError());
            }
        } else {
            $info = array();
            /* 获取数据 */
            $info = M('Config')->field(true)->find($id);

            if (false === $info) {
                $this->error('获取信息错误');
            }
            $this->assign('info', $info);
            $this->meta_title = '编辑工单';
            $this->display();
        }
    }

    /**
     * 详细
     */
    public function detail(){

        $this->display();
    }
}