<?php
//分组聚合统计
header("content-type:text/html;charset=utf-8");
define('ENTITY_TYPE', 4);
require_once dirname(__FILE__).'/path.php';
require_once dirname(__FILE__).'/vendor/autoload.php';
require_once __DIR__ . '/vendor/threads/ThreadManager.php';
set_time_limit(0);
ini_set('memory_limit','3000M');
ini_set('display_errors',1);
ini_set("error_reporting", E_ALL);

$pageSize = 300;
$threadNumber = 10;

while(1) {
    $ids = getIds($pageSize, $threadNumber);
    $thrManager = Thread\ThreadManager::factory(array(
        'timeout' => 5, // seconds
        'maxProcess' => $threadNumber,
        'scriptPath' => __DIR__ . '/elasticMultiAgg.php', // path to worker script
        'onCompliteCallback' => function ($response) {
            print_r($response);
        }
    ));


    for ($i = 0; $i < $threadNumber; $i++) {
        if (isset($ids[$i])) $thrManager->addThread($ids[$i]);
    }
    $thrManager->run(); // run it!
    sleep(3);
}


//print("All processes finished on this line!");

function getIds($pageSize, $threadNumber)
{
    $limit = $pageSize * $threadNumber;
    $sql = "SELECT id FROM `cntCacheV5`  where finished=0  LIMIT $limit";
    $db = dbHandleManage::getSearchV5SlaveDBObject();
    $q = $db->query($sql);
    $return = $ids = array();
    while($row = $q->fetch_array()) {
        $ids[] = $row;
    }


    for ($i = 0; $i < $threadNumber; $i++) {
        if ($i < $threadNumber - 1) {
            $lastIdArr = array_slice($ids, $i * $pageSize, $pageSize);
        } else {
            $lastIdArr = array_slice($ids, $i * $pageSize);
        }
        $return[] = array('min' => $lastIdArr[0]['id'], 'max' =>$lastIdArr[$pageSize-1]['id']);
        unset($lastIdArr);
    }

    return  $return;
}
exit;


