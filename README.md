# yii2-gcm
Envío de notificaciones con GCM

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run
```
php composer.phar require --prefer-dist norotaro/yii2-gcm "1.0.0"
```

or add

```
"norotaro/yii2-gcm": "1.0.0"
```

to the require section of your `composer.json` file.

----------

in your main.php your configuration would look like this

```php
'components' => [
	'gcm' => [
		'class' => 'norotaro\gcm\Gcm',
		'apiKey' => 'your_api_key',
	],
]
```

Usage
-----

```php
/* @var $apnsGcm \bryglen\apnsgcm\Gcm */
$gcm = Yii::$app->gcm;
$gcm->send($push_tokens, $message,
  [
    'customerProperty' => 1,
  ],
  [
    'timeToLive' => 3
  ],
);
