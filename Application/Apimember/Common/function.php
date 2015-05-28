<?php


/**
 * 通过数值构建SQL参数绑定
 * @author WangJiang
 * @param array $list 条件数组
 * @param string $prefix 参数名称前缀
 * @return array $bindNames 参数名称, $bindValues参数绑定用于bind调用
 */
function build_sql_bind($list,$prefix='bindName')
{
    $bindNames = [];
    $bindValues = [];

    foreach ($list as $i => $id) {
        $name = ':'.$prefix . $i;
        $bindNames[] = $name;
        $bindValues[$name] = $id;
    }
    return array($bindNames, $bindValues);
}