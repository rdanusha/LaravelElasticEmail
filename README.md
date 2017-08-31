# Laravel Elastic Email #

A Laravel wrapper for Elastic Email

Can send emails with multiple attachments

### Installation ###

* Step 1

Install package via composer 

```bash
composer require rdanusha/laravel-elastic-email
```
* Step 2

Add this code to **.env file**
```
ELASTIC_ACCOUNT=<Add your account>
ELASTIC_KEY=<Add your key>
```
* Step 3

Update **MAIL_DRIVER** value as 'elastic_email' in your **.env file**
```
MAIL_DRIVER=elastic_email
```

* Step 4

Add this code to your **config/services.php** file
```
'elastic_email' => [
	'key' => env('ELASTIC_KEY'),
	'account' => env('ELASTIC_ACCOUNT')
]
```
* Step 5

Open **config/app.php** file and go to providers array, Then comment out Laravel's default MailServiceProvider and add the following
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

### Usage ###

This package works exactly like Laravel's native mailers. Refer to Laravel's Mail documentation.

https://laravel.com/docs/5.5/mail

### Code Example ###
```php
Mail::to($request->user())->send(new OrderShipped($order));
```
