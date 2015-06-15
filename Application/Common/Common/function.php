<?php

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
 * @param array $sortby 排序类型
 * asc正向排序 desc逆向排序 nat自然排序
 * @return array
 */
function list_sort_by($list, $field, $sortby = 'asc')
{
    if (is_array($list)) {
        $refer = $resultSet = array();
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
function list_to_tree($list, $pk='id', $pid = 'pid', $child = '_child', $root = 0) {
    // 创建Tree
    $tree = array();
    if(is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId =  $data[$pid];
            if ($root == $parentId) {
                $tree[] =& $list[$key];
            }else{
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
function tree_to_list($tree, $child = '_child', $order = 'id', &$list = array())
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
 * 检测商家是否存在
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @param int $id 商铺ID
 * @todo 待完善
 * @return bool
 */
function check_merchant_shop_exist($id)
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

function check_region_exist($id)
{
    //TODO 等待region模型
    return true;
}

/**
 * 生成订单代码
 * @author Fufeng Nie <niefufeng@gmail.com>
 * @return string
 */
function create_order_code()
{
    //TODO 没有代码。。。
    return date("YmdHi") . time();
}

/**
 * 检测当前用户是否为管理员
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
 * @return int
 */
function is_merchant_login()
{
    $user = session('merchant_auth');
    if (empty($user)) {
        return 0;
    } else {
        return session('merchant_auth_sign') == data_auth_sign($user) ? $user['uid'] : 0;
    }
}

/**
 * @return int
 */
function is_member_login()
{
    $user = session('member_auth');
    if (empty($user)) {
        return 0;
    } else {
        return session('member_auth_sign') == data_auth_sign($user) ? $user['uid'] : 0;
    }
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
function api($name, $vars = array())
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
function int_to_string(&$data, $map = array('status' => array(1 => '正常', -1 => '删除', 0 => '禁用', 2 => '未审核', 3 => '草稿')))
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
 * @param array $bindNames
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
    return array($bindNames, $bindValues);
}

/**
 * array_column兼容性处理
 */
if(!function_exists('array_column')){
    function array_column(array $input, $columnKey, $indexKey = null) {
        $result = array();
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
