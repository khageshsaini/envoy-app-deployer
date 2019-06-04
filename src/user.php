<?php

//User Configuration
if (!isset($user) || empty($user)) {
	if(!getenv('DEPLOYER_USER') || empty(getenv('DEPLOYER_USER'))) {
		throw new \Exception('The DEPLOYER_USER environment variable is required when --user option is not set.');
		die;
	}
	$user = getenv('DEPLOYER_USER');
}
