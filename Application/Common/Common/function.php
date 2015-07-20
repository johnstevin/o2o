<?php
use Common\Model\PictureModel;

// OneThink常量定义
const SQ_VERSION    = '1.1.141212';
const SQ_ADDON_PATH = './Addons/';
/**
 * 检查IP是否合法
 * @param string $ip 要检查的IP地址
 * @return bool
 */
function checkIpFormat($ip)
{
    return filter_var($ip, FILTER_VALIDATE_IP) ? true : false;
}

/**
 * 获取IP4地址数字
 * @return int
 */
function get_client_ip_to_int()
{
    return get_client_ip(1, true);
}


/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key 加密密钥
 * @param int $expire 过期时间 单位 秒
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function think_encrypt($data, $key = '', $expire = 0)
{
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = base64_encode($data);
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    $str = sprintf('%010d', $expire ? $expire + time() : 0);

    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
    }
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($str));
}

/**
 * 系统解密方法
 * @param  string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
 * @param  string $key 加密密钥
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function think_decrypt($data, $key = '')
{
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = str_replace(['-', '_'], ['+', '/'], $data);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $data = base64_decode($data);
    $expire = substr($data, 0, 10);
    $data = substr($data, 10);

    if ($expire > 0 && $expire < time()) {
        return '';
    }
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = $str = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        } else {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return base64_decode($str);
}


/**
 * 构建关键字查询条件帮助函数
 * @author WangJiang
 * @param string $words 关键字，多个用','隔开
 * @param string $words_op 'or|and' 关键字逻辑关系，缺省为‘or’
 * @param array $flds 参与查询的字段
 * @param array $map TP查询条件，返回值
 * @return None
 */
function build_words_query($words, $words_op, $flds, &$map)
{
    //TODO:奇葩问题，传入的参数是'or'时，TP会转换成'or '
    $words_op = trim($words_op);

    $nw = count($words);
    $nf = count($flds);
    $where_kws = null;
    for ($i = 0; $i < $nf; $i++) {
        $val = [];
        for ($j = 0; $j < $nw; $j++) {
            $val[] = ['like', '%' . $words[$j] . '%'];
        }
        $val[] = $words_op;
        //$val['_logic']='or';
        $where_kws[$flds[$i]] = $val;
    }
    $where_kws['_logic'] = 'or';
    $map['_complex'] = $where_kws;
    return $map;
}

/**
 * 对查询结果集进行排序
 * @access public
 * @param array $list 查询结果
 * @param string $field 排序的字段名
 * @param string $sortby 排序类型
 * asc正向排序 desc逆向排序 nat自然排序
 * @return array
 */
function list_sort_by($list, $field, $sortby = 'asc')
{
    if (is_array($list)) {
        $refer = $resultSet = [];
        foreach ($list as $i => $data)
            $refer[$i] = &$data[$field];
        switch ($sortby) {
            case 'asc': // 正向排序
                asort($refer);
                break;
            case 'desc':// 逆向排序
                arsort($refer);
                break;
            case 'nat': // 自然排序
                natcasesort($refer);
                break;
        }
        foreach ($refer as $key => $val)
            $resultSet[] = &$list[$key];
        return $resultSet;
    }
    return false;
}

/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pk 主键名称
 * @param string $pid 父级键名
 * @param string $child 子级键名
 * @param int $root 开始的根ID
 * @return array
 */
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0)
{
    // 创建Tree
    $tree = [];
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = [];
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
            $refer[$data[$pk]][$child] = [];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[] =& $list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][] =& $list[$key];
                }
            }
        }
    }
    return $tree;
}

/**
 * 将list_to_tree的树还原成列表
 * @param  array $tree 原来的树
 * @param  string $child 孩子节点的键
 * @param  string $order 排序显示的键，一般是主键 升序排列
 * @param  array $list 过渡用的中间数组，
 * @return array        返回排过序的列表数组
 */
function tree_to_list($tree, $child = '_child', $order = 'id', &$list = [])
{
    if (is_array($tree)) {
        foreach ($tree as $key => $value) {
            $reffer = $value;
            if (isset($reffer[$child])) {
                unset($reffer[$child]);
                tree_to_list($value[$child], $child, $order, $list);
            }
            $list[] = $reffer;
        }
        $list = list_sort_by($list, $order, $sortby = 'asc');
    }
    return $list;
}

/**
 * 处理插件钩子
 * @param string $hook   钩子名称
 * @param mixed $params 传入参数
 * @return void
 */
function hook($hook,$params=array()){
    \Think\Hook::listen($hook,$params);
}

/**
 * 获取插件类的类名
 * @param strng $name 插件名
 */
function get_addon_class($name){
    $class = "Addons\\{$name}\\{$name}Addon";
    return $class;
}

/**
 * 获取插件类的配置文件数组
 * @param string $name 插件名
 */
function get_addon_config($name){
    $class = get_addon_class($name);
    if(class_exists($class)) {
        $addon = new $class();
        return $addon->getConfig();
    }else {
        return array();
    }
}

/**
 * 插件显示内容里生成访问插件的url
 * @param string $url url
 * @param array $param 参数
 * @author liu hui
 */
function addons_url($url, $param = array()){
    $url        = parse_url($url);
    $case       = C('URL_CASE_INSENSITIVE');
    $addons     = $case ? parse_name($url['scheme']) : $url['scheme'];
    $controller = $case ? parse_name($url['host']) : $url['host'];
    $action     = trim($case ? strtolower($url['path']) : $url['path'], '/');

    /* 解析URL带的参数 */
    if(isset($url['query'])){
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }

    /* 基础参数 */
    $params = array(
        '_addons'     => $addons,
        '_controller' => $controller,
        '_action'     => $action,
    );
    $params = array_merge($params, $param); //添加额外参数

    return U('Addons/execute', $params);
}

/**
 * 检测用户是否存在
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @param int $id 用户ID
 * @return bool
 */
function check_user_exist($id)
{
    return \Common\Model\MemberModel::checkUserExist($id);
}

/**
 * 检测商家是否存在
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @param int $id 商家ID
 * @return bool
 */
function check_merchant_exist($id)
{
    return \Common\Model\MerchantModel::checkMerchantExist($id);
}

/**
 * 检测用户中心是否存在这个用户
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @param int $id 用户ID
 * @return bool
 */
function check_ucenter_member_exist($id)
{
    return \Common\Model\UcenterMemberModel::checkUserExist($id);
}

/**
 * 检测商家父级
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @param int $id 父级ID
 * @return bool
 */
function check_merchant_parent_exist($id)
{
    return \Common\Model\MerchantModel::checkMerchantPidExist($id);
}

/**
 * 检测商铺是否存在
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @param int $id 商铺ID
 * @todo 待完善
 * @return bool
 */
function check_shop_exist($id)
{
    return true;
}

/**
 * 检测商品是否存在
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @param int $id 商品ID
 * @return bool
 */
function check_product_exist($id)
{
    return \Common\Model\ProductModel::checkProductExist($id);
}

/**
 * 检测订单号是否存在
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @param string $id
 * @return bool
 */
function check_order_exist($id)
{
    return \Common\Model\OrderModel::checkOrderExist($id);
}

function check_order_vehicle_exist($id)
{

    return !empty((new \Common\Model\OrderVehicleModel)->find($id));
}

/**
 * 检测分类是否存在
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @param int $id
 * @return bool
 */
function check_category_exist($id)
{
    return \Common\Model\CategoryModel::checkCategoryExist($id);
}

/**
 * 检查区域ID是否存在
 * @author Fufeng Nie <niefufeng@gmail.com>
 *
 * @param int $id 区域ID
 * @return bool
 */
function check_region_exist($id)
{
    return \Common\Model\RegionModel::getInstance()->checkIdExists($id);
}

/**
 * 生成订单代码
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @return string
 */
function create_order_code()
{
    return 'S' . date('YmdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
}

/**
 * 生成洗车订单（因为模型的自动完成没法传参数，所以写成了两个方法）
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @return string
 */
function create_vehide_order_code()
{
    return 'V' . date('YmdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
}

/**
 * 检测当前用户是否为管理员
 * @param int $uid 用户ID
 * @return boolean true-管理员，false-非管理员
 */
function is_administrator($uid = null)
{
    $uid = is_null($uid) ? is_admin_login() : $uid;
    return $uid && (intval($uid) === C('USER_ADMINISTRATOR'));
}

/**
 * 时间戳格式化
 * @param int $time
 * @param string $format 格式化的格式
 * @return string 完整的时间显示
 */
function time_format($time = NULL, $format = 'Y-m-d H:i')
{
    $time = $time === NULL ? NOW_TIME : intval($time);
    return date($format, $time);
}

/**
 * 数据签名认证
 * @param  array $data 被认证的数据
 * @return string       签名
 */
function data_auth_sign($data)
{
    //数据类型检测
    if (!is_array($data)) {
        $data = (array)$data;
    }
    ksort($data); //排序
    $code = http_build_query($data); //url编码并生成query字符串
    $sign = sha1($code); //生成签名
    return $sign;
}

/**
 * @return int
 */
function is_admin_login()
{
    $user = session('admin_auth');
    if (empty($user)) {
        return 0;
    } else {
        return session('admin_auth_sign') == data_auth_sign($user) ? $user['uid'] : 0;
    }
}

/**
 * @param string $token TOKEN
 * @return int
 */
function is_merchant_login($token)
{
    $user = F('User/Login/merchant_auth' . $token);
    if (empty($user)) {
        return 0;
    } else {
        return F('User/Login/merchant_auth_sign' . $token) == data_auth_sign($user) ? $user['uid'] : 0;
    }
}

/**
 * 设置Merchant登录状态
 * @author WangJiang
 * @param $token
 * @param $auth
 */
function set_merchant_login($token, $auth)
{
    F('User/Login/merchant_auth' . $token, $auth);
    F('User/Login/merchant_auth_sign' . $token, data_auth_sign($auth));
}

/**
 * 清除Merchant登录状态
 * @author WangJiang
 * @param $token
 */
function clear_merchant_login($token)
{
    F('User/Login/merchant_auth' . $token, null);
    F('User/Login/merchant_auth_sign' . $token, null);
}

/**
 * @param string $token TOKEN
 * @return int
 */
function is_member_login($token)
{
    $user = F('User/Login/member_auth' . $token);
    if (empty($user)) {
        return 0;
    } else {
        return F('User/Login/member_auth_sign' . $token) == data_auth_sign($user) ? $user['uid'] : 0;
    }
}

/**
 * 设置Member登录状态
 * @author WangJiang
 * @param $token
 * @param $auth
 */
function set_member_login($token, $auth)
{
    F('User/Login/member_auth' . $token, $auth);
    F('User/Login/member_auth_sign' . $token, data_auth_sign($auth));
}

/**
 * 清除Member登录状态
 * @author WangJiang
 * @param $token
 */
function clear_member_login($token)
{
    F('User/Login/member_auth' . $token, null);
    F('User/Login/member_auth_sign' . $token, null);
}

/**
 * @param int $length
 * @return string
 */
function generate_saltKey($length = 6)
{
    // 密码字符集，可任意添加你需要的字符
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_[]{}<>~`+=,.;:/?|';
    $saltKey = '';
    for ($i = 0; $i < $length; $i++) {
        $saltKey .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $saltKey;
}

/**
 * @param $pwd
 * @param $saltkey
 * @return string
 */
function generate_password($pwd, $saltkey)
{
    //Md5(Md5(盐值前三位.md5(密码).盐值后几位).盐值)，取最中间24位
    $saltkey1 = substr($saltkey, 0, 3);
    $saltkey2 = substr($saltkey, -3);
    $pwd = md5(md5($saltkey1 . $pwd . $saltkey2) . $saltkey);
    $pwd = substr($pwd, 4, 24);

    return $pwd;
}

/**
 * 调用系统的API接口方法（静态方法）
 * api('User/getName','id=5'); 调用公共模块的User接口的getName方法
 * api('Admin/User/getName','id=5');  调用Admin模块的User接口
 * @param  string $name 格式 [模块名]/接口名/方法名
 * @param  array|string $vars 参数
 */
function api($name, $vars = [])
{
    $array = explode('/', $name);
    $method = array_pop($array);
    $classname = array_pop($array);
    $module = $array ? array_pop($array) : 'Common';
    $callback = $module . '\\Api\\' . $classname . 'Api::' . $method;
    if (is_string($vars)) {
        parse_str($vars, $vars);
    }
    return call_user_func_array($callback, $vars);
}

/**
 * select返回的数组进行整数映射转换
 *
 * @param array $map 映射关系二维数组  array(
 *                                          '字段名1'=>array(映射关系数组),
 *                                          '字段名2'=>array(映射关系数组),
 *                                           ......
 *                                       )
 * @return array
 *
 *  array(
 *      array('id'=>1,'title'=>'标题','status'=>'1','status_text'=>'正常')
 *      ....
 *  )
 *
 */
function int_to_string(&$data, $map = ['status' => [1 => '正常', -1 => '删除', 0 => '禁用', 2 => '未审核', 3 => '草稿']])
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array)$data;
    foreach ($data as $key => $row) {
        foreach ($map as $col => $pair) {
            if (isset($row[$col]) && isset($pair[$row[$col]])) {
                $data[$key][$col . '_text'] = $pair[$row[$col]];
            }
        }
    }
    return $data;
}

/**
 * 通过数值构建SQL参数绑定
 * @author WangJiang
 * @param array $list 条件数组
 * @param array $bindValues
 * @param string $prefix 参数名称前缀
 * @return array [$bindNames,$bindValues] $bindNames 参数名称, $bindValues参数绑定用于bind调用
 */
function build_sql_bind($list, $bindValues = [], $prefix = 'bindName')
{
    foreach ($list as $i => $id) {
        $name = ":$prefix" . $i;
        $bindNames[] = $name;
        $bindValues[$name] = $id;
    }
    return [$bindNames, $bindValues];
}

/**
 * 记录行为日志，并执行该行为的规则
 * @param string $action 行为标识
 * @param string $model 触发行为的模型名
 * @param int $record_id 触发行为的记录id
 * @param int $user_id 执行行为的用户id
 * @param int $type 类型：类型（1-管理员，2-商家，3-用户）
 * @return boolean
 */
function action_log($action = null, $model = null, $record_id = null, $user_id = null, $type = null)
{

    //参数检查
    if (empty($action) || empty($model) || empty($record_id) || empty($user_id) || empty($type)) {
        return '参数不能为空';
    }

    //查询行为,判断是否执行
    $action_info = M('Action')->getByName($action);
    if ($action_info['status'] != 1) {
        return '该行为被禁用或删除';
    }

    //插入行为日志
    $data['action_id'] = $action_info['id'];
    $data['user_id'] = $user_id;
    $data['action_ip'] = ip2long(get_client_ip());
    $data['model'] = $model;
    $data['record_id'] = $record_id;
    $data['create_time'] = NOW_TIME;
    $data['type'] = $type;

    //解析日志规则,生成日志备注
    if (!empty($action_info['log'])) {
        if (preg_match_all('/\[(\S+?)\]/', $action_info['log'], $match)) {
            $log['user'] = $user_id;
            $log['record'] = $record_id;
            $log['model'] = $model;
            $log['time'] = NOW_TIME;
            $log['data'] = ['user' => $user_id, 'model' => $model, 'record' => $record_id, 'time' => NOW_TIME];
            foreach ($match[1] as $value) {
                $param = explode('|', $value);
                if (isset($param[1])) {
                    $replace[] = call_user_func($param[1], $log[$param[0]]);
                } else {
                    $replace[] = $log[$param[0]];
                }
            }
            $data['remark'] = str_replace($match[0], $replace, $action_info['log']);
        } else {
            $data['remark'] = $action_info['log'];
        }
    } else {
        //未定义日志规则，记录操作url
        $data['remark'] = '操作url：' . $_SERVER['REQUEST_URI'];
    }

    M('ActionLog')->add($data);

    if (!empty($action_info['rule'])) {
        //解析行为
        $rules = parse_action($action, $user_id);

        //执行行为
        $res = execute_action($rules, $action_info['id'], $user_id);
    }
}

/**
 * 解析行为规则
 * 规则定义  table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
 * 规则字段解释：table->要操作的数据表，不需要加表前缀；
 *              field->要操作的字段；
 *              condition->操作的条件，目前支持字符串，默认变量{$self}为执行行为的用户
 *              rule->对字段进行的具体操作，目前支持四则混合运算，如：1+score*2/2-3
 *              cycle->执行周期，单位（小时），表示$cycle小时内最多执行$max次
 *              max->单个周期内的最大执行次数（$cycle和$max必须同时定义，否则无效）
 * 单个行为后可加 ； 连接其他规则
 * @param string $action 行为id或者name
 * @param int $self 替换规则里的变量为执行用户的id
 * @return boolean|array: false解析出错 ， 成功返回规则数组
 */
function parse_action($action = null, $self)
{
    if (empty($action)) {
        return false;
    }

    //参数支持id或者name
    if (is_numeric($action)) {
        $map = ['id' => $action];
    } else {
        $map = ['name' => $action];
    }

    //查询行为信息
    $info = M('Action')->where($map)->find();
    if (!$info || $info['status'] != 1) {
        return false;
    }

    //解析规则:table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
    $rules = $info['rule'];
    $rules = str_replace('{$self}', $self, $rules);
    $rules = explode(';', $rules);
    $return = [];
    foreach ($rules as $key => &$rule) {
        $rule = explode('|', $rule);
        foreach ($rule as $k => $fields) {
            $field = empty($fields) ? [] : explode(':', $fields);
            if (!empty($field)) {
                $return[$key][$field[0]] = $field[1];
            }
        }
        //cycle(检查周期)和max(周期内最大执行次数)必须同时存在，否则去掉这两个条件
        if (!array_key_exists('cycle', $return[$key]) || !array_key_exists('max', $return[$key])) {
            unset($return[$key]['cycle'], $return[$key]['max']);
        }
    }

    return $return;
}

/**
 * 执行行为
 * @param array|bool $rules 解析后的规则数组
 * @param int $action_id 行为id
 * @param array $user_id 执行的用户id
 * @return boolean false 失败 ， true 成功
 */
function execute_action($rules = false, $action_id = null, $user_id = null)
{
    if (!$rules || empty($action_id) || empty($user_id)) {
        return false;
    }

    $return = true;
    foreach ($rules as $rule) {

        //检查执行周期
        $map = ['action_id' => $action_id, 'user_id' => $user_id];
        $map['create_time'] = ['gt', NOW_TIME - intval($rule['cycle']) * 3600];
        $exec_count = M('ActionLog')->where($map)->count();
        if ($exec_count > $rule['max']) {
            continue;
        }

        //执行数据库操作
        $Model = M(ucfirst($rule['table']));
        $field = $rule['field'];
        $res = $Model->where($rule['condition'])->setField($field, ['exp', $rule['rule']]);

        if (!$res) {
            $return = false;
        }
    }
    return $return;
}

/**
 * @param int $uid
 * @return string
 * @author stevin.john
 */
function getUcenterMobile($uid = 0)
{
    static $list;
    if (!($uid && is_numeric($uid))) { //获取当前登录用户名
        return '×未知用户×';
    }

    /* 获取缓存数据 */
    if (empty($list)) {
        $list = S('sys_user_mobile_list');
    }

    /* 查找用户信息 */
    $key = "u{$uid}";
    if (isset($list[$key])) { //已缓存，直接使用
        $name = $list[$key];
    } else { //调用接口获取用户信息
        $info = M('UcenterMember')->field('mobile')->find($uid);
        if ($info !== false && $info['mobile']) {
            $mobile = $info['mobile'];
            $name = $list[$key] = $mobile;
            /* 缓存用户 */
            $count = count($list);
            $max = C('USER_MAX_CACHE');
            while ($count-- > $max) {
                array_shift($list);
            }
            S('sys_user_mobile_list', $list);
        } else {
            $name = '';
        }
    }
    return $name;
}

/**
 * 加密token
 * @author WangJiang
 * @param string $token
 * @return string
 */
function encode_token($token)
{
    return $token;
}

/**
 * 解密token
 * @author WangJiang
 * @param string $token
 * @return string
 */
function decode_token($token)
{
    return $token;
}

/**
 * 验证用户是否允许修改商铺数据，不满足条件抛异常
 * @author WangJiang
 * @param int $uid 用户ID
 * @param int $sid 商铺ID
 */
function can_modify_shop($uid, $sid)
{
    if (is_array($sid)) {
        foreach ($sid as $i) {
            $shop = D('MerchantShop')->find($i);
            except_merchant_manager($uid, $shop['group_id']);
        }
    } else {
        $shop = D('MerchantShop')->find($sid);
        except_merchant_manager($uid, $shop['group_id']);
    }
}

/**
 * 验证是否能修改仓库的商品
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @param int $uid 用户ID
 * @param int $depotId 仓库商品ID
 */
function can_modify_depot($uid, $depotId)
{
    if ($depot = \Common\Model\MerchantDepotModel::getInstance()->getById($depotId, ['shop_id'])) {
        except_merchant_manager($uid, $depot['shop_id']);
    } else {
        E('您无权限操作该商品');
    }
}

/**
 * 批量验证用户是否能修改（读取）仓库的商品
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @param int $uid 用户ID
 * @param int|string|array $depotId 仓库ID
 */
function bacth_check_can_modify_depot($uid, $depotId)
{
    $shops = \Common\Model\MerchantDepotModel::getInstance()->alias('md')->field(['ms.group_id'])->group('ms.group_id')->join('LEFT JOIN sq_merchant_shop ms ON ms.id=md.shop_id')->where(['md.id' => ['IN', (array)$depotId]])->select();
    if (!$shops || count($shops) > 1) {
        E('没有权限修改商品或者不能同时修改多个店铺的商品');
    }
    except_merchant_manager($uid, current($shops)['group_id']);
}

/**
 * 是否店长或者管理员，抛异常
 * @author WangJiang
 * @param int $uid 用户ID
 * @param int $gid 分组ID
 */
function except_merchant_manager($uid, $gid)
{
    if (!D('AuthAccess')->where(['uid' => $uid, 'group_id' => $gid, 'role_id' =>array('in',[ C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_SHOP_MANAGER'), C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_MANAGER')])])->find())
        E('用户无权限操作该店铺');
}

class DBException extends RuntimeException
{
    public $errorCode;
    public $errorInfo;

    public function __constructor($errorCode, $errorInfo)
    {
        $this->$errorCode = $errorCode;
        $this->$errorInfo = $errorInfo;
    }
}

/**
 * 提交事务
 * @author WangJiang
 * @param $data
 */
function do_transaction($data, $success = null)
{
    /*
     * $data = [['sql'=>'<sql>','bind'=>[<bounds>],'newId'=><true|false>]...]
     */
    $dbh = new \PDO(C('DB_TYPE') . ':host=' . C('DB_HOST') . ';dbname=' . C('DB_NAME') . ';port=' . C('DB_PORT'),
        C('DB_USER'), C('DB_PWD'), [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"]);
    $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

    $newid = null;
    try {
        if (false == $dbh->beginTransaction())
            throw new DBException($dbh->errorCode(), $dbh->errorInfo());
        foreach ($data as $i) {
            $stmt = $dbh->prepare($i['sql']);
            $r = $stmt->execute($i['bind']);
            if ($r == false)
                throw new DBException($dbh->errorCode(), $dbh->errorInfo());
            if ($i['newId'])
                $newid = $dbh->lastInsertId();
        }
        if (false == $dbh->commit())
            throw new DBException($dbh->errorCode(), $dbh->errorInfo());

        if (is_callable($success)) {
            try {
                call_user_func($success);
            } catch (\Exception $e) {
            }
        }

        return $newid;
    } catch (\Exception $e) {
        $dbh->rollBack();
        throw $e;
    } finally {
        unset($dbh);
    }
}

/**
 * array_column兼容性处理
 */
if (!function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = null)
    {
        $result = [];
        if (null === $indexKey) {
            if (null === $columnKey) {
                $result = array_values($input);
            } else {
                foreach ($input as $row) {
                    $result[] = $row[$columnKey];
                }
            }
        } else {
            if (null === $columnKey) {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row;
                }
            } else {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row[$columnKey];
                }
            }
        }
        return $result;
    }
}

/**
 * curl模拟POST请求
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @param array $data 要发送的POST数据
 * @param string $url 要请求的链接
 * @return mixed
 */
function send_post($data, $url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

/**
 * curl模拟GET请求
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @param string $url 请求的链接
 * @return mixed
 */
function send_get($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

/**
 * 获取PDO对象
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @return PDO
 */
function get_pdo()
{
    static $pdo = null;
    return $pdo instanceof PDO ? $pdo : $pdo = new PDO(C('DB_TYPE') . ':host=' . C('DB_HOST') . ';dbname=' . C('DB_NAME') . ';charset=' . C('DB_CHARSET') . ';port' . C('DB_PORT'), C('DB_USER'), C('DB_PWD'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
}

/**
 * 根据type返回不同角色id,在给用户初始化角色时用
 * eg: $role= typeToRole(type);
 * @param int $type 商家类型
 * @return int 角色id
 */
function typeToRole($type)
{
    if ($type == 1) {
        /* 返回店长id */
        return C('AUTH_ROLE_ID')['ROLE_ID_MERCHANT_SHOP_MANAGER'];
    } else {
        /* 返回管理员id */
        return C('AUTH_ROLE_ID')['ROLE_ID_MERCHANT_VEHICLE_MANAGER'];
    }
}

/**
 * 获取单个汉字拼音首字母。
 * 注意:此处不要纠结。汉字拼音是没有以U和V开头的
 * @param $s0
 * @return null|string
 */
function getfirstchar($s0)
{
    $fchar = ord($s0{0});
    if ($fchar >= ord("A") and $fchar <= ord("z")) return strtoupper($s0{0});
    $s1 = iconv("UTF-8", "gb2312", $s0);
    $s2 = iconv("gb2312", "UTF-8", $s1);
    if ($s2 == $s0) {
        $s = $s1;
    } else {
        $s = $s0;
    }
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if ($asc >= -20319 and $asc <= -20284) return "A";
    if ($asc >= -20283 and $asc <= -19776) return "B";
    if ($asc >= -19775 and $asc <= -19219) return "C";
    if ($asc >= -19218 and $asc <= -18711) return "D";
    if ($asc >= -18710 and $asc <= -18527) return "E";
    if ($asc >= -18526 and $asc <= -18240) return "F";
    if ($asc >= -18239 and $asc <= -17923) return "G";
    if ($asc >= -17922 and $asc <= -17418) return "H";
    if ($asc >= -17922 and $asc <= -17418) return "I";
    if ($asc >= -17417 and $asc <= -16475) return "J";
    if ($asc >= -16474 and $asc <= -16213) return "K";
    if ($asc >= -16212 and $asc <= -15641) return "L";
    if ($asc >= -15640 and $asc <= -15166) return "M";
    if ($asc >= -15165 and $asc <= -14923) return "N";
    if ($asc >= -14922 and $asc <= -14915) return "O";
    if ($asc >= -14914 and $asc <= -14631) return "P";
    if ($asc >= -14630 and $asc <= -14150) return "Q";
    if ($asc >= -14149 and $asc <= -14091) return "R";
    if ($asc >= -14090 and $asc <= -13319) return "S";
    if ($asc >= -13318 and $asc <= -12839) return "T";
    if ($asc >= -12838 and $asc <= -12557) return "W";
    if ($asc >= -12556 and $asc <= -11848) return "X";
    if ($asc >= -11847 and $asc <= -11056) return "Y";
    if ($asc >= -11055 and $asc <= -10247) return "Z";
    return NULL;
    //return $s0;
}

/**
 * 获取整条字符串汉字拼音首字母
 * @param $zh
 * @return string
 */
function pinyin_long($zh)
{
    $ret = "";
    $s1 = iconv("UTF-8", "gb2312", $zh);
    $s2 = iconv("gb2312", "UTF-8", $s1);
    if ($s2 == $zh) {
        $zh = $s1;
    }
    for ($i = 0; $i < strlen($zh); $i++) {
        $s1 = substr($zh, $i, 1);
        $p = ord($s1);
        if ($p > 160) {
            $s2 = substr($zh, $i++, 2);
            $ret .= getfirstchar($s2);
        } else {
            $ret .= $s1;
        }
    }
    return $ret;
}

/**
 * 构建距离查询语句
 * @author WangJiang
 * @param $lng
 * @param $lat
 * @param $distance
 * @param $bind
 * @return string
 */
function build_distance_sql_where($lng, $lat, $distance, &$bind, $lnglatField = 'lnglat')
{
    $sql = "ST_Distance_Sphere($lnglatField,POINT(:lng,:lat))<:dist";
    $bind[':lng'] = $lng;
    $bind[':lat'] = $lat;
    $bind[':dist'] = $distance;
    return $sql;
}

function upload_picture($uid, $type)
{
    $type = strtoupper($type);
    //print_r("{$type}_PICTURE_UPLOAD");
    //print_r($_FILES);
    /* 调用文件上传组件上传文件 */
    $Picture = new PictureModel();
    $pic_driver = C('PICTURE_UPLOAD_DRIVER');
    $info = $Picture->upload(
        $uid,
        $_FILES,
        C("{$type}_PICTURE_UPLOAD"),
        C('PICTURE_UPLOAD_DRIVER'),
        C("UPLOAD_{$pic_driver}_CONFIG")
    );

    if ($info == false)
        E($Picture->getError());

    return $info;
}


/**
 * 短线验证码接口 >>
 */

/**
 * 获得缓存的验证码
 * @author WangJiang
 * @param $mobile
 * @return mixed
 */
function get_sms_code($mobile)
{
    $ck = "verify_code_$mobile";
    return S($ck, '', ['expire' => C('VERIFY_CODE_EXPIRE')]);
}

/**
 * 缓存验证码
 * @author WangJiang
 * @param $mobile
 * @param $code
 */
function set_sms_code($mobile, $code)
{
    $ck = "verify_code_$mobile";
    S($ck, $code, ['expire' => C('VERIFY_CODE_EXPIRE')]);
}

/**
 * 发送验证码
 * @author WangJiang
 * @param $mobile
 * @return string
 */
function send_sms_code($mobile)
{
    require_once(__ROOT__ . 'Addons/Sms/Common/function.php');
    if (get_sms_code($mobile) !== false)
        E(C('VERIFY_CODE_EXPIRE') . '秒内不能重复获取');
    $code = [];
    while (count($code) < 6) {
        $code[] = rand(1, 9);
    }
    $code = implode('', $code);
    set_sms_code($mobile, $code);
    \Addons\Sms\Common\send_code([$mobile], $code);
    return $code;
}

/**
 * 检查验证码
 * @author WangJiang
 * @param $mobile
 * @param $code
 * @return bool
 */
function verify_sms_code($mobile, $code)
{
    //add by WangJiang
    //开发模式下，不检查验证码，不推送消息
    if (!empty(C('DEVELOPMENT_MODE')))
        return true;
//    var_dump($mobile);
//    var_dump($code);
//    var_dump(get_sms_code($mobile));
//    var_dump(get_sms_code(get_sms_code($mobile)==$code));
///    die;
    return get_sms_code($mobile) == $code;
}

/**
 * >> 短线验证码接口
 */

/**
 * 从上级分组获得分组链
 * @author WangJiang
 * @param $ids
 * @param $ret
 * @param null $catem
 */
function get_cate_chain_down($ids, &$ret, $catem = null)
{
    if (is_null($catem))
        $catem = D('Category');
    foreach ($ids as $id) {
        $cate = $catem->find($id);
        if ($cate['pid'] == 0) {
            break;
        }
        if (!in_array($cate['pid'], $ret)) {
            $ret[] = $cate['pid'];
        }
        $pids[] = $cate['pid'];
    }
    if ($pids)
        get_cate_chain_down($pids, $ret, $catem);
}

function get_cate_chain_up($ids, &$ret, $catem = null)
{
    if (is_null($catem))
        $catem = D('Category');
    foreach ($ids as $id) {

    }
}

/**
 * 根据商铺ID获取店长
 * @author Fufeng Nie <niefufeng@gmail.com>
 *
 * @param int $shopId 商铺ID
 * @return null
 */
function get_shopkeeper_by_shopid($shopId)
{
    $pdo = get_pdo();
    $sth = $pdo->prepare('SELECT group_id FROM sq_merchant_shop WHERE id=:id');
    $sth->execute([':id' => $shopId]);
    if (!$shop = $sth->fetch(PDO::FETCH_ASSOC)) E('商铺不存在');
    $groupId = $shop['group_id'];
    $sth = $pdo->prepare('SELECT uid FROM sq_auth_access WHERE group_id=:group_id AND role_id=:role_id');
    $sth->execute([':group_id' => $groupId, ':role_id' => C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_SHOP_MANAGER')]);
    if ($access = $sth->fetch(PDO::FETCH_ASSOC)) {
        return $access['uid'];
    }
    return null;
}

/**
 * 根据用户ID推送消息
 * @author Fufeng Nie <niefufeng@gmail.com>
 *
 * @param integer $appid 应用ID，可传【STORE】和【CLIENT】
 * @param int $uid 用户ID
 * @param string $message_content 消息内容
 * @param string|null $message_title 消息标题
 * @param string|null $message_type 消息类型
 * @param string|null $notification_content 通知内容
 * @param string|null $notification_title 通知标题
 * @param string|null $category 通知分类（IOS8+可用）
 * @param array $extras 附加参数
 * @return bool
 */
function push_by_uid($appid, $uid, $notification_content, $extras = [], $notification_title = null, $message_content = '', $message_title = null, $category = null, $message_type = null)
{
    //add by WangJiang
    //开发模式下，不检查验证码，不推送消息
    if (!empty(C('DEVELOPMENT_MODE')))
        return;
    //return \Addons\Push\Push::getInstance(C('PUSH_TYPE'), $appid)->pushByUid($uid, $message_content, $message_title, $message_type, $notification_content, $notification_title, $extras, $category);
}

/**
 * 更新设备的标签或别名
 * @author Fufeng Nie <niefufeng@gmail.com>
 *
 * @param string $registrationId 登记ID
 * @param null|string $alias 别名
 * @param null|array $addTags 要添加的标签
 * @param null|array $removeTags 要删除的标签
 * @return \JPush\Model\DeviceResponse
 */
function update_device_tag_alias($appid, $registrationId, $alias = null, $addTags = null, $removeTags = null)
{
    //add by WangJiang
    //开发模式下，不检查验证码，不推送消息
    if (!empty(C('DEVELOPMENT_MODE')))
        return;
//    return \Addons\Push\Push::getInstance(C('PUSH_TYPE'), $appid)->updateDeviceTagAlias($registrationId, $alias, $addTags, $removeTags);
}

/**
 * 根据设备推送消息
 * @author Fufeng Nie <niefufeng@gmail.com>
 *
 * @param string $appId 用户ID，目前有【STORE】和【CLIENT】两个
 * @param string|array $platform 设备，可传【all】、【ios】、【android】和【winphone】，可传多个
 * @param string $notification_content 通知内容
 * @param array $extras 扩展信息
 * @param null|string $notification_title 通知标题
 * @param null|string $message_content 消息内容
 * @param null|string $message_title 消息标题
 * @param null $category 消息分类（仅IOS8+有效）
 * @return bool
 */
function push_by_platform($appId, $platform, $notification_content, $extras = [], $notification_title = null, $message_content = '', $message_title = null, $category = null)
{
//    return \Addons\Push\Push::getInstance(C('PUSH_TYPE'), $appId)->pushByPlatform($platform, $notification_content, $extras, $notification_title, $message_content, $message_title, $category);
}

/**
 * 删除设备的别名
 * @author Fufeng Nie <niefufeng@gmail.com>
 *
 * @param string $registrationId 设备注册ID
 * @param string $appId 应用ID
 * @return \JPush\Model\DeviceResponse
 */
function remove_device_alias($appId, $registrationId)
{
//    return \Addons\Push\Push::getInstance(C('PUSH_TYPE'), $appId)->removeDeviceAlias($registrationId);
}

/**
 * 删除设备的标签
 * @author Fufeng Nie <niefufeng@gmail.com>
 *
 * @param string $appId 应用ID
 * @param string $registrationId 设备注册ID
 * @return \JPush\Model\DeviceResponse
 */
function remove_device_tag($appId, $registrationId)
{
//    return \Addons\Push\Push::getInstance(C('PUSH_TYPE'), $appId)->removeDeviceTag($registrationId);
}

/**
 * 删除别名
 * @author Fufeng Nie <niefufeng@gmail.com>
 *
 * @param string $appId 应用ID
 * @param string $alias 别名
 * @return \JPush\Model\DeviceResponse
 */
function delete_alias($appId, $alias)
{
//    return \Addons\Push\Push::getInstance(C('PUSH_TYPE'), $appId)->deleteAlias($alias);
}

/**
 * @param $acRes
 * @param $field
 * @author Stevin.John@qq.com
 */
function _arrMinByField($acRes, $field)
{
    if (count($acRes) < 1) return $acRes;
    $mainRes = $acRes[0];
    foreach ($acRes as $k => $val) {
        $val[$field] = intval($val[$field]);
        if ($mainRes[$field] > $val[$field]) {
            $mainRes = $val;
        }
    }
    return $mainRes;
}
