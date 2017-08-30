# LaravelElasticEmail

# Laravel Elastic Email #

A Laravel wrapper for Elastic Email

### Installation ###

Add Laravel Elastic Email as a dependency using the composer CLI:

```bash
composer require rdanusha/LaravelElasticEmail
```

Next, add the following to your config/services.php and add the correct values to your .env file
```php
'elastic_email' => [
	'key' => env('ELASTIC_KEY'),
	'account' => env('ELASTIC_ACCOUNT')
]
```

Next, in config/app.php, comment out Laravel's default MailServiceProvider and add the following
```php
'providers' => [
    /*
     * Laravel Framework Service Providers...
     */
    ...
//    Illuminate\Mail\MailServiceProvider::class,
    Rdanusha\LaravelElasticEmail\LaravelElasticEmailServiceProvider::class,
    ...
],
```

Finally switch your default mail provider to elastic email in your .env file by setting MAIL_DRIVER=elastic_email

### Usage ###

This package works exactly like Laravel's native mailers. Refer to Laravel's Mail documentation.
