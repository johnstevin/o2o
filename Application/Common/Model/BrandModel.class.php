<?php
namespace Common\Model;

use Think\Model\AdvModel;

/**
 * 品牌模型
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @package Common\Model
 */
class BrandModel extends AdvModel
{
    protected static $model;

    /**
     * 获取当前模型的实例
     * @author Fufeng Nie <niefufeng@gmail.com>
     * @return BrandModel
     */
    public static function getInstance()
    {
        return self::$model instanceof self ? self::$model : self::$model = new self;
    }
}