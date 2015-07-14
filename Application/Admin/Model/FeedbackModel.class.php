<?php

namespace Admin\Model;
use Think\Model;
/**
 * 反馈模型
 * @author liuhui
 */

class FeedbackModel extends Model
{
    protected $_validate = array(
        array('description', 'require', '描述不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
    );

    protected $_auto = array(
        array('user_id', UID, self::MODEL_INSERT),
        array('create_ip','get_client_ip', self::MODEL_INSERT, 'function', 1),
        array('edit_ip', 'get_client_ip', self::MODEL_BOTH, 'function', 1),
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('edit_time', NOW_TIME, self::MODEL_BOTH),
        array('status', '0', self::MODEL_BOTH),
    );

    /**
     * *获取详细信息
     * @param int $id 反馈id
     * @param bool $field 查询字段
     * @return mixed
     */
    public function info($id, $field = true)
    {
        $map = array();
        $map['id'] = $id;
        $Feedback= $this->field($field)->where($map)->find();
        $Feedback['_picture'] = M('Picture')->where(array('id' => $Feedback['picture_id']))->find();
        return $Feedback;
    }

    /**
     * 更新信息
     * @return boolean 更新状态
     * @author liu hui
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
     * @return bool
     */
    public function offfeedback(){
        return $this->where(array('id' => $_REQUEST['id']))->setField('status', 2);
    }
}