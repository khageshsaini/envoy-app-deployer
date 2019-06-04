{{--Setup--}}
@setup
	require_once 'src/app.php';
	require_once 'src/server.php';
	require_once 'src/git.php';
	require_once 'src/deploy_path.php';

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
   	$env_path = __DIR__."/../{$env_file}";
@endsetup

{{-- Server Setup --}}
@servers($servers)

{{-- Upload env for app --}}
@task('upload_env', ['on' => 'localhost'])
    echo "Attempting to upload env file..."
    scp {{ $env_path }} {{ "{$scp_server}:{$deploy_path}.env_new" }} > /dev/null
@endtask

{{-- Switch .envs --}}
@task('switch_env', ['on' => 'server'])
    echo "Attempting to switch envs..."
    if test -d {{ $deploy_path }}; then
        cd {{ $deploy_path }}
        if test -f {{ "{$deploy_path}.env_new" }}; then
            echo "Copying .env file"
            cp .env .env_old
            echo "Setting new .env file"
            mv .env_new .env
        fi  
    fi
@endtask

{{-- Pull the branch into repository task --}}
@task('pull', ['on' => 'server'])
    echo "Attempting to pull from git...";
    if test -d {{ $deploy_path }}; then
        cd {{ $deploy_path }}
        if git ls-remote -h {{ $remote }} | grep  "refs/heads/{{ $branch }}" &> /dev/null; then 
            echo "Fetching from git..."
            git fetch {{ $remote }} {{$branch}} > /dev/null
            git checkout {{ $branch }} > /dev/null
            git stash > /dev/null
            git reset --hard  {{ $remote.'/'.$branch }} > /dev/null
            git stash pop > /dev/null || true
            echo "Pulled the {{ $branch }} branch successfully via {{ $remote }}"
        fi
    fi
@endtask

{{-- Updates composer, then runs a fresh installation --}}
@task('composer', ['on' => 'server'])
    echo "Attempting to run composer..."
    if test -d {{ $deploy_path }}; then
        cd {{ $deploy_path }}
        echo "Running composer Install..."
        composer install --prefer-dist --no-interaction
        echo "Running composer dump-autoload..."
        composer dump-autoload > /dev/null
        echo "Composer dependencies have been installed"
    fi
@endtask

{{-- Set permissions for various files and directories --}}
@task('permissions', ['on' => 'server'])
    echo "Attempting to set permissions..."
    if test -d {{ $deploy_path }}; then
        cd {{ $deploy_path }}
        @foreach($writable as $item)
            chmod -R 755 {{ $item }}
            chmod -R g+s {{ $item }}
            chgrp -R www-data {{ $item }}
            echo "Permissions have been set for {{ $item }} folder"

            @if($item === 'storage')
                touch "storage/logs/lumen.log"
            @endif
        @endforeach
    fi
@endtask

{{-- Deployment Story, use to deploy a new version of a existent project --}}
@story('deploy')
		upload_env
		switch_env
        pull
        composer
        permissions
@endstory