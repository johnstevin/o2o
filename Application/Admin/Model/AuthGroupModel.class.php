<?php


namespace Admin\Model;

use Think\Model;

/**
 * 用户组模型类
 * Class AuthGroupModel
 * @author liuhui
 */
class AuthGroupModel extends Model
{
    const GROUP_ADMIN = 1;                   // 超级管理员用户组类型标识

    protected $_validate = array(
        array('title', 'require', '必须设置用户组标题', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        //array('title','require', '必须设置用户组标题', Model::EXISTS_VALIDATE  ,'regex',Model::MODEL_INSERT),
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
     * 返回用户组列表
     * 默认返回正常状态的管理员用户组列表
     * @param array $where 查询条件,供where()方法使用
     *
     * @return array 返回用户组列表
     */
    public function getGroups($where = array())
    {
        $map = array('status' => 1, 'id' => array('neq', AuthGroupModel::GROUP_ADMIN), 'module' => 'admin');
        $map = array_merge($map, $where);
       // return $this->relation('_roles')->where($map)->select();
        return $this->where($map)->select();
    }

    /**
     * 获取用户组详细信息
     * @param  milit $id 分类ID或标识
     * @param  boolean $field 查询字段
     * @return array     分类信息
     * @author liuhui
     */
    public function info($id, $field = true)
    {
        /* 获取分类信息 */
        $map = array();
        if (is_numeric($id)) { //通过ID查询
            $map['id'] = $id;
        } else { //通过标识查询
            $map['name'] = $id;
        }
        return $this->field($field)->where($map)->find();
    }

    /**
     * 获取分类树，指定分类则返回指定分类极其子分类，不指定则返回所有分类树
     * @param  integer $id 分类ID
     * @param  boolean $field 查询字段
     * @return array          分类树
     */
    public function getTree($id = 0, $field = true)
    {
        /* 获取当前分类信息 */
        if ($id) {
            $info = $this->info($id);
            $id = $info['id'];
        }

        /* 获取所有分类 */
        $map = array('status' => array('gt', -1));
        $list = $this->field($field)->where($map)->order('id')->select();
        $list = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = $id);

        /* 获取返回数据 */
        if (isset($info)) { //指定分类则返回当前分类极其子分类
            $info['_'] = $list;
        } else { //否则返回所有分类
            $info = $list;
        }

        return $info;
    }

    /**
     * 更新角色信息
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
            $res = $this->add();
        } else {
            $res = $this->save();
        }

        return $res;
    }

    /**
     * 保存区域
     */
    public function saveRegion($result){
        /*保存区域*/
        $region_id=0;
        for($i=6;$i>0;$i--){
            $temp=I('level'.$i);
            if( $temp!= 0&&!empty($temp)){
                $region_id=$temp;break;
            }
        }
        if ($region_id == 0) {
            $this->error='请选择区域';
            return false;
        }
        $GroupRegion = M('AuthGroupRegion');
        $data['group_id'] = $result;
        $data['region_id'] = $region_id;
        return $GroupRegion->add($data);
    }
}

