<?php

namespace Admin\Controller;

/**
 * 版本管理控制器
 */
class VersionController extends AdminController
{

    /**
     * 版本管理首页
     * @author Liu Hui
     */
    public function index()
    {
        $map = array('status' => array('gt', -1));
        $list = $this->lists('Version', $map);
        int_to_string($list, array('package_type' => array(1 => '用户', 2 => '商家'), 'version_type' => array(1 => '基础版',2 => '内测版',3 => '公测版',4 => '候选版', 5 => '发行版',)));
        // 记录当前列表页的cookie
        Cookie('__forward__', $_SERVER['REQUEST_URI']);

        $this->assign('_list', $list);
        $this->meta_title = '版本管理';
        $this->display();
    }

    /**
     * 新增页面初始化
     * @author Liu Hui
     */
    public function add()
    {
        if (IS_POST) {
            $file=I('file');
            if(empty($file)){
                $this->error('请上传安装包！');
            }
            $file = json_decode(think_decrypt($file));
            if (isset($file->id) && is_numeric($file->id)) {
                $this->error('该安装包数据库已经存在！');
            }
            $Version = D('Version');
            $data = $Version->create();

            $data['path'] = $file->path;
            $data['md5'] = $file->md5;
            $data['sha1'] = $file->sha1;

            $result = $Version->add($data);
            if (false !== $result) {
                //记录行为
                // action_log('admin_add_menu','AuthRule',$result,UID,1);
                $this->success('新增成功！');
            } else {
                $this->error('新增失败！');
            }
        } else {
            $this->assign('info', null);
            $this->meta_title = '新增版本';
            $this->display('edit');
        }
    }


    /* 文件上传 */
    public function upload()
    {
        $return = array('status' => 1, 'info' => '上传成功', 'data' => '');
        /* 调用文件上传组件上传文件 */
        $File = D('Version');
        $file_driver = C('DOWNLOAD_UPLOAD_DRIVER');
        $info = $File->upload(
            $_FILES,
            C('VERSION_PACKAGE_UPLOAD'),
            C('DOWNLOAD_UPLOAD_DRIVER'),
            C("UPLOAD_{$file_driver}_CONFIG")
        );

        /* 记录附件信息 */
        if ($info) {
            $return['data'] = think_encrypt(json_encode($info['file']));
            $return['info'] = $info['file']['name'];
        } else {
            $return['status'] = 0;
            $return['info'] = $File->getError();
        }

        /* 返回JSON数据 */
        $this->ajaxReturn($return);
    }

    /**
     * 编辑页面初始化
     * @author Liu Hui
     */
    public function edit($id = null)
    {
        $Version = D('Version');
        if (IS_POST) {
            if (empty($id)) {
                $this->error('参数非法');
            }
            $file = json_decode(think_decrypt(I('file')));
            if (isset($file->id) && is_numeric($file->id)) {
                $this->error('该安装包数据库已经存在！');
            }
            $Version = D('Version');
            $data = $Version->create();
            if(!empty($file->path)) {
                $data['path'] = $file->path;
                $data['md5'] = $file->md5;
                $data['sha1'] = $file->sha1;
            }
            $result = $Version->save($data);
            if (false !== $result) {
                //记录行为
                // action_log('admin_add_menu','AuthRule',$result,UID,1);
                $this->success('编辑成功！');
            } else {
                $this->error('编辑失败！');
            }
        } else {

            /* 获取信息 */
            $info = $id ? $Version->info($id) : '';
            $this->assign('info', $info);
            $this->meta_title = '编辑模型';
            $this->display();
        }
    }

    /**
     * 发布一个版本
     * @author Liu Hui
     */
    public function  publish($method = null)
    {
        $ids = I('get.ids');
        empty($ids) && $this->error('参数不能为空！');
        $Version = D('Version');
//        $ids = explode(',', $ids);

        switch (strtolower($method)) {
            case 'forbid':
                $data['status'] = '0';
                break;
            case 'resume':
                $data['status'] = '1';
                break;
            default:
                $this->error($method . '参数非法');
        }

        $res = $Version->where(array('id' => array('in', $ids)))->save($data);
//            if (!$res) {
//                break;
//            }
//        }
        if (!$res) {
            $error = $Version->getError();
            $this->error(empty($error) ? '未知错误！' : $error);
        } else {
            $this->success('保存成功！');
        }
    }

    /**
     * 删除一条数据
     * @author Liu Hui
     */
    public function del()
    {
        $ids = I('get.ids');
        empty($ids) && $this->error('参数不能为空！');
        $Version = D('Version');
//        $ids = explode(',', $ids);
//        foreach ($ids as $value) {
        $data['status'] = '-1';
        $res = $Version->where(array('id' => array('in', $ids)))->save($data);
//            if (!$res) {
//                break;
//            }
//        }
        if (!$res) {
            $error = $Version->getError();
            $this->error(empty($error) ? '未知错误！' : $error);
        } else {
            $this->success('删除成功！');
        }
    }
}
