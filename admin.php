<?php

if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

/**
 * 系统调试设置
 * 项目正式部署后请设置为false
 */
//define('APP_DEBUG', true );
define('BIND_MODULE','Admin');
define('APP_PATH', './Application/');
define('RUNTIME_PATH', './Runtime/');

require './ThinkPHP/ThinkPHP.php';
