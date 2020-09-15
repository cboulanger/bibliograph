<?php

namespace lib\controllers;

interface IItemController {
  /**
   * Returns the requested or all accessible properties of a reference
   * @param string $datasource
   * @param $arg2 if numeric, the id of the reference
   * @param $arg3
   * @param $arg4
   * @return array
   * @throws \InvalidArgumentException
   */
  function actionItem($datasource, $arg2, $arg3 = null, $arg4 = null);

  /**
   * Returns a HTML table with the reference data
   * @param $datasource
   * @param $id
   * @return string
   */
  public function actionItemHtml($datasource, $id);
}
