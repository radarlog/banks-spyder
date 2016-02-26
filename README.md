Banks Spyder
============================

Banks Spyder is free software to automatically generate/download bank statements in PDF format from your accounts. Currently supported is only Bank Of Cyprus.


REQUIREMENTS
------------

The minimum requirement by this project that your Web server supports PHP 5.5.0.


INSTALLATION
------------

### Install via Composer

If you do not have [Composer](http://getcomposer.org/), you may install it by following the instructions
at [getcomposer.org](http://getcomposer.org/doc/00-intro.md#installation-nix).

You can then install this project template using the following command:

~~~
php composer.phar global require "fxp/composer-asset-plugin:~1.1.1"
php composer.phar create-project --prefer-dist radarlog/banks-spyder banks-spyder
~~~

Now you should be able to access the application through the following URL, assuming `banks-spyder` is the directory
directly under the Web root.

~~~
http://localhost/banks-spyder/web/
~~~


CONFIGURATION
-------------

### Database

Edit the file `config/common.php` with real data, for example:

```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=yii2basic',
    'username' => 'root',
    'password' => '1234',
    'charset' => 'utf8',
];
```

**NOTES:**
- Yii won't create the database for you, this has to be done manually before you can access it.
- Check and edit the other files in the `config/` directory to customize your application as required.
- You can override local settings by creating `config/*-local.php` files in the same format


### Migrations

Update database by applying migrations with command:

```
./yii migrate
```

USAGE
-----

You can retrieve all companies to be parsed from all your accounts by running console command:

```
./yii parse/banks-clients
```

You can parse next unparsed company and generate bank statements in PDF format by running console command:

```
./yii parse/next-company
```

Generated bank statements can be found in `/modules/banks/statements` folder. If no transactions were found, the report would not be created.