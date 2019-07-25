<?php

//Server configuration
require_once 'host.php';
require_once 'user.php';

$hosts = array_map('trim', explode(',', $host));
$remotes = [];
foreach ($hosts as $key => $host) {
    $remotes['remote_'.$key] = "{$user}@{$host}";
}

$localhost = '127.0.0.1';

//Configure servers array
$servers = array_merge($remotes, compact('localhost'));

//Remote Servers Identifiers
$remote_keys = array_keys($remotes);
