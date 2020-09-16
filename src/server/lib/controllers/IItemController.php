<?php

namespace lib\controllers;

interface IItemController {
  /**
   * Returns the requested or all accessible properties of a reference
   * @param string $datasourceId
   * @param mixed $arg2
   * @param mixed $arg3
   * @param mixed $arg4
   * @return array
   * @throws \InvalidArgumentException
   */
  function actionItem($datasourceId, $arg2, $arg3 = null, $arg4 = null);

  /**
   * Returns a HTML table with the reference data
   * @param string $datasourceId
   * @param string|int $itemId
   * @return string
   */
  public function actionItemHtml($datasourceId, $itemId);
}
