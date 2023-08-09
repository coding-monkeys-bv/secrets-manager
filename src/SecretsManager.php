<?php

namespace CodingMonkeys\SecretsManager;

use Aws\Exception\AwsException;
use Aws\SecretsManager\SecretsManagerClient;
use Aws\Sts\StsClient;
use Illuminate\Support\Facades\Cache;

class SecretsManager
{
    public $client;
    private $config;
    private $secrets;
    private $cacheRefreshed = false;

    public function __construct(array $config)
    {
        $this->config = $config;

        $this->connect();
    }

    public function connect()
    {
        try {
            $stsClient = new StsClient([
                'version' => 'latest',
                'region' => $this->config['region'],
            ]);

            $result = $stsClient->assumeRole([
                'RoleArn' => 'arn:aws:iam::' . $this->config['aws_account_id'] . ':role/' . $this->config['role'],
                'RoleSessionName' => $this->config['role_session_name'],
            ]);

            $credentials = $result['Credentials'];

            $this->client = new SecretsManagerClient([
                'version' => 'latest',
                'region' => $this->config['region'],
                'credentials' => [
                    'key' => $credentials['AccessKeyId'],
                    'secret' => $credentials['SecretAccessKey'],
                    'token' => $credentials['SessionToken']
                ]
            ]);

        } catch (AwsException $e) {
            $error = $e->getAwsErrorCode();

            throw $e;
        }
    }

    public function getSecrets()
    {
        $this->secrets = Cache::remember('aws-secrets', now()->addMinutes($this->config['cache_timeout_in_minutes']), function () {
            $result = $this->client->getSecretValue(['SecretId' => $this->config['environment']]);

            // Mark cache as refreshed.
            $this->cacheRefreshed = true;

            return json_decode($result['SecretString'], true);
        });

        return $this;
    }

    public function updateDatabaseCredentials()
    {
        // Return when cache is not refreshed.
        if (!$this->cacheRefreshed) {
            return;
        }

        // Update database config.
        config([
            'database.connections.mysql.host' => $this->secrets['host'],
            'database.connections.mysql.port' => $this->secrets['port'],
            'database.connections.mysql.database' => $this->secrets['dbname'],
            'database.connections.mysql.username' => $this->secrets['username'],
            'database.connections.mysql.password' => $this->secrets['password'],
        ]);

        return $this;
    }
}