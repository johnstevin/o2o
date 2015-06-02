<?php

namespace Admin\Controller;

class IndexController extends AdminController {

    public function index(){
        $result= '[{"id":"01","fid":null,"text":"首页","iconCls":null,"EnglishTitle":"System Home","url":null,"children":[]},{"id":"02","fid":null,"text":"商家","iconCls":null,"EnglishTitle":"Permission","url":"/PermissionMgr/Default/Index","children":[{"id":"0201","fid":"02","text":"权限分配","EnglishTitle":"Permission","url":"/PermissionMgr/Permissions/Index","iconCls":null},{"id":"0202","fid":"02","text":"权限模板","EnglishTitle":"Permission Template","url":"http://localhost/Zqdn/Admin/AuthManager/index","iconCls":null}]},{"id":"03","fid":null,"text":"用户","iconCls":null,"EnglishTitle":"Basic Info","url":"/BasicInfo/Default/Index","children":[{"id":"0301","fid":"03","text":"字典类别","EnglishTitle":"Dictionary","url":"/BasicInfo/DataItems/List","iconCls":null},{"id":"0302","fid":"03","text":"字典值","EnglishTitle":null,"url":"/BasicInfo/DataItemValues/Index","iconCls":null},{"id":"0303","fid":"03","text":"客户级别","EnglishTitle":null,"url":"/BasicInfo/ClientLevels/Index","iconCls":null},{"id":"0304","fid":"03","text":"上课效果","EnglishTitle":null,"url":"/BasicInfo/Effects/Index","iconCls":null}]},{"id":"04","fid":null,"text":"商品","iconCls":null,"EnglishTitle":"Student","url":"/StudentMgr/Default/Index","children":[{"id":"0401","fid":"04","text":"学生信息","EnglishTitle":"Student Info","url":"/StudentMgr/Student/Index","iconCls":null},{"id":"0402","fid":"04","text":"课程管理","EnglishTitle":null,"url":"/StudentMgr/StudentProduct/Index","iconCls":null}]},{"id":"05","fid":null,"text":"系统","iconCls":null,"EnglishTitle":"Product","url":"/ProductMgr/Default/Index","children":[{"id":"0501","fid":"05","text":"系统配置","EnglishTitle":"Product Infomation","url":"/ProductMgr/Product/Index","iconCls":null},{"id":"0502","fid":"05","text":"组织管理","EnglishTitle":"Product Infomation","url":"/ProductMgr/Product/Index","iconCls":null},{"id":"0503","fid":"05","text":"角色管理","EnglishTitle":"Product Infomation","url":"/ProductMgr/Product/Index","iconCls":null},{"id":"0504","fid":"05","text":"菜单管理","EnglishTitle":"Product Infomation","url":"/ProductMgr/Product/Index","iconCls":null},{"id":"0505","fid":"05","text":"权限管理","EnglishTitle":"Product Infomation","url":"/ProductMgr/Product/Index","iconCls":null}]}]';
        //$menu_list = json_decode($result);
        $this->assign('menu_list',$result);
        $this->display();

    }

    /**
     * 生成菜单
     * @param array 条件
     */
    public function getMenus($where=array()){

        $menus =D('AuthRule')->getMenus($where);
        $this-> ajaxReturn($menus);
    }

}