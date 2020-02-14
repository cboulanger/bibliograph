<?php
// require needed libraries & constants
require __DIR__ . '/config/constants.php';
require APP_BACKEND_DIR . '/vendor/autoload.php';
require APP_BACKEND_DIR . '/vendor/yiisoft/yii2/Yii.php';
require APP_BACKEND_DIR . '/lib/components/Configuration.php';
// load environment variables
(new Symfony\Component\Dotenv\Dotenv())->load( DOTENV_FILE);
