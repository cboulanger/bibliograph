<?
/**
 * Name parser class.
 *
 * @athor Christian Boulanger
 *    Converting into PHP5 class, modifications, localization
 *
 *
 */
class nameparser_NameParser
{

  /**
   * The unparsed full name
   * @var string
   */
  public $name;

  /**
   * The full salutation or honorific prefix
   * @var string
   */
  public $prefix;

  /**
   * The given name(s)
   * @var string
   */
  public $first;

  /**
   * The middle name,
   * @var string
   */
  public $middle;

  /**
   * The last name
   * @var string
   */
  public $last;

  /**
   * The (honorific) suffix
   * @var string
   */
  public $suffix;



  /**
   * The list of possible salutations. Is populated in the
   * setup() method.
   * @var array
   */
  static public $prefix_list =  array();

  /**
   * The list of possible regular expressions for last names
   * @var array
   */
  static public $lastNameRegExpr_list = array( '\S+' );

  /**
   * The list of possible suffixes. Is populated in the
   * setup() method.
   * @var array
   */
  static public $suffix_list = array();

  /**
   * An array of regular expressions to try on the full name
   * @var array
   */
  static protected $regExpr_list = array();

  /**
   * The objects containing localized data and methods
   * @var array
   */
  static protected $locale_objects = array();

  /**
   * Constructor. Takes a string and parses it into the
   * different parts
   * @param $unparsed
   * @return void
   */
  public function __construct( $name )
  {
    $this->name = trim( $name );
    $this->setup();
    usort( self::$prefix_list, array( $this, "compareByLength") );
    usort( self::$suffix_list, array( $this, "compareByLength") );
    usort( self::$lastNameRegExpr_list, array( $this, "compareByLength") );
    $this->parse();
  }

  /**
   * Will scan through the "locale" subdirectory and include the
   * scripts that are placed there. The scripts must define a class
   * named nameparser_locale_XX, XX being the name of the file without
   * the ".php" extension. The locale object can add prefixes, suffixes
   * regular expressions etc., based on the name
   *
   * @return void
   */
  protected function setup()
  {
    $locale_dir = dirname( __FILE__ ) . "/locale";
    if ( ! count( self::$locale_objects ) )
    {
      foreach( scandir( $locale_dir )  as $file )
      {
        if ( $file[0] == "." ) continue;

        /*
         * load file, instantiate locale object, which can then
         * manipulate static properties or member objects as needed
         */
        require_once "$locale_dir/$file";
        $locale = substr( $file, 0, -4 );
        $class  = "nameparser_locale_" . $locale;
        self::$locale_objects[$locale] = new $class($this);
      }
    }
  }

  public function addPrefixes( array $prefixes )
  {
    self::$prefix_list = array_unique( array_merge( self::$prefix_list, $prefixes) );
  }

  public function addSuffixes( array $suffixes )
  {
    self::$suffix_list = array_unique( array_merge( self::$suffix_list, $suffixes ) );
  }

  public function addLastNameRegExpr( array $regExpr )
  {
    self::$lastNameRegExpr_list = array_unique( array_merge( self::$lastNameRegExpr_list, $regExpr ) );
  }

  public function addRegExpr( array $regExpr )
  {
    self::$regExpr_list = array_unique( array_merge( self::$regExpr_list, $regExpr ) );
  }

  /**
   * Converts an array of strings into a alternation subpattern
   * @param array $arr
   * @return string
   */
  protected function createAlternation( array $arr )
  {
    return str_replace( ".", "\.", implode("|", $arr ) );
  }

  /**
   * Helper function for usort() to sort an array by
   * length of its entries, longer ones first
   * @param $a
   * @param $b
   * @return unknown_type
   */
  protected function compareByLength ($a, $b)
  {
      $la = strlen ($a);
      $lb = strlen ($b);
      if ($la == $lb) return (0);
      return ($la > $lb) ? -1 : 1;
  }

  /**
   * The main parsing engine. Sets the object members from
   * the given full name
   * @return void
   */
  protected function parse()
  {
    $result_match = array();
    foreach( self::$regExpr_list as $regExpr )
    {
      /*
       * insert prefixes, lastname and suffix regular expressions
       */
      $regExpr = ( str_replace( "/","", sprintf(
        $regExpr,
        $this->createAlternation( self::$prefix_list ),
        $this->createAlternation( self::$lastNameRegExpr_list ),
        $this->createAlternation( self::$suffix_list )
      ) ) );

      /*
       * execute expression
       */
      preg_match("/$regExpr/u", $this->name, $match );

      /*
       * use the match with the best result
       */
      if ( count( $match) > count( $result_match ) )
      {
        $result_match = $match;
      }
    }

    $this->prefix = trim( $result_match['prefix'] );
    $this->first  = trim( $result_match['first'] );
    $this->middle = trim( $result_match['middle'] );
    $this->last   = trim( $result_match['last'] );
    $this->suffix = trim( $result_match['suffix'] );
  }


}
?>