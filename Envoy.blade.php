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


{{-- Check Deployment Path --}}
@task('check_path', ['on' => 'server'])
    echo "{{ 'Target Deployement Path - '.$deploy_path  }}"
    echo "Attempting to validate deployement path..."
    if test -d {{ $deploy_path }}; then
        echo "Success."
    else
        echo "Failed. Exiting."
        exit 1
    fi
@endtask

{{-- Upload env for app --}}
@task('upload_env', ['on' => 'localhost'])
    echo "Attempting to upload env file..."
    if test -f {{ $env_path }}; then
        echo "Initiating SCP..."
        scp {{ $env_path }} {{ "{$scp_server}:{$deploy_path}.env_new" }} > /dev/null
        echo "Env uploaded successfully"
    else 
        echo "Cannot find the env file. Please check for env file. Exiting"
        exit 1
    fi
@endtask

{{-- Switch .envs --}}
@task('switch_env', ['on' => 'server'])
    echo "Attempting to switch envs..."
    if test -d {{ $deploy_path }}; then
        cd {{ $deploy_path }}
        if test -f {{ "{$deploy_path}.env_new" }}; then
            echo "Copying .env file..."
            cp .env .env_old
            echo "Old env backed up successfully by .env_old"
            echo "Setting new .env file..."
            mv .env_new .env
            echo "New env file in effect"
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
        else 
            echo "Possible misconfiguration in remote and branch input. Please check your input."
            echo "Git pull failed"
        fi
    else
        echo "Cannot change current directory to deploy path"
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
    else
        echo "Cannot change current directory to deploy path"
    fi
@endtask

{{-- Set permissions for various files and directories --}}
@task('permissions', ['on' => 'server'])
    echo "Attempting to set permissions..."
    if test -d {{ $deploy_path }}; then
        cd {{ $deploy_path }}
        @foreach($writable as $item)
            chmod -R 775 {{ $item }} || true
            chmod -R g+s {{ $item }} || true
            chgrp -R www-data {{ $item }} || true
            echo "Permissions have been set for {{ $item }} folder"

            @if($item === 'storage')
                touch "storage/logs/lumen.log"
            @endif
        @endforeach
    else
        echo "Cannot change current directory to deploy path"
    fi
@endtask

{{-- Set custom tasks --}}
@task('custom_tasks', ['on' => 'server'])
    echo "Attempting to invoke custom tasks..."
    if test -d {{ $deploy_path }}; then
        cd {{ $deploy_path }}
        #Write commands here...

        echo "Custom tasks compeleted."
    else
        echo "Cannot change current directory to deploy path"
    fi
@endtask

{{-- Deployment Story, use to deploy a new version of a existent project --}}
@story('deploy')
        check_path
		upload_env
		switch_env
        pull
        composer
        permissions
        custom_tasks
@endstory