<?php
// +----------------------------------------------------------------------
// | Created by stevin.
// +----------------------------------------------------------------------
// | Date: 2015-5-25
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Think\Controller;

class AdminController extends Controller
{

    public function _empty()
    {

    }

    protected function _initialize()
    {
        // 获取当前用户ID
        if (defined('UID')) return;
        define('UID', is_admin_login());
        if (!UID) {// 还没登录 跳转到登录页面
            $this->redirect('Public/login');
        }

        $this->isOnline(UID, time());

        /* 读取数据库中的配置 */
        $config = S('DB_CONFIG_DATA');
        if (!$config) {
            $config = api('Config/lists');
            S('DB_CONFIG_DATA', $config);
        }
        C($config);

        define('IS_ROOT', is_administrator());
        if (!IS_ROOT && C('ADMIN_ALLOW_IP')) {
            // 检查IP地址访问
            if (!in_array(get_client_ip(), explode(',', C('ADMIN_ALLOW_IP')))) {
                $this->error('403:禁止访问');
            }
        }

        if (!IS_ROOT) {
            $access = $this->accessControl();
            if (false === $access) {
                $this->error('403:禁止访问');
            } elseif (null === $access) {
                $rule = strtolower(CONTROLLER_NAME . '/' . ACTION_NAME);
                $checkType = array('in', '1,2');  //TODO
                if (!$this->checkRule($rule, $checkType)) {
                    $this->error('未授权访问!');
                } else {
                    $dynamic = $this->checkDynamic();
                    if (false === $dynamic) {
                        $this->error('未授权访问!');
                    }
                }
            }

            //把区域放进缓存
            $region = S(UID.'AUTH_ADMIN_REGION');
            if (empty($region)) {
                $Region = D('Region');
               S(UID.'AUTH_ADMIN_REGION', $Region->subordinate());
            }

        }
        $this->assign('menu_list', json_encode($this->getMenus()));
    }

    /**
     * @param array array 条件
     * @return array  获取菜单
     */
    public function getMenus($where = array())
    {
        $menus = session('_AUTH_ADMIN_MENU_LIST');
        if (empty($menus)) {
            $AuthRule = D('AuthRule');
            $map = array('status' => '1', 'hide' => '1', 'level' => array('ELT', 1));
            $map = array_merge($map, $where);
            $AuthList = $_SESSION['_AUTH_LIST_' . UID . 'in,1,2'];
            $menus = $AuthRule->where($map)->order('sort asc')->field('id,title as text,pid as fid,url')->select();
            if (!IS_ROOT) {
                foreach ($menus as $key => $item) {
                    //  检测菜单权限
                    if (!(in_array(strtolower($item['url']), $AuthList))) {
                        unset($menus[$key]);
                        continue;
                    }
                }
            }
            $menus= list_to_tree($menus, 'id', 'fid', 'children');
            session('_AUTH_ADMIN_MENU_LIST', $menus);
        }
        return $menus;
    }


    /**
     * action访问控制,在 **登陆成功** 后执行的第一项权限检测任务
     *
     * @return boolean|null  返回值必须使用 `===` 进行判断
     *
     *   返回 **false**, 不允许任何人访问(超管除外)
     *   返回 **true**, 允许任何管理员访问,无需执行节点权限检测
     *   返回 **null**, 需要继续执行节点权限检测决定是否允许访问
     */
    final protected function accessControl()
    {
        $allow = C('ALLOW_VISIT');
        $deny = C('DENY_VISIT');
        $check = strtolower(CONTROLLER_NAME . '/' . ACTION_NAME);
        if (!empty($deny) && in_array_case($check, $deny)) {
            return false;//非超管禁止访问deny中的方法
        }
        if (!empty($allow) && in_array_case($check, $allow)) {
            return true;
        }
        return null;//需要检测节点权限
    }

    /**
     * 权限检测
     * @param string $rule 检测的规则
     * @param string $mode check模式
     * @return boolean
     */
    final protected function checkRule($rule, $type, $mode = 'url')
    {
        static $Auth = null;
        if (!$Auth) {
            $Auth = new \Think\Auth();
        }
        if (!$Auth->check($rule, UID, $type, $mode)) {
            return false;
        }
        return true;
    }

    /**
     * 检测是否是需要动态判断的权限
     * @return boolean|null
     *      返回true则表示当前访问有权限
     *      返回false则表示当前访问无权限
     *      返回null，则表示权限不明
     */
    protected function checkDynamic()
    {
    }

    /**
     * 通用分页列表数据集获取方法
     *
     *  可以通过url参数传递where条件,例如:  index.html?name=param
     *  可以通过url空值排序字段和方式,例如: index.html?_field=id&_order=asc
     *  可以通过url参数r指定每页数据条数,例如: index.html?r=5
     *
     * @param sting|Model $model 模型名或模型实例
     * @param array $where where查询条件(优先级: $where>$_REQUEST>模型设定)
     * @param array|string $order 排序条件,传入null时使用sql默认排序或模型属性(优先级最高);
     *                              请求参数中如果指定了_order和_field则据此排序(优先级第二);
     *                              否则使用$order参数(如果$order参数,且模型也没有设定过order,则取主键降序);
     *
     * @param boolean $field 单表模型用不到该参数,要用在多表join时为field()方法指定参数
     * @return array|false
     * 返回数据集
     */
    protected function lists($model, $where = array(), $order = '', $field = true)
    {
        $options = array();
        $REQUEST = (array)I('request.');
        if (is_string($model)) {
            $model = M($model);
        }

        $OPT = new \ReflectionProperty($model, 'options');
        $OPT->setAccessible(true);

        $pk = $model->getPk();
        if ($order === null) {
            //order置空
        } else if (isset($REQUEST['_order']) && isset($REQUEST['_field']) && in_array(strtolower($REQUEST['_order']), array('desc', 'asc'))) {
            $options['order'] = '`' . $REQUEST['_field'] . '` ' . $REQUEST['_order'];
        } elseif ($order === '' && empty($options['order']) && !empty($pk)) {
            $options['order'] = $pk . ' desc';
        } elseif ($order) {
            $options['order'] = $order;
        }
        unset($REQUEST['_order'], $REQUEST['_field']);

        if (empty($where)) {
            $where = array('status' => array('egt', -1));
        }
        if (!empty($where)) {
            $options['where'] = $where;
        }
        $options = array_merge((array)$OPT->getValue($model), $options);
        $total = $model->where($options['where'])->count();

        if (isset($REQUEST['r'])) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        }
//        $page = new \Think\Page($total, $listRows, $REQUEST['r']);
        $page = new \Think\Page($total, $listRows);
        //if ($total > $listRows) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
       // }

        foreach($where as $key=>$val) {
            $page->parameter[$key]   =   urlencode($val);
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $options['limit'] = $page->firstRow . ',' . $page->listRows;

        $model->setProperty('options', $options);

        return $model->field($field)->select();
    }

    /**
     * 对数据表中的单行或多行记录执行修改 GET参数id为数字或逗号分隔的数字
     *
     * @param string $model 模型名称,供M函数使用的参数
     * @param array $data 修改的数据
     * @param array $where 查询时的where()方法的参数
     * @param array $msg 执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     */
    final protected function editRow($model, $data, $where, $msg)
    {
        $id = array_unique((array)I('id', 0));
        $id = is_array($id) ? implode(',', $id) : $id;
        //如存在id字段，则加入该条件
        $fields = M($model)->getDbFields();
        if (in_array('id', $fields) && !empty($id)) {
            $where = array_merge(array('id' => array('in', $id)), (array)$where);
        }
        // 是否是超级管理员

        $msg = array_merge(array('success' => '操作成功！', 'error' => '操作失败！', 'url' => '', 'ajax' => IS_AJAX), (array)$msg);
        if (M($model)->where($where)->save($data) !== false) {



            //记录行为
            action_log('admin_update_status', $model, $id, UID,1);


            $this->success($msg['success'], $msg['url'], $msg['ajax']);
        } else {
            $this->error($msg['error'], $msg['url'], $msg['ajax']);
        }
    }

    /**
     * 禁用条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array $where 查询时的 where()方法的参数
     * @param array $msg 执行正确和错误的消息,可以设置四个元素 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     */
    protected function forbid($model, $where = array(), $msg = array('success' => '状态禁用成功！', 'error' => '状态禁用失败！'))
    {
        $data = array('status' => 0);
        $this->editRow($model, $data, $where, $msg);
    }

    /**
     * 恢复条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array $where 查询时的where()方法的参数
     * @param array $msg 执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     */
    protected function resume($model, $where = array(), $msg = array('success' => '状态恢复成功！', 'error' => '状态恢复失败！'))
    {
        $data = array('status' => 1);
        $this->editRow($model, $data, $where, $msg);
    }

    /**
     * 还原条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array $where 查询时的where()方法的参数
     * @param array $msg 执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     */
    protected function restore($model, $where = array(), $msg = array('success' => '状态还原成功！', 'error' => '状态还原失败！'))
    {
        $data = array('status' => 1);
        $where = array_merge(array('status' => -1), $where);
        $this->editRow($model, $data, $where, $msg);
    }

    /**
     * 条目假删除
     * @param string $model 模型名称,供D函数使用的参数
     * @param array $where 查询时的where()方法的参数
     * @param array $msg 执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     */
    protected function delete($model, $where = array(), $msg = array('success' => '删除成功！', 'error' => '删除失败！'))
    {
        $data['status'] = -1;
        $this->editRow($model, $data, $where, $msg);
    }

    /**
     * 设置一条或者多条数据的状态
     */
    public function setStatus($Model = CONTROLLER_NAME)
    {

        $ids = I('request.ids');
        $status = I('request.status');
        if (empty($ids)) {
            $this->error('请选择要操作的数据');
        }

        $map['id'] = array('in', $ids);
        switch ($status) {
            case -1 :
                $this->delete($Model, $map, array('success' => '删除成功', 'error' => '删除失败'));
                break;
            case 0  :
                $this->forbid($Model, $map, array('success' => '禁用成功', 'error' => '禁用失败'));
                break;
            case 1  :
                $this->resume($Model, $map, array('success' => '启用成功', 'error' => '启用失败'));
                break;
            default :
                $this->error('参数错误');
                break;
        }
    }

    /**
     * AJAX模板返回
     * @param  string $templete 渲染后的模板
     * @param  string $info 反馈信息
     * @param  int $status 状态 1-成功，0-失败
     * @return json
     * @author Stevin.John <stevin.john@qq.com>
     */
    private function ajaxTempReturn($templete = '', $info = '', $status = 0)
    {
        $data['data'] = $templete;
        $data['info'] = $info;
        $data['status'] = $status;
        $this->ajaxReturn($data);
    }

    /**
     * @param    $uid
     * @author   Stevin.John@qq.com
     */
    public function isOnline ( $uid, $ac_time ) {
        $res = S('ADMIN_ONLINE_'.$uid);
        !$res ? $this->error('超时！请重新登陆') : '';
        $res['token'] === session('admin_auth')['token'] ? : $this->error('您的账号已在其它地方登陆，请重新登陆');
        $res['last_login_time'] = $ac_time;
        S('ADMIN_ONLINE_'.$uid, $res, 1200);
    }

}
