<?php

namespace CodingMonkeys\SecretsManager;

use Illuminate\Support\ServiceProvider;

class SecretsManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\AWS\SecretsManager::class, function () {
            return new \App\Services\AWS\SecretsManager([
                'region' => env('AWS_DEFAULT_REGION', 'eu-central-1'),
                'role' => env('SECRETS_MANAGER_ROLE'),
                'role_session_name' => env('SECRETS_MANAGER_ROLE'),
                'aws_account_id' => env('SECRETS_MANAGER_AWS_ACCOUNT_ID'),
                'environment' => env('SECRETS_MANAGER_ENVIRONMENT'),
                'cache_timeout_in_minutes' => env('SECRETS_MANAGER_CACHE_TIMEOUT', 1),
            ]);
        });
    }

    public function boot(): void
    {
        SecretsManagerFacade::getSecrets()
            ->updateDatabaseCredentials();
    }
}