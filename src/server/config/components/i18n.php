<?php
// Internationalization
// move into modules!
// https://stackoverflow.com/questions/34357254/override-translation-path-of-module-on-yii2
// https://www.yiiframework.com/doc/guide/2.0/en/tutorial-i18n#module-translation
// catchall "*" doesn't work
return [
  'translations' => [
    'app' => [
      'class' => \yii\i18n\GettextMessageSource::class,
      'basePath' => '@messages',
      'catalog' => 'messages',
      'useMoFile' => false
    ]
  ],
];
