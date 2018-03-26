<?php
namespace Deployer;

// see https://deployer.org/docs

require 'recipe/yii2-app-advanced.php';

// Project name
set('application', 'Bibliograph');

// Project repository
set('repository', 'git@github.com:cboulanger/bibliograph.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true); 

// Shared files/dirs between deploys 
add('shared_files', []);
add('shared_dirs', []);

// Writable dirs by web server 
add('writable_dirs', []);


// Hosts

host('project.com')
    ->set('deploy_path', '~/{{application}}');    
    
// Tasks

task('build', function () {
    run('cd {{release_path}} && build');
});

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

