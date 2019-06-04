<?php

//Server configuration
require_once 'host.php';
require_once 'user.php';

$scp_server = "{$user}@{$host}";
$server = "-A {$scp_server}";
$localhost = "127.0.0.1";
$servers = compact('server', 'localhost');