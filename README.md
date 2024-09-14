## 
## Install for laravel

**1.** Open file **bootstrap/providers.php** and connect the provider from the package (optional, using laravel discovered package system by default)
```
\Crow\LaravelActivationCode\Providers\ActivationCodeServiceProvider::class,
```

**2.** Run commands

For creating config file
```
php artisan vendor:publish --provider="Crow\LaravelActivationCode\Providers\ActivationCodeServiceProvider" --tag=config
```
For creating migration file
```
php artisan activation:code:publish --tag=migration
```
For generate table
```
php artisan migrate
```

## ENV variables

File .env

Total of attempt for enter code
```
ACTIVATION_CODE_DEFAULT_MAX_ATTEMPT=5
```

Total of attempt for enter code (for sms mode)
```
ACTIVATION_CODE_SMS_MAX_ATTEMPT=5
```

Generate code mode
```
ACTIVATION_CODE_DEFAULT_GENERATE_MODE=5
```

Generate code mode (for sms mode)
```
ACTIVATION_CODE_SMS_GENERATE_MODE=4
```

Code length
```
ACTIVATION_CODE_DEFAULT_CODE_LENGTH=20
```

Code length (for sms mode)
```
ACTIVATION_CODE_SMS_CODE_LENGTH=5
```

Code TTL
```
ACTIVATION_CODE_DEFAULT_CODE_TTL=1h
```

Code TTL (for sms mode)
```
ACTIVATION_CODE_SMS_CODE_TTL=5m
```

## Custom model

###### Step 1

Create custom model for activation code

Example:

File: **app/CustomFile.php**

Content:

```
<?php

namespace App;

class CustomFile extends \Crow\LaravelActivationCode\Model\ActivationCode
{
    // other code
}
```

###### Step 2

Open **config/activation_code.php** and change parameter "model", example:

```
...
// remove
'model' => \Crow\LaravelActivationCode\Model\ActivationCode::class,
// add
'model' => \App\CustomFile::class,
```

## Usage

#### Initialize service

```
$service = app(\Crow\LaravelActivationCode\ActivationCodeService::class);
```

or injected

```
// use
use Crow\LaravelActivationCode\ActivationCodeService;
// constructor
public function __construct(
    ActivationCodeService service
)
```

or use helper

```
\Crow\LaravelActivationCode\Helpers\ActivationCodeHelper::METHOD_NAME_FROM_SERVICE()
```

further we will use the variable **$service**

#### Generation code

Returned **\Crow\LaravelActivationCode\Model\ActivationCode** eloquent model

Basic usage with min parameters

```
$model = $service->make('test@test.com', 'user_register');
```

Usage with parameter

```
$model = $service->make('test@test.com', 'user_register', 10);
// 1 parameter - receiver for code (email, phone or other)
// 2 parameter - type code (context) (user activation, password recovery, confirm order or other)
// 3 parameter - (optional) entity ID
```

#### Check activation code on of valid

Returned **\Crow\LaravelActivationCode\Model\ActivationCode** eloquent model

If returned model, then code valid

```
$model = $service->get('test@test.com', '12345', 'user_register');
// with extra parameters
$model = $service->get('test@test.com', '12345', 'user_register', true, true);
// 1 parameter - receiver for code (email, phone or other)
// 2 parameter - code
// 3 parameter - type code (context) (user activation, password recovery, confirm order or other)
// 4 parameter - (optional) use exception (true - use (default), false - not use)
// 5 parameter - (optional) disabled attempts for code (true - disable, false - enable (default))
```

or

```
$model = $service->getByCode('12345', 'user_register');
$model = $service->getByCode('12345', 'user_register', false);
// 1 parameter - code
// 2 parameter - type code (context) (user activation, password recovery, confirm order or other)
// 3 parameter - (optional) use exception (true - use (default), false - not use)
```

#### Delete activation code

Use if you need to activate the code or just delete it

```
$service->delete($model);
```

## Configuring activation code

All configuration is optional depending on the task

Example

```
$service
    ->setMode(\Crow\LaravelActivationCode\ActivationCodeService::MODE_SMS)
    ->setGenerateCodeMode(\Crow\LaravelActivationCode\ActivationCodeService::GENERATE_CODE_MODE_ALPHABET_LOWER)
    ->setCodeLength(7)
    ->setCodeTTL('20m')
    ->setMaxAttempt(3);
$service
    ->setMode('sms')
    ->setGenerateCodeMode(3)
    ->setCodeLength(7)
    ->setCodeTTL('20m')
    ->setMaxAttempt(5);
```
- **setMode** - code generation mode (null - use default configuration, sms - use configuration for sms mode) (only works for configurations that have not been manually overridden)
- **setGenerateCodeMode** - rule of generation code
- - **Crow\LaravelActivationCode\ActivationCodeService::GENERATE_CODE_MODE_ALPHABET** (1) - only letters, case insensitive
- - **Crow\LaravelActivationCode\ActivationCodeService::GENERATE_CODE_MODE_ALPHABET_LOWER** (2) - only lowercase letters
- - **Crow\LaravelActivationCode\ActivationCodeService::GENERATE_CODE_MODE_ALPHABET_UPPER** (3) - only uppercase letters
- - **Crow\LaravelActivationCode\ActivationCodeService::GENERATE_CODE_MODE_NUMBER** (4) - only numbers
- - **Crow\LaravelActivationCode\ActivationCodeService::GENERATE_CODE_MODE_ALL** (5) - (default) letters and numbers
- **setCodeLength** - code length
- **setCodeTTL** - code lifetime, format: 10m (example: 10 - 10 seconds, 10m - 10 minutes, 10h - 10 hours, 10d - 10 days)
- **setMaxAttempt** - maximum number of attempts to enter the code (use when checking code)

#### Reset configuration

- mode
- generationCode
- code length
- code ttl
- max attempt

```
$service->reset();
```

## Example

```
// generate code
$model = $service->make('test@test.com', 'user_register');

// get code
$model = $service->get('test@test.com', '12345', 'user_register');
... you PHP code

// activate of code
$service->delete($model);
```

With custom configuration

```
// custom configuration
$service
    ->setMode(\Crow\LaravelActivationCode\ActivationCodeService::MODE_SMS)
    ->setGenerateCodeMode(\Crow\LaravelActivationCode\ActivationCodeService::GENERATE_CODE_MODE_ALPHABET_LOWER)
    ->setCodeLength(7)
    ->setCodeTTL('20m')
    ->setMaxAttempt(3);
    
// create code
$model = $service->make('test@test.com', 'user_register');

// get code
$service->setMaxAttempt(3);
$model = $service->get('test@test.com', '12345', 'user_register');
... you PHP code

// activate of code
$service->delete($model);
```
