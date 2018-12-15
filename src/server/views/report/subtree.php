<?php
use app\models\Datasource;
use app\models\Folder;
use app\models\Reference;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $controller app\controllers\ReportController */
/* @var $folders array */
/* @var $datasource string */

?>
<!DOCTYPE html>
<html>
<head>
    <?= Html::csrfMetaTags() ?>
    <meta charset="utf-8" />
    <title>Bibliograph report</title>
</head>
<body>
<?php foreach ($folders as $folder): list($level,$folderId) = $folder; ?>
<?= "<h$level>{$controller->getFolder($datasource, $folderId)->label}</h$level>";?>
<ol><?php foreach ($controller->getReferences($datasource, $folderId) as $record): ?>
    <li>
        <?= $record['author'] ?? ($record['editor'] ? $record['editor'] . " (Hg.)":"");?> (<?= $record['year'];?>):<br />
        <?= $record['title'];?><?= $record['journal'] ? " (" . $record['journal'] . ")" : ""?>.
    </li>
    <?php endforeach;?>
</ol>
<?php endforeach;?>
</body>
</html>