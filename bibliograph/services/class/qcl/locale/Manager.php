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
 * Manages locales and translations. uses the php gettext extension by default.
 * Language switch is not done via setting locales, but using textdomains, to avoid
 * the problems with gettext. Locale is set to "C"
 * see http://stackoverflow.com/questions/15541747/use-php-gettext-without-having-to-install-locales
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
   * The default path to the locale files
   * @var string
   */
  protected $messages_path = 'locale/C.UTF-8/LC_MESSAGES';

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
	   * set default locale
	   */
    if( is_dir( $this->messages_path ) )
    {
      setlocale( LC_ALL, 'C.UTF-8' );
      putenv("LANG=C.UTF-8");
      $this->setLocale();
      if( $this->hasLog() ) $this->logLocaleInfo();
    }
    else
    {
      if ( $this->hasLog() ) {
        $this->log( sprintf(
          'Locale directory %s does not exist.',
          realpath( $this->messages_path )
        ), QCL_LOG_LOCALE );
      }
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
    /*
     * application textdomain
     */
    $locale = either( $locale, $this->getUserLocale() );
    $textdomain = $this->getApplication()->id() . "_" . $locale;
    bindtextdomain( $textdomain, dirname(dirname($this->messages_path)));
    if( function_exists("bind_textdomain_codeset") ) // PHP on Mac Bug
    {
      bind_textdomain_codeset( $textdomain, 'UTF-8');
    }
    // set default for _("...") function
    textdomain($textdomain);

    /*
     * qcl textdomain,
     */
    $qcl_textdomain = "qcl_" . $locale;
    bindtextdomain( $qcl_textdomain, dirname(__FILE__) );
    if( function_exists("bind_textdomain_codeset") ) // PHP on Mac Bug
    {
      bind_textdomain_codeset( $qcl_textdomain, 'UTF-8');
    }

    $this->locale = $locale;
    if( $this->hasLog()) $this->log( "Setting locale '$locale'", QCL_LOG_LOCALE);
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
   * determines the user locale from the browser
   * @return
   */
  public function getUserLocale()
  {
    $browser_locale = Locale::acceptFromHttp($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
    if( $browser_locale )
    {
      $locale = substr( $browser_locale, 0, 2); // we need only the first two
      if( ! in_array( $locale, $this->getAvailableLocales() ) )
      {
        $locale = $this->default_locale;
      }
    }
    else
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
      $app_id = $this->getApplication()->id();
      $id_len = strlen($app_id);
      $availableLocales = array();
      foreach ( scandir( $this->messages_path ) as $file )
      {
        if ( substr($file,0, $id_len) == $app_id )
        {
          $availableLocales[] = substr($file, $id_len+1, 2);
        }
      }
    }
    return array_unique($availableLocales);
  }

  /**
   * Translates a message and applies sprintf formatting to it.
   * If a class name is given, the textdomain will be computed from the the class'
   * root namespace plus underscore plus locale name.
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
      // use textdomain bound to class
      $textdomain = $segments[0] . "_" . $this->getUserLocale();
      $translation = dgettext( $textdomain, $messageId );
      //if ( $this->hasLog() ) $this->log( "Translating '$messageId' into '$translation' using domain '$textdomain'", QCL_LOG_LOCALE);
    }
    else
    {
      // use default textdomain
      $translation = gettext( $messageId );
      //if ( $this->hasLog() ) $this->log( "Translating '$messageId' into '$translation'.", QCL_LOG_LOCALE);
    }
    array_unshift( $varargs, $translation );
    $result = @call_user_func_array('sprintf',$varargs);
    if( ! $result )
    {
      throw new LogicException("Invalid translation message or parameters.");
    }
    return $result;
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
      // use textdomain bound to class
      $textdomain = $segments[0] . "_" . $this->getUserLocale();
      $translation = dngettext( $textdomain, $singularMessageId, $pluralMessageId, $count );
    }
    else
    {
      // use default textdomain
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
    $this->info( "  User locale:        " . $this->getUserLocale() );
    $this->info( "  Current locale:     " . $this->getLocale() );
  }
}
