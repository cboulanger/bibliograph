<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 06.06.18
 * Time: 09:23
 */

namespace lib\controllers;


use app\controllers\traits\AuthTrait;
use app\controllers\traits\DatasourceTrait;
use app\controllers\traits\MessageTrait;

class ProgressController extends  \yii\web\Controller
{
  use MessageTrait;
  use AuthTrait;
  use DatasourceTrait;
}