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
        if (!IS_ROOT) {
            $map['user_id'] = UID;
        }

        $list = $this->lists('Feedback', $map);
        int_to_string($list, array('status' => array(-1 => '删除', 0 => '待处理', 1 => '处理中', 2 => '处理结束')));
        $this->assign('_list', $list);
        $this->meta_title = '用户信息';
        // 记录当前列表页的cookie
        Cookie('__forward__', $_SERVER['REQUEST_URI']);
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
        $Feedback = D('Feedback');
        if (IS_POST) {
            if (false !== $Feedback->update()) {

                //记录行为
//                action_log('admin_update_brand','brand',$id,UID,1);

                $this->success('编辑成功！');
            } else {
                $error = $Feedback->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $info = $id ? $Feedback->info($id) : '';
            $this->assign('info', $info);
            $this->meta_title = '编辑工单';
            $this->display();
        }
    }

    /**
     * 上传图片
     */
    public function uploadPicture()
    {
        //TODO: 用户登录检测

        /* 返回标准数据 */
        $return = array('status' => 1, 'info' => '上传成功', 'data' => '');

        /* 调用文件上传组件上传文件 */
        $Picture = D('Picture');
        $pic_driver = C('PICTURE_UPLOAD_DRIVER');
        $info = $Picture->upload(
            $_FILES,
            C('FEEDBACK_PICTURE_UPLOAD'),
            C('PICTURE_UPLOAD_DRIVER'),
            C("UPLOAD_{$pic_driver}_CONFIG")
        ); //TODO:上传到远程服务器

        /* 记录图片信息 */
        if ($info) {
            $return['status'] = 1;
            $return = array_merge($info['download'], $return);
        } else {
            $return['status'] = 0;
            $return['info'] = $Picture->getError();
        }
        /* 返回JSON数据 */
        $this->ajaxReturn($return);
    }

    /**
     * 详细
     */
    public function detail($id = 0)
    {

        $FeedbackReply = D('FeedbackReply');
        if (IS_POST) {
            if (false !== $FeedbackReply->update()) {

                //记录行为
//                action_log('admin_update_brand','brand',$id,UID,1);

                $this->success('回复成功！');
            } else {
                $error = $FeedbackReply->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $Feedback = D('Feedback');
            $reply = $id ? $FeedbackReply->feedbackdetail($id) : '';
            $info = $id ? $Feedback->info($id) : '';
            $this->assign('info', $info);
            $this->assign('replys', $reply);
            $this->assign('uid', UID);
            $this->meta_title = '问题描述';
            // 记录当前列表页的cookie
            Cookie('__forward__', $_SERVER['REQUEST_URI']);
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
                $this->forbid('Feedback');
                break;
            case 'resume':
                $this->resume('Feedback');
                break;
            case 'delete':
                $this->delete('Feedback');
                break;
            case 'offfeedback':
                $Feedback = D('Feedback');
                if (false !== $Feedback->offfeedback()) {
                    $this->success('关闭成功！');
                } else {
                    $error = $Feedback->getError();
                    $this->error(empty($error) ? '未知错误！' : $error);
                }
                break;
            default:
                $this->error($method . '参数非法');
        }
    }
}