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
 * @param $words
 * @param $words_op
 * @param $flds
 * @param $map
 * @return mixed
 */
function build_words_query($words, $words_op, $flds, &$map)
{
    //TODO:奇葩问题，传入的参数是'or'时，TP会转换成'or '
    $words_op=trim($words_op);

    $nw = count($words);
    $nf = count($flds);
    $where_kws = null;
    for ($i = 0; $i < $nf; $i++) {
        $val = array();
        for ($j = 0; $j < $nw; $j++) {
            $val[] = array('like', '%' . $words[$j] . '%');
        }
        $val[] = $words_op;
        //$val['_logic']='or';
        $where_kws[$flds[$i]] = $val;
    }
    $where_kws['_logic'] = 'or';
    $map['_complex'] = $where_kws;
    return $map;
}
