<?php
set_time_limit(0);
include("gs3.php");
/* fake keys!, please put yours */
define('S3_KEY', 'DA5S4D5A6S4D');
define('S3_PRIVATE','adsadasd');
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
</head>

<body>
<h1 align="center">Testing Amazon S3 Stream Wrapper</h1>
<hr>
<h3 align="left">Create a Directory (bucket)</h3>
<ul>
  <li>Creating cesar_gs3 (public-read)</li>
  <?php unlink('s3://cesar_gs3/cesar_gs3_test.txt'); ?>
  <?php $e=mkdir('s3://cesar_gs3',_PUBLIC_READ);?>
  <li><?php if ($e) {?>Done<? } else {?>Error!: Amazon Said: <?php echo  "<pre>$amazonResponse</pre>"; }?></li>
</ul>
<h3 align="left">Creating a file inside cesar_gs3</h3>
<ul>
  <li>Creating file.txt</li>
  <?php 
      $f = fopen('s3://cesar_gs3/file.txt', 'w+r');
        for($i=0; $i <= 1000; $i++)
            fwrite($f, "Line #$i\n");
        rewind($f);
        fwrite($f, "Line #5\n");
    fclose($f);
  ?>
  <li><?php if ($amazonResponse=='') {?>Done<? } else {?>Error!: Amazon Said: <?php echo  "<pre>$amazonResponse</pre>"; }?></li>
</ul>
<h3 align="left">Listing the content of a directory</h3>
<ul>
  <li>Opening dir cesar_gs3</li>
  <?php 
      $e = opendir('s3://cesar_gs3');
  ?>
  <li><?php if ($amazonResponse=='') {?>Done<? } else {?>Error!: Amazon Said: <?php echo  "<pre>$amazonResponse</pre>"; }?></li>
  <?php if ($amazonResponse=='') {?>
  <li>Listing files
    <ul>
      <?php while($f = readdir($e) ) {?>
      <li><?=$f?></li>
      <? } closedir($e);?>
    </ul>
  <?php }?>
  </li>
</ul>

<h3 align="left">Getting info of a file</h3>
<ul>
  <li>stat over file.txt</li>
  <li><pre><?php print_r(stat('s3://cesar_gs3/file.txt'));?></pre></li>
</ul>

<h3 align="left">Reading a file</h3>
<ul>
  <li>Reading file.txt</li>
    <?php 
      $f = fopen('s3://cesar_gs3/file.txt', 'r');
    $t = fread($f,1000); //foo read
    rewind($f);
    $c="";
    while ( $c = fread($f, 2048 ) )
        $content.=$c;

    fclose($f);
  ?>
  <li><?php if ($e) {?>Done<? } else {?>Error!: Amazon Said: <?php echo  "<pre>$amazonResponse</pre>"; }?></li>
</ul>
<h4>Content</h4>
<pre><?=$content?></pre>
<h3 align="left">Delete a file</h3>
<ul>
  <li>deleting test.txt</li>
  <?php $e=unlink('s3://cesar_gs3/file.txt');?>
  <li><?php if ($e) {?>Done<? } else {?>Error!: Amazon Said: <?php echo  "<pre>$amazonResponse</pre>"; }?></li>
</ul>

<h3 align="left">Delete a dir</h3>
<ul>
  <li>deleting cesar_gs3</li>
  <?php $e=rmdir('s3://cesar_gs3/');?>
  <li><?php if ($e) {?>Done<? } else {?>Error!: Amazon Said: <?php echo  "<pre>$amazonResponse</pre>"; }?></li>
</ul>
</body>
</html>
