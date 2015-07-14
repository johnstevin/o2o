<?php

namespace Admin\Model;

use Think\Model;

/**
 * 反馈回复模型
 * @author liuhui
 */
class FeedbackReplyModel extends Model
{
    protected $_validate = array(
        array('fid', 'require', '系统错误', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
    );

    protected $_auto = array(
        array('sys_uid', UID, self::MODEL_INSERT),
        array('reply_ip', 'get_client_ip', self::MODEL_INSERT, 'function', 1),
        array('reply_time', NOW_TIME, self::MODEL_INSERT),
    );

    /**
     * *获取详细信息
     * @param int $id 反馈id
     * @param bool $field 查询字段
     * @return mixed
     */
    public function feedbackdetail($id, $field = true)
    {
        $map = array();
        $map['fid'] = $id;
        $reply = $this->field($field)->order('reply_time')->where($map)->select();
        foreach ($reply as &$key) {
            $key['show_name'] = M('UcenterMember')->where(array('id' => $key['sys_uid']))->getField('mobile');

            if (!empty($key['show_name'])) {
                $key['show_name'] = substr_replace($key['show_name'], '*****',3,5);
            }
        }
        return $reply;
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


            if ($data['from_uid'] != UID) {
                M('Feedback')->where(array('id' => $data['fid']))->setField('status', 1);
            }

            $res = $this->add();
        } else {
            $res = $this->save();
        }
        return $res;
    }
}