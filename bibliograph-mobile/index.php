<?php
if( $_REQUEST['isbn'] )
{
  header( "location: ./build/#isbn." . $_REQUEST['isbn'] );
  exit;
}
header( "location: ./build/" );