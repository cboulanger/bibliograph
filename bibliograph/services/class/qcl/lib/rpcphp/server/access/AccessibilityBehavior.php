<?php

/*
 * Method Accessibility values
 *
 * Possible values are:
 *
 *   "public" -
 *     The method may be called from any session, and without any checking of
 *     who the Referer is.
 *
 *   "domain" -
 *     The method may only be called by a script obtained via a web page
 *     loaded from this server.  The Referer must match the request URI,
 *     through the domain part.
 *
 *   "session" -
 *     The Referer must match the Referer of the very first RPC request
 *     issued during the session.
 *
 *   "fail" -
 *     Access is denied
 */

require_once dirname(__FILE__) . "/IAccessibilityBehavior.php";

define("Accessibility_Public",             "public");
define("Accessibility_Domain",             "domain");
define("Accessibility_Session",            "session");
define("Accessibility_Fail",               "fail");

/**
 * Default accessibility for methods when not overridden by the service class.
 */
if ( ! defined("defaultAccessibility") )
{
  define("defaultAccessibility",  Accessibility_Domain);
}

/**
 * Class for checking the  accessibility of service object. To change
 * the behavior of the accessibility of RPC object, subclass this
 * class and use AbstractServer::setAccessibilityBehavior() to configure
 * the server with the subclass.
 *
 * @author Derrell Lipman
 * @author Christian Boulanger
 *
 * @todo AccessibilityBehaviors should throw exceptions instead of returning false
 */
class AccessibilityBehavior implements IAccessibilityBehavior
{

  /**
   * Error message
   * @var string
   */
  var $error_message;

  /**
   * Error number
   */
  var $error_number;

  /**
   * Server object
   * @var AbstractServer
   */
  var $server;

  /**
   * Constructor
   * @param AbstractServer $server
   */
  function __construct( $server )
  {
    $this->server = $server;
  }

  /**
   * Check accessibility of a service class by doing referrer checking
   * There is a global default which can be overridden by
   * each service for a specified method.
   * @param object $serviceObject
   * @param string $method Method for which the accessibility should be checked
   * @return bool True if accessible, false if not.
   */
  function checkAccessibility( $serviceObject, $method )
  {

    /*
     * Assign the default accessibility
     */
    $accessibility = defaultAccessibility;

    /*
     * See if there is a "GetAccessibility" method in the class.
     * If there is, it should take two parameters: the method name
     * and the default accessibility, and return one of the
     * Accessibililty values.
     */
    if ( method_exists($serviceObject, "GetAccessibility") )
    {
      /*
       * Yup, there is.  Get the accessibility for the requested method
       */
      $accessibility = $serviceObject->GetAccessibility($method, $accessibility);
    }

    /*
     * Do the accessibility test.
     */
    switch( $accessibility )
    {
      case Accessibility_Public:
        /* Nothing to do.  The method is accessible. */
        break;

      case Accessibility_Domain:
        /* Determine the protocol used for the request */
        if (isset($_SERVER["SSL_PROTOCOL"]))
        {
            $requestUriDomain = "https://";
        }
        else
        {
            $requestUriDomain = "http://";
        }

        // Add the server name
        $requestUriDomain .= $_SERVER["SERVER_NAME"];

        // The port number optionally follows.  We don't know if they manually
        // included the default port number, so we just have to assume they
        // didn't.
        if ((! isset($_SERVER["SSL_PROTOCOL"]) && $_SERVER["SERVER_PORT"] != 80) ||
            (  isset($_SERVER["SSL_PROTOCOL"]) && $_SERVER["SERVER_PORT"] != 443))
        {
            // Non-default port number, so append it.
            $requestUriDomain .= ":" . $_SERVER["SERVER_PORT"];
        }

        /* Get the Referer, up through the domain part */
        if (preg_match("#^(https?://[^/]*)#", $_SERVER["HTTP_REFERER"], $regs) === false)
        {
            /* unrecognized referer */
            $this->error_number = JsonRpcError_PermissionDenied;
            $this->error_message = "Permission Denied [2]";
            return false;
        }

        /* Retrieve the referer component */
        $refererDomain = $regs[1];

        /* Is the method accessible? */
        if ($refererDomain != $requestUriDomain)
        {
            /* Nope. */
            $this->error_number  = JsonRpcError_PermissionDenied;
            $this->error_message = "Permission Denied [3]";
            return false;
        }

        /* If no referer domain has yet been saved in the session... */
        if (! isset($_SESSION["session_referer_domain"]))
        {
            /* ... then set it now using this referer domain. */
            $_SESSION["session_referer_domain"] = $refererDomain;
        }
        break;

      case Accessibility_Session:
        /* Get the Referer, up through the domain part */
        if (preg_match("#(((http)|(https))://[^/]*)(.*)#",
                 $_SERVER["HTTP_REFERER"],
                 $regs) === false)
        {
            /* unrecognized referer */
            $this->error_number  = JsonRpcError_PermissionDenied;
            $this->error_message = "Permission Denied [4]";
            return false;
        }

        /* Retrieve the referer component */
        $refererDomain = $regs[1];

        /* Is the method accessible? */
        if (isset($_SESSION["session_referer_domain"]) &&
            $refererDomain != $_SESSION["session_referer_domain"])
        {
            /* Nope. */
            $this->error_number  = JsonRpcError_PermissionDenied;
            $this->error_message = "Permission Denied [5]";
            return false;
        }
        else if (! isset($_SESSION["session_referer_domain"]))
        {
            /* No referer domain is yet saved in the session.  Save it. */
            $_SESSION["session_referer_domain"] = $refererDomain;
        }

        break;

      case Accessibility_Fail:
        $this->error_number  = JsonRpcError_PermissionDenied;
        $this->error_message = "Permission Denied [6]";
        return false;

      default:
        /* Service's GetAccessibility() function returned a bogus value */
        $this->error_number  = JsonRpcError_PermissionDenied;
        $this->error_message = "Service error: unknown accessibility.";
        return false;
    }

    /*
     * we made it to here. method is accessible
     */
    return true;
  }

  function getErrorMessage()
  {
    return $this->error_message;
  }

  function getErrorNumber()
  {
    return $this->error_number;
  }
}
?>