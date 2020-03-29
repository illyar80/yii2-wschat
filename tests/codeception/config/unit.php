<?php
return [
    'id' => 'wschat-tests',
    'basePath' => dirname(__DIR__),
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache'
        ],
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'pgsql:host=localhost;dbname=wschat_tests',
//            'dsn' => 'mysql:host=localhost;dbname=wschat_tests',
            'username' => '',
            'password' => '',
            'charset' => 'utf8',
        ],
    ],
];
