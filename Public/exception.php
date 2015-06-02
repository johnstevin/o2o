<?php
header('Content-Type:application/json; charset=utf-8');
$result = array();
$result['success'] = false;
$result['error_code'] = 40004;
$result['message'] = 'system error';
echo json_encode($result);
exit;