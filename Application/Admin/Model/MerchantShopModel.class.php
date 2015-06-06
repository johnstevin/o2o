<?php

namespace Common\Model;

use Think\Model\AdvModel;

class MerchantShopModel extends AdvModel
{
    protected $_validate = array();

    protected $_auto = array();

    /**
     * 列表
     * @param null $status
     * @param null $group_id
     * @param string $field
     * @return mixed
     */
    public function lists($status = null, $group_id = null, $field = '*')
    {
        $map = array();
        $status === null ?: $map['status'] = $status;
        $group_id === null ?: $map['group_id'] = $group_id;
        $result = $this->where($map)->field($field)->select();
        return $result;
    }

    /**
     * 详情
     * @param int $id
     * @param string $field
     * @return mixed
     */
    public function info($id = 0, $field = '*')
    {
        $id = (int)($id);
        $map = array();
        $map['id'] = $id;
        return $this->where($map)->field($field)->find();
    }

    /**
     * @param  商家id
     * @return bool 保存成功返回true ，反之false
     */
    public function saveCheckInfo($shop_id)
    {
        //检查商户是否存在
        $info = $this->info($shop_id, 'id,title,description,group_id,type,pid,add_uid,region_id');
        if (empty($info)) {
            $this->error = '没有此商铺';
            return false;
        }
        //TODO liuhui 事物控制
        M()->startTrans();
        //保存组织包括区域以及权限信息


        $Group = D('AuthGroup');
        $pidGroup = $Group->info($info['group_id']);
        if (empty($pidGroup)) {
            $this->error = '未找到上级商铺';
            M()->rollback();
            return false;
        }
        $data['title'] = $info['title'];
        $data['description'] = $info['description'];
        $data['level'] = $pidGroup['level'] + 1;
        $data['pid'] = $info['group_id'];
        $data['public'] = 1;
        $data['status'] = 1;
        $data['type'] = $info['type'];
        $Group->create($data);

        // 保存组织
        $res = $Group->add($data);

        //保存组织区域
        if (false !== $res) {
            $GroupRegion = M('AuthGroupRegion');
            $Region['group_id'] = $res;
            $Region['region_id'] = $info['region_id'];
            //return $GroupRegion->add($Region);

            if (false !== $GroupRegion->add($Region)) {

                //保存角色、组织和权限关系
                $arr[$res][] = $info['role_id'];
                //$arrs = is_array($arr) ? $arr : explode(',', trim($arr, ','));
                $AuthRole = D('AuthRole');

                if (false !== $AuthRole->addToRole($info['add_uid'], $arr)) {
                    //修改状态
                    $info['status'] = 1;
                    if (false !== $this->save($info)) {
                        M()->commit();
                        return true;
                    } else {
                        $this->error = '修改状态失败';
                        M()->rollback();
                        return false;
                    };
                } else {
                    $this->error = '保存权限失败';
                    M()->rollback();
                    return false;
                };
            } else {
                $this->error = '保存区域失败';
                M()->rollback();
                return false;
            }
        } else {
            $this->error = '保存组织失败';
            M()->rollback();
            return false;
        }
    }

    /**
     * @param int $shop_id 商家id
     * @return bool|void
     */
    public function saveUnPassReason($shop_id)
    {
        //修改状态
        $data['id'] = $shop_id;
        $data['message'] = I('reason');
        $data['status'] = 0;
        return $this->save($data);
    }

    /**
     * 审核商家资料要保存商家对应的组织以及组织对应角色
     * @param array $info 商家表中的一条记录
     * @return bool|mixed
     */
    public function saveGroupAndAuth($info)
    {
        $Group = D('AuthGroup');
        $pidGroup = $Group->info($info['group_id']);
        if (empty($pidGroup)) {
            $this->error = '未找到上级商铺';
            return false;
        }
        $data['title'] = $info['title'];
        $data['description'] = $info['description'];
        $data['level'] = ($pidGroup['level'] + 1);
        $data['pid'] = $info['group_id'];
        $data['public'] = 1;
        $data['status'] = 1;
        $data['type'] = $info['type'];
        $Group->create($data);

        // 保存组织
        $res = $Group->add($data);

        //保存组织区域
        if (false !== $res) {
            $GroupRegion = M('AuthGroupRegion');
            $Region['group_id'] = $res;
            $Region['region_id'] = $info['region_id'];
            //return $GroupRegion->add($Region);


            if (false !== $GroupRegion->add($Region)) {


                //保存角色、组织和权限关系
                $arr[$res][] = $info['role_id'];
                //$arrs = is_array($arr) ? $arr : explode(',', trim($arr, ','));
                $AuthRole = D('AuthRole');

                return $AuthRole->addToRole($info['add_uid'], $arr);
            } else {
                $this->error = '保存区域失败';
                return false;
            }
        } else {
            $this->error = '保存组织失败';
            return false;
        }
    }


}