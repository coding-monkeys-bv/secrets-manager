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

        if (! Cache::has('aws-secrets')) {
            $this->connect();
        }
    }

    public function connect()
    {
        try {
            $stsClient = new StsClient([
                'version' => 'latest',
                'region' => $this->config['region'],
            ]);

            $result = $stsClient->assumeRole([
                'RoleArn' => 'arn:aws:iam::'.$this->config['aws_account_id'].':role/'.$this->config['role'],
                'RoleSessionName' => $this->config['role_session_name'],
            ]);

            $credentials = $result['Credentials'];

            $this->client = new SecretsManagerClient([
                'version' => 'latest',
                'region' => $this->config['region'],
                'credentials' => [
                    'key' => $credentials['AccessKeyId'],
                    'secret' => $credentials['SecretAccessKey'],
                    'token' => $credentials['SessionToken'],
                ],
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

    public function updateConfigFiles()
    {
        // Update app settings.
        config([
            'app.name' => $this->secrets['app_name'],
            'app.key' => $this->secrets['app_key'],
            'app.env' => $this->secrets['app_env'],
            'app.debug' => (bool) $this->secrets['app_debug'],
        ]);

        // Update database config.
        config([
            'database.connections.'.$this->config['db_connection'].'.driver' => $this->secrets['driver'],
            'database.connections.'.$this->config['db_connection'].'.host' => $this->secrets['host'],
            'database.connections.'.$this->config['db_connection'].'.port' => $this->secrets['port'],
            'database.connections.'.$this->config['db_connection'].'.database' => $this->secrets['dbname'],
            'database.connections.'.$this->config['db_connection'].'.username' => $this->secrets['username'],
            'database.connections.'.$this->config['db_connection'].'.password' => $this->secrets['password'],
        ]);

        // Update S3 config.
        config([
            'filesystems.disks.s3.key' => $this->secrets['aws_access_key_id'],
            'filesystems.disks.s3.secret' => $this->secrets['aws_secret_access_key'],
            'filesystems.disks.s3.region' => $this->secrets['aws_default_region'],
            'filesystems.disks.s3.bucket' => $this->secrets['aws_bucket'],
        ]);

        return $this;
    }
}
