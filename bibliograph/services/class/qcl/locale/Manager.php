<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2015 Christian Boulanger
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
   * The name of the directory containing all locale information
   * @var string
   */
  protected $locale_dir_name = "locale";

  /**
   * The "language" of the translations. Is always "C.UTF-8" because the actual
   * locale is switched using textdomains, not languages.
   * @var string
   */
  protected $lang = 'C.UTF-8';

  /**
   * The name of the directory containing the message catalogues
   * @var string
   */
  protected $catalog_dir_name = "LC_MESSAGES";


  /**
   * The curren locale
   * @var string
   */
  public $locale;
  
  
  /**
   * The namespace of the application for which translation is requested
   * If not set, it is automatically determined
   */
  protected $appNamespace;

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
    $lang = $this->lang;
    $localeDir = $this->getMessagesRoot() . "/" . $this->getMessagesRelativePath();
    if( is_dir( $localeDir ) )
    {
      setlocale( LC_ALL, $lang );
      putenv("LANG=$lang");
      $this->setLocale();
      //if( $this->hasLog() ) $this->logLocaleInfo();
    }
    else
    {
      $this->log( sprintf( 'Locale directory %s does not exist.', $localeDir ));
    }
    
    // subscribe to system messages
    $this->addSubscriber("qcl/access/user-authenticated","onUserAuthenticated");
	}
	
	/**
	 * subscriber function when user has authenticated
	 */
	public function onUserAuthenticated($message)
	{ 
	  try
	  {
  	  $localeFromConfig = $this->getApplication()->getPreference("application.locale");
  	  if( $localeFromConfig ) {
  	    $activeUser = $message->getData();
  	    $this->log( sprintf( "Setting locale for user '%s'", $activeUser->getName()), QCL_LOG_LOCALE);
  	    $this->setLocale($localeFromConfig);
  	  }
  	  return $localeFromConfig;
	  }
	  catch( qcl_config_Exception $e ){
	    // this is a hack to work around upgrade problems, can be removed later.
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

  public function log( $message )
  {
    if( $this->hasLog()) parent::log( $message, QCL_LOG_LOCALE );
  }

  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------

  /**
   * Getter for locale property
   * @return string
   */
  public function getLocale()
  {
    return $this->locale;
  }
  
  /**
   * Returns the topnamespace of the current application
   * @return string
   */
  protected function getAppNamespace()
  {
    if( $this->appNamespace )
    {
      return $this->appNamespace;  
    }
    $appClass = get_class( $this->getApplication() );
    $namespace = array_shift( explode("_", $appClass ) ); // TODO namespacing
    return $namespace;
  }
  
  /**
   * set the applicaiton namespace manually
   */
  public function setAppNamespace( $namespace )
  {
    $this->appNamespace = $namespace;
  }

  /**
   * Returns the textdomain for the given locale
   * @param $locale
   * @return string
   */
  public function getTextDomain( $locale )
  {
    return $this->getAppNamespace() . "_" . $locale;
  }

  /**
   * Returns the root directory in which gettext will look for locale information
   * @return string
   */
  protected function getMessagesRoot()
  {
    $app = $this->getApplication();
    if( !$this->appNamespace and $app instanceof qcl_application_plugin_IPluginApplication )
    {
      return QCL_PLUGIN_DIR . "/" . $this->getAppNamespace() . "/services/" . $this->locale_dir_name;
    }
    return realpath("./{$this->locale_dir_name}");
  }

  /**
   * Returns the relative path from the messages root to the directory containing the message catalogs
   * @return string
   */
  protected  function getMessagesRelativePath()
  {
    return $this->lang . "/" . $this->catalog_dir_name;
  }

  /**
   * Returns the absolute path to the directory containing the message catalogs
   * @return string
   */
  protected  function getMessageDirPath()
  {
    return $this->getMessagesRoot() . "/" . $this->getMessagesRelativePath();
  }

  /**
   * Returns the path for the file that contains the messages for the given locale
   * @param $locale
   * @return string
   */
  public function getMessagesFilePath( $locale )
  {
    return $this->getMessageDirPath() . "/" . $this->getTextDomain( $locale ) . ".po";
  }

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
	  
    // determine which locale to use
    if( ! $locale ) 
    {
      $locale =  $this->getUserLocale();
    }
    $this->log( "Setting locale to '$locale'", QCL_LOG_LOCALE);
    
    // application textdomain      
    $textdomain = $this->getTextDomain( $locale );
    $path = $this->getMessagesRoot();
    bindtextdomain( $textdomain, $path );
    $this->log( "Binding textdomain '$textdomain' to '$path'", QCL_LOG_LOCALE);
    if( function_exists("bind_textdomain_codeset") ) // PHP on Mac Bug
    {
      bind_textdomain_codeset( $textdomain, 'UTF-8');
    }
    // set default for _("...") function
    textdomain($textdomain);

    // qcl library textdomain is special since it is not tied to the application
    $qcl_textdomain = "qcl_" . $locale;
    $qcl_path = dirname(__FILE__);
    bindtextdomain( $qcl_textdomain, $qcl_path );
    $this->log( "Binding textdomain '$qcl_textdomain' to '$qcl_path'");
    if( function_exists("bind_textdomain_codeset") ) // PHP on Mac Bug
    {
      bind_textdomain_codeset( $qcl_textdomain, 'UTF-8');
    }

    $this->locale = $locale;
    
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
    if ( $availableLocales === null or $this->appNamespace )
    {
      $appNamespace = $this->getAppNamespace();
      $len = strlen($appNamespace);
      $availableLocales = array();
      $messageDirPath = $this->getMessageDirPath();
      if( file_exists( $messageDirPath ) )
      {
        foreach ( scandir( $messageDirPath ) as $file )
        {
          if ( substr($file,0, $len) == $appNamespace )
          {
            $availableLocales[] = substr($file, $len+1, 2);
          }
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

    if ( $className and ( count( $segments = explode( "_", $className) ) > 1 ) ) // TODO namespacing
    {
      // use textdomain bound to class
      $textdomain = $segments[0] . "_" . $this->getUserLocale();
      $translation = dgettext( $textdomain, $messageId );
      $this->log( "Translating '$messageId' into '$translation' using domain '$textdomain' (for class $className)", QCL_LOG_LOCALE);
    }
    else
    {
      // use default textdomain
      $translation = gettext( $messageId );
      $this->log( "Translating '$messageId' into '$translation'.", QCL_LOG_LOCALE);
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
    $locale = $this->getLocale();
    $this->info( "  Current locale:     $locale");
    $this->info( "  Textdomain:         " . $this->getTextDomain( $locale ) );
    $filepath = $this->getMessagesFilePath( $locale ) ;
    $this->info( "  Message catalog:    $filepath" . (file_exists($filepath)?"":" (missing!)" ) );
  }
}
