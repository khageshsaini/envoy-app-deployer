<?php

if (isset($on)) {
    $slackUrl = getenv("SLACK_WEBHOOK_URL");
    $hostNames = implode(' | ', $hosts);
} else {
    throw new \Exception('The --on option is not set.');
    die;
}
