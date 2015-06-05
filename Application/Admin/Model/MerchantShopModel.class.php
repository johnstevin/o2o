<?php

namespace Common\Model;
use Think\Model\AdvModel;

class MerchantShopModel extends AdvModel{
    protected $_validate = array(



    );

    protected $_auto = array(



    );

    /**
     * 列表
     * @param null $status
     * @param null $group_id
     * @param string $field
     * @return mixed
     */
    public function lists($status = null, $group_id = null, $field = '*'){
        $map = array();
        $status === null   ? : $map['status']   = $status;
        $group_id === null ? : $map['group_id'] = $group_id;
        $result = $this->where($map)->field($field)->select();
        return $result;
    }

    /**
     * 详情
     * @param int $id
     * @param string $field
     * @return mixed
     */
    public function info($id = 0, $field = '*'){
        $id  = (int)($id);
        $map = array();
        $map['id'] = $id;
        return $this->where($map)->field($field)->find();
    }

}