<?php

namespace Jonnx\LaravelSimpleDocker\Console\Commands;

use Illuminate\Console\Command;

class Init extends Command
{
    protected $org;

    protected $repo;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docker:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'initialize a simple docker environment';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param  \App\DripEmailer  $drip
     * @return mixed
     */
    public function handle()
    {
        if($this->checkDependencies()) {
            $this->getOrg();
            $this->getRepo();
            $this->dockerfile();
            $this->dockerCompose();
            $this->env();
        }
    }

    protected function checkDependencies()
    {
        $this->comment('Checking dependencies');

        // check docker exists
        // @todo check version
        $dockerCommand = shell_exec(sprintf("which %s", escapeshellarg('docker')));
        $hasDockerCommand = !empty($dockerCommand);

        // check docker-compose exists
        // @todo check version
        $dockerComposeCommand = shell_exec(sprintf("which %s", escapeshellarg('docker-compose')));
        $hasDockerComposeCommand = !empty($dockerComposeCommand);

        $dependenciesMet = $hasDockerCommand && $hasDockerComposeCommand;
        if(!$dependenciesMet) {
            $this->warning('Looks like there are some missing dependencies!');
            if(!$hasDockerCommand) {
                $this->warning('missing command: docker');
            }
            if(!$hasDockerComposeCommand) {
                $this->warning('missing command: docker-compose');
            }

            return $this->confirm('Do you want to continue anyways?', false);
        }

        return true;
    }

    protected function getOrg()
    {
        $composerConfig = json_decode(file_get_contents(base_path('composer.json')));
        $name = data_get($composerConfig, 'name');
        $namePieces = explode('/', $name);
        $org = $namePieces[0];
        $this->org = $this->ask('What is your docker repo organization?', $org);
    }

    protected function getRepo()
    {
        $composerConfig = json_decode(file_get_contents(base_path('composer.json')));
        $name = data_get($composerConfig, 'name');
        $namePieces = explode('/', $name);
        $repo = $namePieces[1];
        $this->repo = $this->ask('What is the project\'s docker repo name?', $repo);
    }

    protected function dockerfile()
    {
        if($this->confirm('Do you want to setup a Dockerfile?', true)) {
            // copy Dockerfile
            $this->comment('creating "/Dockerfile"');
            copy(base_path('vendor/jonnx/laravel-simple-docker/resources/php:7.3-apache/Dockerfile'), base_path('Dockerfile'));

            // create docker dir
            if(!file_exists(base_path('docker'))) {
                $this->comment('creating "/docker"');
                mkdir(base_path('docker'));
            }
            
            // create entrypoint.sh
            $this->comment('creating "/docker/entrypoint.sh"');
            copy(base_path('vendor/jonnx/laravel-simple-docker/resources/php:7.3-apache/docker/entrypoint.sh'), base_path('docker/entrypoint.sh'));

            // create supervisor.conf
            $this->comment('creating "/docker/supervisord.conf"');
            copy(base_path('vendor/jonnx/laravel-simple-docker/resources/php:7.3-apache/docker/supervisord.conf'), base_path('docker/supervisord.conf'));

            // create virtualhost.conf
            $this->comment('creating "/docker/virtualhost.conf"');
            copy(base_path('vendor/jonnx/laravel-simple-docker/resources/php:7.3-apache/docker/virtualhost.conf'), base_path('docker/virtualhost.conf'));
        }
    }

    protected function dockerCompose()
    {
        $this->comment('creating "/docker-compose.yml"');
        $dockerCompose = file_get_contents(base_path('/vendor/jonnx/laravel-simple-docker/resources/php:7.3-apache/docker-compose.yml'));
        $dockerCompose = str_replace('__ORG__', $this->org, $dockerCompose);
        $dockerCompose = str_replace('__REPO__', $this->repo, $dockerCompose);
        file_put_contents(base_path('docker-compose.yml'), $dockerCompose);
    }

    protected function env()
    {
        $this->comment('creating "/.env.example"');
        copy(base_path('vendor/jonnx/laravel-simple-docker/resources/php:7.3-apache/.env.example'), base_path('.env.example'));

        // determine if env file should be created
        $createEnv = true;
        $envExists = file_exists(base_path('.env'));
        if($envExists) {
            $createEnv = $this->confirm('Do you want to replace your local env (this will change the APP_KEY)?', $createEnv);
        }

        // create environment file
        if($createEnv) {
            $this->comment('creating "/.env.example"');
            copy(base_path('.env.example'), base_path('.env'));

            // set new application key
            $this->call('key:generate');
            exec('php artisan key:generate', $keyGenerateOutput);
        }
    }
}