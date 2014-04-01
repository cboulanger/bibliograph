<?php

/**
 * Interface for classes checking the  accessibility of service object.
 *
 * @author Derrell Lipman
 * @author Christian Boulanger
 *
 * @todo AccessibilityBehaviors should throw exceptions instead of returning false
 */
interface IAccessibilityBehavior
{
  /**
   * Check accessibility of a service class by doing referrer checking
   * There is a global default which can be overridden by
   * each service for a specified method.
   * @param object $serviceObject
   * @param string $method Method for which the accessibility should be checked
   * @return bool True if accessible, false if not.
   */
  function checkAccessibility( $serviceObject, $method );

  function getErrorMessage();

  function getErrorNumber();
}
?>