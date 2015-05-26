<?php


/**
 * @param $shopIds
 * @return array
 */
function build_sql_bind($shopIds)
{
    $bindNames = [];
    $bindValues = [];

    foreach ($shopIds as $i => $id) {
        $name = ':id' . $i;
        $bindNames[] = $name;
        $bindValues[$name] = $id;
    }
    return array($bindNames, $bindValues);
}