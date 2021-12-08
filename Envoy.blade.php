{{--Setup--}}
@setup
	require_once 'src/app.php';
	require_once 'src/server.php';
	require_once 'src/git.php';
	require_once 'src/deploy_path.php';
    require_once 'src/slack.php';

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
    $new_env_file_name = ".env_new";
@endsetup

{{-- Server Setup --}}
@servers($servers)


{{-- Check Deployment Path --}}
@task('check_path', ['on' => $remote_keys])
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

    function upload() {
        local_path="${1}"
        server="${2}"
        remote_path="${3}"
        echo "Attempting to upload env file on ${server}..."
        if test -f "${local_path}"; then
            echo "Initiating SCP on ${server}..."
            scp  "${local_path}" "${server}:${remote_path}" > /dev/null
            echo "Env uploaded successfully on ${server}"
        else
            echo "Cannot find the env file. Please check for env file. Exiting"
            exit 1
        fi
    }

    servers=({{ implode(' ', array_values($remotes)) }})
    for server in "${!servers[@]}"; do
        upload "{{ $env_path }}" "${servers[server]}" "{{ "{$deploy_path}{$new_env_file_name}" }}"
    done
@endtask

{{-- Download env for app --}}
@task('download_env', ['on' => 'localhost'])

    function download() {
        server="${1}"
        remote_path="${2}"
        local_path="${3}"
        echo "Attempting to download env file from ${server}..."
        echo "Initiating SCP from ${server}..."
        scp  "${server}:${remote_path}" "${local_path}"  > /dev/null
        echo "Env downloaded successfully from ${server}"
    }

    server="{{ reset($remotes) }}"
    download "{{ reset($remotes) }}" "{{ "{$deploy_path}.env" }}" "{{ $env_path }}"
@endtask

{{-- Switch .envs --}}
@task('switch_env', ['on' => $remote_keys])
    echo "Attempting to switch envs..."
    if test -d {{ $deploy_path }}; then
        cd {{ $deploy_path }}
        if test -f {{ "{$deploy_path}{$new_env_file_name}" }}; then
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
@task('pull', ['on' => $remote_keys])
    echo "Attempting to pull from git...";
    if test -d {{ $deploy_path }}; then
        cd {{ $deploy_path }}
        if git ls-remote -h {{ $remote }} | grep  "refs/heads/{{ $branch }}" &> /dev/null; then
            echo "Fetching from git..."
            git fetch {{ $remote }} {{$branch}} > /dev/null
            git stash > /dev/null
            git checkout {{ $branch }} > /dev/null
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
@task('composer', ['on' => $remote_keys])
    echo "Attempting to run composer..."
    if test -d {{ $deploy_path }}; then
        cd {{ $deploy_path }}
        echo "Running composer Install..."
        composer install --prefer-dist --no-interaction --no-dev --ansi
        echo "Running composer dump-autoload..."
        composer dump-autoload > /dev/null
        echo "Composer dependencies have been installed"
    else
        echo "Cannot change current directory to deploy path"
    fi
@endtask

{{-- Set permissions for various files and directories --}}
@task('permissions', ['on' => $remote_keys])
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
@task('custom_tasks', ['on' => end($remote_keys)])
    echo "Attempting to invoke custom tasks..."
    if test -d {{ $deploy_path }}; then
        cd {{ $deploy_path }}
        #Write commands here...

        echo "Custom tasks completed."
    else
        echo "Cannot change current directory to deploy path"
    fi
@endtask

{{-- Send Slack Notification --}}
@task('notify_on_slack', ['on' => end($remote_keys)])
    if test -d {{ $deploy_path }}; then
        if [[ -n {{ $slackUrl }} ]];then
            # slack webhook integration
            echo "Attempting to send slack notification..."

            cd {{ $deploy_path }}
            url=$(git config --get remote.{{$remote}}.url)
            re="^(https|git)(:\/\/|@)([^\/:]+)[\/:]([^\/:]+)\/(.+).git$"
            if [[ $url =~ $re ]]; then
                user=${BASH_REMATCH[4]}
                repo=${BASH_REMATCH[5]}
            fi

            githubUrl="https://github.com/$user/$repo/tree/{{$branch}}"

            #slack webhook params
            slackParams='{"app":"'"$repo"'","env": "{{$on}}","github_url":"'"$githubUrl"'","remote":"{{$remote}}","branch":"{{$branch}}","hosts":"{{$hostNames}}"}'

            # post request to webhook with params
            response=$(curl -sS -X POST -H 'Content-type: application/json' -d "$slackParams" '{{ $slackUrl }}')
            if [[ -z $response ]];then
                echo "Slack notification sent successfully."
            else
                echo "Failed to send slack notification."
            fi
        fi
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
        notify_on_slack
@endstory

