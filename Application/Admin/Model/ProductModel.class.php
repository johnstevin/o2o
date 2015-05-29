<?php
/**
 * Created by PhpStorm.
 * User: liuhui
 * Date: 5/28/15
 * Time: 4:05 PM
 */
namespace Admin\Model;
use \Think\Model;

class ProductModel extends Model{

    /**
     * 自动验证
     * @var array
     */
    protected $_validata=array(
        array('title','require', '必须设置商品标题', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        array('price','currency','商品价格必须是货币形式',self::MUST_VALIDATE,'regex',self::MODEL_INSERT),
    );

    /**
     * 自动验证
     * @var array
     */
    protected $_auto=array(
        array('add_time', NOW_TIME, self::MODEL_INSERT),
        array('add_ip', 'get_client_ip', self::MODEL_INSERT, 'function', 1),
        array('edit_time', NOW_TIME),
    );

    /**
     * 关联模型
     * @var array
     */
    protected $_link=array(
        'brand'=>array(
            'class_name' => 'Brand',
            'parent_key' => 'brand_id',
            'mapping_name' => '_brand',
            'mapping_order' => 'sort desc',
        ),
        'norms'=>array(
            'class_name' => 'norms',
            'parent_key' => 'norms_id',
            'mapping_name' => '_norms',
        ),
    );

    /**
     * 获取商品列表
     */
    public function lists($where,$order,$field){

        return $this->field($field)->select();
    }

    /**
     * 获取用户组详细信息
     * @param  milit $id 分类ID或标识
     * @param  boolean $field 查询字段
     * @return array     分类信息
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
     * 更新信息
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
}