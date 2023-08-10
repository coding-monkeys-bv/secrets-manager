# Laravel AWS Secrets Manager

This package allows you to fetch your secrets from AWS Secrets Manager.


## Installation

You can install the package via composer:
```bash
composer require codingmonkeys/secrets-manager
```

## Usage

### Configuration

The secrets manager package uses the following environment variables:

```dotenv
DB_CONNECTION=mysql
AWS_DEFAULT_REGION=eu-central-1
SECRETS_MANAGER_ROLE=arn:aws:iam::123456789012:role/role-name
SECRETS_MANAGER_ROLE_SESSION_NAME=role-session-name
SECRETS_MANAGER_AWS_ACCOUNT_ID=123456789012
SECRETS_MANAGER_ENVIRONMENT=dev
SECRETS_MANAGER_CACHE_TIMEOUT=60
```

Please note: SECRETS_MANAGER_CACHE_TIMEOUT is the number of minutes the secrets are cached.

### Implementation

In AppServiceProvider, paste this line in the boot method:

```php
// Update database config.
app('secrets-manager')->getSecrets()->updateDatabaseCredentials();
```