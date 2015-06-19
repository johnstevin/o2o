<?php


namespace Admin\Model;
use Think\Model;
/**
 * 权限规则模型
 * @author liuhui
 */
class AuthRuleModel extends Model{
    
    const RULE_URL = 1;
    const RULE_MAIN = 2;
    const AUTH_ROLE_RULE         = 'auth_role_rule'; // 关系表表名
    protected $_validate = array(
        array('title','require', '必须设置规则标题', Model::MUST_VALIDATE ,'regex',Model::MODEL_INSERT),
    );
    /**
     * 获取规则详细信息
     * @param  milit   $id 规则ID或标识
     * @param  boolean $field 查询字段
     * @return array     规则信息
     * @author liuhui
     */
    public function info($id, $field = true){
        /* 获取规则信息 */
        $map = array();
        if(is_numeric($id)){ //通过ID查询
            $map['id'] = $id;
        } else { //通过标识查询
            $map['name'] = $id;
        }
        return $this->field($field)->where($map)->find();
    }

    /**
     * 获取规则树，指定规则则返回指定规则极其子规则，不指定则返回所有规则树
     * @param  integer $id    规则ID
     * @param  boolean $field 查询字段
     * @return array          规则树
     */
    public function getTree($id = 0, $field = true){
        /* 获取当前规则信息 */
        if($id){
            $info = $this->info($id);
            $id   = $info['id'];
        }

        /* 获取所有规则 */
        $map  = array('status' => array('gt', -1));
        $list = $this->field($field)->where($map)->order('id')->select();
        $list = int_to_string($list, array('status' => array(1 => '正常', -1 => '删除', 0 => '禁用')));
        $list = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = $id);

        /* 获取返回数据 */
        if(isset($info)){ //指定规则则返回当前规则极其子规则
            $info['_child'] = $list;
        } else { //否则返回所有规则
            $info = $list;
        }

        return $info;
    }

    /**
     * 更新规则信息
     * @return boolean 更新状态
     */
    public function update(){
        $data = $this->create();
        if(!$data){ //数据对象创建错误
            return false;
        }

        /* 添加或更新数据 */
        if(empty($data['id'])){
            $res = $this->add();
        }else{
            $res = $this->save();
        }

        return $res;
    }

    /**
     * @param  检查id是否全部存在
     * @param $mid
     * @param string $msg
     * @return array|string $gid  规则id列表
     */
    public function checkId($modelname,$mid,$msg = '以下id不存在:'){
        if(is_array($mid)){
            $count = count($mid);
            $ids   = implode(',',$mid);
        }else{
            $mid   = explode(',',$mid);
            $count = count($mid);
            $ids   = $mid;
        }

        $s = M($modelname)->where(array('id'=>array('IN',$ids)))->getField('id',true);
        if(count($s)===$count){
            return true;
        }else{
            $diff = implode(',',array_diff($mid,$s));
            $this->error = $msg.$diff;
            return false;
        }
    }

    /**
     * 检查规则id是否全部存在
     * @param $mid
     * @return array|string
     */
    public function checkRuleId($mid){
        return $this->checkId('AuthRule',$mid, '以下角色组id不存在:');
    }

    /**
     * 把角色添加到规则组,支持批量添加角色到规则组
     * @param 角色id
     * @param 规则id
     * @return bool
     * 示例: 把Role_id=1的角色添加到Rule_id为1,2的组 `AuthRoleModel->addToRule(1,'1,2');`
     */
    public function addToRule($uid,$gid){
        $uid = is_array($uid)?implode(',',$uid):trim($uid,',');
        $gid = is_array($gid)?$gid:explode( ',',trim($gid,',') );

        $Access = M(self::AUTH_ROLE_RULE);
        if( isset($_REQUEST['batch']) ){
            //为单个用户批量添加规则时,先删除旧数据
            $del = $Access->where( array('role_id'=>array('in',$uid)) )->delete();
        }
        $uid_arr = explode(',',$uid);
        //$uid_arr = array_diff($uid_arr,array(C('USER_ADMINISTRATOR')));
        $add = array();
        if( $del!==false ){
            foreach ($uid_arr as $u){
                //判断用户id是否合法
                if(M('AuthRole')->getFieldByUid($u,'id') == false){
                    $this->error = "编号为{$u}的角色不存在！";
                    return false;
                }
                foreach ($gid as $g){
                    if( is_numeric($u) && is_numeric($g) ){
                        $add[] = array('rule_id'=>$g,'role_id'=>$u);
                    }
                }
            }
            $Access->addAll($add);
        }
        if ($Access->getDbError()) {
            if( count($uid_arr)==1 && count($gid)==1 ){
                //单个添加时定制错误提示
                $this->error = "不能重复添加";
            }
            return false;
        }else{
            return true;
        }
    }

}
