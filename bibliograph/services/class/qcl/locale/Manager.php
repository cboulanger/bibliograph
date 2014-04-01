<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */


/**
 * Manages locales and translations. uses the php gettext
 * extension by default.
 * @see http://mel.melaxis.com/devblog/2005/08/06/localizing-php-web-sites-using-gettext/
 * TODO: we need only one persistent object per user, but this is instantiated for each and every request.
 */
class qcl_locale_Manager extends qcl_core_Object
{

	//-------------------------------------------------------------
  // class variables
  //-------------------------------------------------------------

  /**
   * The default locale
   * @var string
   */
  public $default_locale = "en";

  /**
   * The curren locale
   * @var string
   */
  public $locale;

	//-------------------------------------------------------------
  // setup
  //-------------------------------------------------------------

	/**
 	* constructor
 	*/
	public function __construct()
	{
	  if ( ! function_exists("gettext") )
	  {
	    throw new JsonRpcException("You must install the php5-gettext extension.");
	  }

  	/*
  	 * initialize parent class
  	 */
	  parent::__construct();

	  /*
	   * bind textdomains and set application textdomain
	   * as default domain
	   */
    $appId = $this->getApplication()->id();
    $i18nAppPath = './locale';
    if( is_dir($i18nAppPath) )
    {

      $path = bindtextdomain( $appId, $i18nAppPath );
      if( function_exists("bind_textdomain_codeset") ) // PHP on Mac Bug
      {
        bind_textdomain_codeset( $appId, 'UTF-8');
      }
      textdomain($appId);
      if ( $this->hasLog() ) $this->log( "textdomain path for '$appId': '$path'", QCL_LOG_LOCALE );

      /*
       * bind qcl textdomain
       */
      $path = bindtextdomain( "qcl", dirname(__FILE__) );
      if( function_exists("bind_textdomain_codeset") ) // PHP on Mac Bug
      {
        bind_textdomain_codeset( "qcl", 'UTF-8');
      }
      if ( $this->hasLog() ) $this->log( "qcl textdomain path: '$path'", QCL_LOG_LOCALE );

      /*
       *  automatically determine locale
       */
      $this->setLocale();
    }
    else
    {
      if ( $this->hasLog() ) $this->log( sprintf(
        'Directory %s missing in services directory for i18n.',
        $i18nAppPath
      ), QCL_LOG_LOCALE );
    }
	}

	/**
	 * Return singleton instance of this class
	 * @return qcl_locale_Manager
	 */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }

  //-------------------------------------------------------------
  // logging
  //-------------------------------------------------------------

  public function hasLog()
  {
    return $this->getLogger()->isFilterEnabled( QCL_LOG_LOCALE );
  }

  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------


  /**
   * sets the default locale. if no value is given, the locale is determined on
   * system and browser settings.
   *
   * @param $locale mixed
   *    Locale string or null if locale should be automatically be
   *    determined.
   * @return void
   */
	public function setLocale($locale=null)
	{
    $locale = either( $locale, $this->getUserLocale() );
    $locales = array( $locale );
    if ( ! strstr( $locale, "_" ) )
    {
      $loc = $locale . "_" . strtoupper( $locale );
      $locales[] = $loc;
      $locales[] = $loc . ".UTF8";
    }
    else
    {
      $locales[] = $locale . ".UTF8";
    }

    if ( ! $systemLocale = setlocale( LC_MESSAGES, $locales ) )
    {
    	$this->log( "setlocale() returned false for '" . implode( ",", $locales ) . "' - check your server's i18n settings! Falling back to '$locale'", QCL_LOG_LOCALE);
    	$systemLocale = $locale;
    }
    
    putenv("LC_MESSAGES=$systemLocale");
    $this->locale = $systemLocale;
    if ( $this->hasLog() )
    {
      $this->log( "Setting locale '$systemLocale'", QCL_LOG_LOCALE);
      $this->logLocaleInfo();
    }
	}

	/**
	 * Getter for locale property
	 * @return string
	 */
	public function getLocale()
	{
	  return $this->locale;
	}

  /**
   * determines the user locale from the system or browser
   * @return
   */
  public function getUserLocale()
  {
    $browser_locales = explode(",", $_SERVER["HTTP_ACCEPT_LANGUAGE"] );
    $locale = null;
    foreach ( $browser_locales as $brlc )
    {
      $lc = strtolower( substr( $brlc, 0, 2 ) );
      if ( in_array( $lc, $this->getAvailableLocales() ) )
      {
        $locale = $lc;
        break;
      }
    }

    if ( ! $locale )
    {
       $system_locale = getenv("LANGUAGE");
       if ( $system_locale )
       {
         $locale = substr( $system_locale, 0, 2 );
       }
    }

    if ( ! $locale )
    {
      $locale = $this->default_locale;
    }
    return $locale;
  }


  /**
   * Return the available application locales
   * @return array of available locales
   */
  public function getAvailableLocales()
  {
    static $availableLocales = null;
    if ( $availableLocales === null )
    {
      $availableLocales = array();
      foreach ( scandir( "./locale" ) as $dir )
      {
        if ( $dir[0] != "." )
        {
          $availableLocales[] = $dir;
        }
      }
    }
    return $availableLocales;
  }

  /**
   * Translates a message and applies sprintf formatting to it.
   * The gettext domain is taken from the first segment of the class
   * name. Class foo_bar_Baz will use translations of
   * domain "foo", stored in "foo/class/locale/xx/LC_MESSAGES/foo.po".
   *
   * @param string $messageId
   *    Message id (may contain format strings)
   * @param array $varargs
   *    Optional arguments applied to the format string
   * @param string $className
   *    If given, use the first segment of the class name as domain
   *    for the translation.
   * @return string
   */
  public function tr( $messageId, $varargs=array(), $className=null )
  {
    if ( $className and ( count( $segments = explode( "_", $className) ) > 1 ) )
    {
      $translation = dgettext( $segments[0], $messageId );
      //if ( $this->hasLog() ) $this->log( "Translating '$messageId' into '$translation' using domain '$segments[0]'", QCL_LOG_LOCALE);
    }
    else
    {
      $translation = gettext( $messageId );
      //if ( $this->hasLog() ) $this->log( "Translating '$messageId' into '$translation'.", QCL_LOG_LOCALE);
    }
    array_unshift( $varargs, $translation );
    return call_user_func_array('sprintf',$varargs);
  }

  /**
   * Like tr(), but translate a plural message.
   * Depending on the third argument the plural or the singular form is
   * chosen.
   *
   * @param string $singularMessageId
   *    Message id of the singular form (may contain format strings)
   * @param string $pluralMessageId
   *    Message id of the plural form (may contain format strings)
   * @param int $count
   *    If greater than 1 the plural form otherwhise the singular form is returned.
   * @param array $varargs
   *    (optional) Variable number of arguments for the sprintf formatting
   * @param string $className
   *    If given, use the first segment of the class name as domain
   *    for the translation.
   * @return string
   */
  public function trn ( $singularMessageId, $pluralMessageId, $count, $varargs=array(), $className=null )
  {
    if ( $className and ( count( $segments = explode( "_", $className) ) > 1 ) )
    {
      $translation =  dngettext( $segments[0], $singularMessageId, $pluralMessageId, $count );
    }
    else
    {
      $translation =  ngettext( $singularMessageId, $pluralMessageId, $count );
    }
    array_unshift( $varargs, $translation );
    return call_user_func_array('sprintf',$varargs);
  }

  /**
   * dumps information on the translation engine to the log
   * @return void
   */
  public function logLocaleInfo()
  {
    $this->info( "Locale information: ");
    $this->info( "  Available locales:  " . implode(",", $this->getAvailableLocales() ) );
    $this->info( "  Browser locales :   " . $_SERVER["HTTP_ACCEPT_LANGUAGE"]  );
    $this->info( "  System locale :     " . getenv("LANGUAGE") );
    $this->info( "  User locale:        " . $this->getUserLocale() );
    $this->info( "  Current locale:     " . $this->getLocale() );
  }
}
?>