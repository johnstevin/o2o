<?php

namespace Common\Model;

use Think\Model\RelationModel;

/**
 * 用户模型
 */
class MemberModel extends RelationModel
{
    protected static $model;
    ## 状态常量
    const STATUS_DELETE = -1;
    const STATUS_LOCK = 0;
    const STATUS_ACTIVE = 1;

    protected $_validate = [
        ['nickname', '1,16', '昵称长度为1-16个字符', self::EXISTS_VALIDATE, 'length'],
        ['nickname', '', '昵称被占用', self::EXISTS_VALIDATE, 'unique'], //用户名被占用

    ];

    public function lists($status = 1, $order = 'uid DESC', $field = true)
    {
        $map = ['status' => $status];
        return $this->field($field)->where($map)->order($order)->select();
    }

    /**
     * 获得当前模型的实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return MemberModel
     */
    public static function getInstance()
    {
        return self::$model instanceof self ? self::$model : self::$model = new self;
    }

    /**
     * 检测用户是否合法
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param $id
     * @return bool
     */
    public static function checkUserExist($id)
    {
        $id = intval($id);
        return ($id && self::getById($id, self::STATUS_ACTIVE, 'uid')) ? true : false;
    }

    /**
     * 根据ID查找用户
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 用户ID
     * @param null|int $status 用户状态
     * @param string|array $fileds 要查询的字段
     * @return null|array
     */
    public static function getById($id, $status = null, $fileds = '*')
    {
        $where['uid'] = $id;
        $where['status'] = ($status && in_array($status, array_keys(self::getStatusOptions()))) ? $status : self::STATUS_ACTIVE;
        return self::getInstance()->where($where)->field($fileds)->find() ?: null;
    }

    /**
     * 获得所有用户状态
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return array
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_DELETE => '删除',
            self::STATUS_ACTIVE => '正常',
            self::STATUS_LOCK => '锁定'
        ];
    }

    public function getMemberInfos( $mapUid, $field = '*' ) {
        try{
            $userInfo=$this
                ->field($field)
                ->table('__UCENTER_MEMBER__ a')
                ->join('__MEMBER__ b ON  a.id = b.uid','LEFT')
                ->where(array('a.is_member'=>array('eq', '1'),'b.status'=>array('eq', '1'),'a.id'=>$mapUid))
                ->select();
            if(empty($userInfo))
                E(-1);
            return $userInfo;

        }catch (\Exception $ex){
            return $ex->getMessage();
        }
    }

    public function saveInfo ($data) {
        try {

            empty($data) ? E('修改字段不能为空') : '';
            $data = $this->create($data);
            if(empty($data))
                E('创建对象失败');
            if(empty($data['uid'])){
                $id = $this->add();
                if(!$id)
                    E('新增失败');
            } else {
                $status = $this->save();
                if(false === $status)
                    E('更新失败');
            }


        } catch (\Exception $ex) {

            return $ex->getMessage();

        }
    }

}
