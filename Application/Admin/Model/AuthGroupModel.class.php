<?php


namespace Admin\Model;

use Think\Model;

/**
 * 组织机构模型类
 * Class AuthGroupModel
 * @author liuhui
 */
class AuthGroupModel extends Model
{
    const GROUP_ADMIN = 1;                   // 超级管理员组织机构类型标识

    protected $_validate = array(
        array('title', 'require', '必须设置组织机构标题', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        //array('title','require', '必须设置组织机构标题', Model::EXISTS_VALIDATE  ,'regex',Model::MODEL_INSERT),
        array('description', '0,80', '描述最多80字符', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
        // array('rules','/^(\d,?)+(?<!,)$/', '规则数据不合法', Model::VALUE_VALIDATE , 'regex'  ,Model::MODEL_BOTH ),
    );

//    protected $_link = [
//        'Roles' => [
//            'mapping_type' => self::HAS_MANY,
//            'class_name' => 'AuthRole',
//            'foreign_key' => 'group_id',
//            'mapping_name' => '_roles',
//            'mapping_order' => 'id desc',
//            'condition' => 1
//        ]
//    ];

    /**
     * 返回组织机构列表
     * 默认返回正常状态的管理员组织机构列表
     * @param array $where 查询条件,供where()方法使用
     *
     * @return array 返回组织机构列表
     */
    public function getGroups($where = array(),$field=true)
    {
        $map = array('status' => 1, 'id' => array('neq', AuthGroupModel::GROUP_ADMIN), 'module' => 'admin');
        $map = array_merge($map, $where);
        // return $this->relation('_roles')->where($map)->select();
        $list = $this->field($field)->where($map)->select();
        return $list;
    }

    /**
     * 获取组织机构详细信息
     * @param  int $id 组织ID或标识
     * @param  boolean $field 查询字段
     * @return array     组织信息
     * @author liuhui
     */
    public function info($id, $field = true)
    {
        /* 获取组织信息 */
        $map = array();
        if (is_numeric($id)) { //通过ID查询
            $map['id'] = $id;
        } else { //通过标识查询
            $map['title'] = $id;
        }
        return $this->field($field)->where($map)->find();
    }

    /**
     * 获取组织机构树，指定组织则返回指定组织极其子组织，不指定则返回所有组织树
     * @param  integer $id 组织ID
     * @param  boolean $field 查询字段
     * @return array          组织树
     */
    public function getTree($id = 0, $field = true)
    {
        //TODO liu hui where
        if ($id) {
            $where=array('id'=>$id);
        }
        /* 获取所有组织 */
        $map = array('status' => array('EGT', -1), 'id' => array('neq', AuthGroupModel::GROUP_ADMIN));
        $map=array_merge($where,$map);
        $list = $this->field($field)->where($map)->order('id')->select();
        $list = int_to_string($list, array('status' => array(1 => '正常', -1 => '删除', 0 => '禁用', 2 => '未审核', 3 => '草稿'), 'public' => array(1 => '公共', 0 => '私有'), 'type' => array(1 => '管理员', 2 => '商户', 3 => '用户')));

        /*非超管级管理员只列出拥有权限的组织*/
        if (!IS_ROOT) {
            /*获取当前用户所拥有的组织*/
            foreach ($list as $key => $data) {
                if (!in_array($data['id'], $this->UserAuthGroup())) {
                    unset($list[$key]);
                    continue;
                }
            }

            $userGroup = D('Tree')->toTree($list, $pk = 'id', $pid = 'pid', $child = '_child');

            return $userGroup;
        } else {
            /* 获取当前组织信息 */
            if ($id) {
                $info = $this->info($id);
                $id = $info['id'];
            }
            $list = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = $id);
            /* 获取返回数据 */
            if (isset($info)) { //指定组织则返回当前组织极其子组织
                $info['_child'] = $list;
            } else { //否则返回所有组织
                $info = $list;
            }
            return $info;
        }
    }

    /**
     * 更新组织信息
     * @return boolean 更新状态
     */
    public function update()
    {
        $data = $this->create();
        if (!$data) { //数据对象创建错误
            return false;
        }
        /* 添加或更新数据 */
        if (empty($data['id'])) {

            /* 权限控制*/
            if (!IS_ROOT) {
                $pid = I('pid');
                if ($pid == 0) {
                    $this->error = '权限不足,请联系管理员';
                    return false;
                }
                if ($pid != 0 && !(in_array($pid, $this->UserAuthGroup()))) {
                    $this->error = '权限不足，请联系管理员';
                    return false;
                }
            }

            $res = $this->add();
        } else {

            /* 权限控制*/
            if (!IS_ROOT) {
                if (!(in_array($data['id'], $this->UserAuthGroup()))) {
                    $this->error = '权限不足，请联系管理员';
                    return false;
                }
            }
            $res = $this->save();
        }

        return $res;
    }

    /**
     * 保存区域
     */
    public function saveRegion($result)
    {
        /*保存区域*/
        $region_id = 0;
        for ($i = 6; $i > 0; $i--) {
            $temp = I('level' . $i);
            if ($temp != 0 && !empty($temp)) {
                $region_id = $temp;
                break;
            }
        }
        if ($region_id == 0) {
            $this->error = '请选择区域';
            return false;
        }
        $GroupRegion = M('AuthGroupRegion');
        $data['group_id'] = $result;
        $data['region_id'] = $region_id;
        return $GroupRegion->add($data);
    }

    /**
     * 返回拥有权限的组织
     * @return array 权限数组
     * @author liuhui
     */
    public function UserAuthGroup()
    {

        $userGroup = S(UID . 'AUTH_GROUP');
        if (empty($userGroup)) {
            $map = array('status' => array('EGT', -1), 'id' => array('neq', AuthGroupModel::GROUP_ADMIN));
            $list = $this->field('id,pid')->where($map)->order('id')->select();

            $AuthAccess = D('AuthAccess');
            $userGroup = $AuthAccess->getUserGroup(UID);

            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data['id']] = $data;
            }

            foreach ($userGroup as $key => &$item) {
                $item = $refer[$item['group_id']];
                if ($child = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = $item['id'])) {
                    $item['_child'] = $child;
                }
            }
            $userGroup = array_unique(array_column(tree_to_list($userGroup, $child = '_child', $order = 'id'), 'id'));

            S(UID . 'AUTH_GROUP', $userGroup);
        }
        return $userGroup;
    }

    /**
     * @return array|mixed
     */
    public function UserGroupFormat()
    {
        $FormatGroup = S(UID . 'AUTH_GROUP_FORMAT');
        if (empty($FormatGroup)) {
            $FormatGroup = $this->getGroups();
            $Tree = D('Tree');
            $FormatGroup = $Tree->toFormatTree($FormatGroup);

            if (!IS_ROOT) {
                /*获取当前用户所拥有权限的组织*/
                foreach ($FormatGroup as $key => $data) {
                    if (!in_array($data['id'], $this->UserAuthGroup())) {
                        unset($FormatGroup[$key]);
                        continue;
                    }
                }
            }
            S(UID . 'AUTH_GROUP_FORMAT', $FormatGroup);
        }
        return $FormatGroup;
    }
}

