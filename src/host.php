<?php

//Host Configuration
if (isset($on)) {
    if (in_array($on, ['test', 'stable', 'staging', 'live', 'audit', 'beta'])) {
        $host_var = strtoupper($on).'_HOST';

        if (!getenv($host_var) || empty(getenv($host_var))) {
            throw new \Exception("The {$host_var} environment variable is required when --on option is set.");
            die;
        }

        $host = getenv($host_var);
    } else {
        throw new \Exception('The --on option must be valid.');
        die;
    }
} else {
    throw new \Exception('The --on option is not set.');
    die;
}
