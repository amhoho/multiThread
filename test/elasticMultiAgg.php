<?php
//分组聚合统计
header("content-type:text/html;charset=utf-8");
define('ENTITY_TYPE', 4);
require_once dirname(__FILE__).'/path.php';
require_once dirname(__FILE__).'/vendor/autoload.php';

set_time_limit(0);
ini_set('memory_limit','3000M');
ini_set('display_errors',1);
ini_set("error_reporting", E_ALL);
//获取参数
$argv = $_SERVER['argv'][1];
//将参数转化成数组
parse_str($argv, $argvArr);

$elasticCount = new elasticCount();
$elasticCount->setAggMinId($argvArr['min'])
             ->setAggMaxId($argvArr['max'])
             ->setIdOrder('multi')
             ->runMultiStats();


