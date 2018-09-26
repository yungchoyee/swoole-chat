<?php
$config = [
    'base' => [
        'host' => '192.168.3.186',
        'port' => 9502
    ],
    'manage' => [
        'adminID' => '838881690'
    ],
    'swoole' => [
        'worker_num' => 1, //worker进程设置为1，这样就能将fd与用户id进行唯一绑定，不然多进程下无法判断用户是否绑定
        'heartbeat_check_interval' => 1,
        'heartbeat_idle_time' => 125999999999,
        'daemonize' => 1,
        'log_file' => __DIR__.'/swoole.log'
    ]
];
?>
