<?php
return [
    'id' => 'bibliograph-server',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'bibliograph\controllers',
    'aliases' => [
        '@bibliograph' => dirname(__DIR__),
    ],
    'components' => [
    ]
];