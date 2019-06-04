{{--Setup--}}
@setup
	require_once 'app.php';
	require_once 'server.php';
	require_once 'git.php';
	require_once 'deploy_path.php';

	/*
    |--------------------------------------------------------------------------
    | Writable resources
    |--------------------------------------------------------------------------
    |
    | Define the resources that needs writable permissions.
    |
    */
    $writable = [
        'storage'
    ];

    /*
    |--------------------------------------------------------------------------
    | APP ENV PATH
    |--------------------------------------------------------------------------
    |
    | Define the resources that needs writable permissions.
    |
    */
   	$env_file = isset($on) ? ".env_{$on}" : ".env";
   	$env_path = __DIR__."/../../{$env_file}";
@endsetup

{{-- Server Setup --}}
@servers($servers)