<?php

namespace Admin\Model;

use Think\Model\AdvModel;

class MerchantShopModel extends AdvModel
{
    protected $_validate = array();

    protected $_auto = array();

    /**
     * 列表
     * @param null $status
     * @param null $group_id
     * @param bool $field
     * @return mixed
     */
    public function lists($status = null, $group_id = null, $field = true)
    {
        $map = array();
        $status === null ?: $map['status'] = $status;
        $group_id === null ?: $map['group_id'] = $group_id;
        //筛选出属于他自己的区域
        if (!IS_ROOT) {
            $authRegion = S('AUTH_ADMIN_REGION');
            //print_r($authRegion);
            $Region_arr = is_array($authRegion) ? $authRegion : explode(',', trim($authRegion, ','));
            $where = array('region_id' => array('in', $Region_arr));
            $map = array_merge($map, $where);
        }



        /*分页*/
        $total = $this->where($map)->count();

        if (isset($REQUEST['r'])) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        }

        $page = new \Think\Page($total, $listRows);

        $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');

        $options['limit'] = $page->firstRow . ',' . $page->listRows;

        $this->setProperty('options', $options);





        $result = $this->where($map)->order('id desc')->field($field)->select();
//        return $result;

          return [
              'data' => $result,
              '_page' => $page->show()
          ];
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
        $MerchantShop= $this->where($map)->field($field)->find();
        $MerchantShop['_picture'] = M('Picture')->where(array('id' => $MerchantShop['picture']))->find();
        $MerchantShop['_yyzz_picture'] = M('Picture')->where(array('id' => $MerchantShop['yyzz_picture']))->find();
        $MerchantShop['_spwsxkz_picture'] = M('Picture')->where(array('id' => $MerchantShop['spwsxkz_picture']))->find();
        $MerchantShop['_id_cart_front_picture'] = M('Picture')->where(array('id' => $MerchantShop['id_cart_front_picture']))->find();
        $MerchantShop['_id_cart_back_picture'] = M('Picture')->where(array('id' => $MerchantShop['id_cart_back_picture']))->find();
        return $MerchantShop;
    }

    /**
     * @param  商家id
     * @return bool 保存成功返回true ，反之false
     */
    public function saveCheckInfo($shop_id)
    {
        //检查商户是否存在
        $info = $this->info($shop_id, 'id,title,description,group_id,type,add_uid,region_id');
        if (empty($info)) {
            $this->error = '没有此商铺';
            return false;
        }

        //保存组织包括区域以及权限信息

        $Group = D('AuthGroup');
        $pidGroup = $Group->info($info['group_id']);
        if (empty($pidGroup)) {
            $this->error = '未找到上级商铺';
            return false;
        }
        //检查商铺区域是否在他的权限范围内
        if (!IS_ROOT) {
            $authRegion = S('AUTH_ADMIN_REGION');
            if (!in_array($info['region_id'], $authRegion)) {
                $this->error = '该商铺不属你你的管辖范围内';
                return false;
            }
        }
        //TODO liuhui 事物控制
        M()->startTrans();

        $data['title'] = $info['title'];
        $data['description'] = $info['description'];
        $data['level'] = $pidGroup['level'] + 1;
        $data['pid'] = $info['group_id'];
        $data['public'] = 1;
        $data['status'] = 1;
        $data['type'] = C('auth_group_type')['MERCHANT'];
        $Group->create($data);

        // 保存组织
        $res = $Group->add($data);

        //保存组织区域
        if (false !== $res) {
            $GroupRegion = M('AuthGroupRegion');
            $Region['group_id'] = $res;
            $Region['region_id'] = $info['region_id'];

            if (false !== $GroupRegion->add($Region)) {

                //保存角色、组织和权限关系
                $arr[$res][]=  typeToRole($info['type']);
                $AuthAccess = D('AuthAccess');

                if (false !== $AuthAccess->addToRole($info['add_uid'], $arr)) {
                    //修改状态
                    $info['status'] = 1;
                    $info['group_id'] = $res;
                    if (false !== $this->save($info)) {
                        M()->commit();
                        return true;
                    } else {
                        $this->error = '修改状态失败';
                        M()->rollback();
                        return false;
                    }
                } else {
                    $this->error = '保存权限失败';
                    M()->rollback();
                    return false;
                }
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
        $data['status'] = 3;
        return $this->save($data);
    }

}