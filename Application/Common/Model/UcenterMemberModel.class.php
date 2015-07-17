<?php
namespace Common\Model;

use Think\Model\AdvModel;

class UcenterMemberModel extends AdvModel
{
    protected $pk = 'id';
    protected $autoinc = true;
    protected static $model;
    ## 状态常量
    const STATUS_ACTIVE = 1;//正常

    protected $_validate = [
        ['real_name', '', '真实姓名已存在', self::EXISTS_VALIDATE, 'unique'],
        ['email', '', '邮箱已存在', self::EXISTS_VALIDATE, 'unique'],

    ];

    /**
     * 获取当前模型实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return UcenterMemberModel
     */
    public static function getInstance()
    {
        return (self::$model instanceof self) ? self::$model : self::$model = new self;
    }

    /**
     * 检测用户是否存在
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id
     * @return bool
     */
    public static function checkUserExist($id)
    {
        $id = intval($id);
        return ($id !== 0 && self::get($id, 'id')) ? true : false;
    }

    /**
     * 获取用户
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @param int $id 用户ID
     * @param string|array $fileds 要查询的字段
     * @return mixed|null
     */
    public static function get($id, $fileds = '*')
    {
        $id = intval($id);
        return $id ? self::getInstance()->where(['id' => $id])->field($fileds)->find() : null;
    }


}