<?php

//Git Config Configuration
if (isset($on) && in_array($on, ['test', 'stable', 'staging', 'live', 'audit', 'beta'])) {
    if ('live' === $on) {
        $remote = 'upstream';
        $branch = 'master';
    } else {
        if (!isset($remote)) {
            $remote = 'upstream';
        }

        if (!isset($branch)) {
            $branch = 'master';
        }
    }
} else {
    if (!isset($remote) || empty($remote)) {
        throw new \Exception('The --remote option must be set when --on option is not set.');
        die;
    }

    if (!isset($branch) || empty($branch)) {
        throw new \Exception('The --branch option must be set when --on option is not set.');
        die;
    }
}
