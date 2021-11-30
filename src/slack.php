<?php

if (isset($on)) {
    $slackUrl = getenv("SLACK_WEBHOOK_URL");
    if (isset($slackUrl)) {
        $app = basename(exec('cd .. && pwd'));
        $slackParams = json_encode([
            'app' => $app,
            'env' => ucwords($on),
            'remote' => $remote,
            'branch' => $branch,
            'user'=> exec('git config user.name'),
            'hosts' => implode(' | ', $hosts)
        ]);
    }
} else {
    throw new \Exception('The --on option is not set.');
    die;
}
