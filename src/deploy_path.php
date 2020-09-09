<?php

//Server Path Configuration--}}
if (isset($on)) {
    if (in_array($on, ['test', 'stable', 'staging', 'live', 'audit'])) {
        $path_var = 'APP_'.strtoupper($on).'_PATH';

        if (!getenv($path_var) || empty(getenv($path_var))) {
            throw new \Exception("The {$path_var} environment variable is required when --on option is set.");
            die;
        }

        $deploy_path = getenv($path_var);
    } else {
        throw new \Exception('The --on option must be valid.');
        die;
    }
} else {
    //If on variable is not set, then we must get the host from the user itself
    if (!isset($target_path) || empty($target_path)) {
        throw new \Exception('The --target_path option is required if --on option is not set.');
        die;
    }

    $deploy_path = $target_path;
}
