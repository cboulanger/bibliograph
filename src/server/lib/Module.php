<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 08.03.18
 * Time: 18:52
 */

namespace lib;
use ReflectionClass;
use Yii;

/**
 * Class Module
 * @package lib
 * @property string $name
 * @property boolean $enabled
 *    Whether the module ist enabled
 * @property boolean $installed
 *    Whether the module has already been installed, i.e. the install() method
 *    has been called.
 * @property string $version
 * @property string $installedVersion
 * @property string $configKeyPrefix
 * @property string $configKeyEnabled
 *
 */
class Module extends \yii\base\Module
{

  /**
   * @var bool Wether the module has already been initialized.
   */
  protected $initialized = false;

  /**
   * A string constant defining the category for logging and translation
   * Should be overridden by subclasses
   */
  const CATEGORY="module";

  /**
   * The name of the module
   * @var string
   */
  protected $name="";

  /**
   * The code version of the module
   * @var string
   */
  protected $version = "";

  /**
   * Whether the module is disabled and should not be installed
   * @var bool
   */
  public $disabled = false;

  /**
   * An array of strings containing error messages.
   * @var array
   */
  public $errors = [];

  /**
   * Returns the name of the module or the class name if no name has been defined.
   * @return string
   */
  public function getName()
  {
    return $this->name ? $this->name : static::class;
  }

  /**
   * @return string
   */
  public function getConfigKeyPrefix()
  {
    return "modules.{$this->id}.";
  }

  /**
   * @return string
   */
  public function getConfigKeyEnabled()
  {
    return $this->configKeyPrefix . "enabled";
  }

  /**
   * Getter for code version
   * @return string
   */
  public function getVersion()
  {
    return $this->version;
  }

  /**
   * @return bool
   */
  public function getInstalled()
  {
    return Yii::$app->config->keyExists($this->configKeyEnabled);
  }

  /**
   * @return bool
   */
  public function getEnabled()
  {
    return $this->installed && Yii::$app->config->getPreference($this->configKeyEnabled);
  }

  /**
   * @param $value
   */
  public function setEnabled( $value)
  {
    Yii::$app->config->setPreference($this->configKeyEnabled,$value);
  }

  /**
   * Return the version of the module as it was last installed or an empty string if not installed
   * @return string
   */
  public function getInstalledVersion()
  {
    return (
      Yii::$app->config->keyExists($this->configKeyPrefix . "version") )?
      Yii::$app->config->getPreference($this->configKeyPrefix . "version") : "";
  }

  /**
   * @inheritDoc
   * @throws \ReflectionException
   */
  public function init()
  {
    if ($this->initialized) {
      $module_name = get_called_class();
      Yii::warning("Module '$module_name' has already been initialized.");
      return;
    }
    //$not = $this->enabled ? "" : "not";
    //Yii::debug("Module '{$this->id}' is $not enabled.", __METHOD__, __METHOD__);
    parent::init();

    // add translations
    Yii::$app->i18n->translations[static::CATEGORY] = [
      'class' => \yii\i18n\GettextMessageSource::class,
      'basePath' => dirname((new ReflectionClass($this))->getFileName()) . "/messages",
      'catalog' => 'messages',
      'useMoFile' => false
    ];
    $this->initialized = true;
  }

  /**
   * Returns true if the module has already been initialized
   * @return bool
   */
  public function isInitialized() {
    return $this->initialized;
  }

  /**
   * Initializes the module unless it has already been initialized
   * @throws \ReflectionException
   */
  public function safeInit() {
    if (!$this->initialized) {
      $this->init();
    }
  }


  /**
   * Overriding methods must call `parent::install()` when installation succeeds.
   * @param boolean $enabled
   *    Whether the module should be enabled after installation (defaults to false)
   * @return bool
   */
  public function install($enabled=false)
  {
    if( ! Yii::$app->config->keyExists($this->configKeyEnabled) ){
      Yii::$app->config->createKey($this->configKeyEnabled,"boolean",false, $enabled);
    } else {
      Yii::$app->config->setKeyDefault($this->configKeyEnabled,$enabled);
    }
    if( ! Yii::$app->config->keyExists($this->configKeyPrefix . "version") ){
      Yii::$app->config->createKey($this->configKeyPrefix . "version","string",false, $this->version);
    } else {
      Yii::$app->config->setKeyDefault($this->configKeyPrefix . "version", $this->version);
    }
    return true;
  }

  /**
   * Adds the preference namespaced with the module's prefix
   * @param $key
   *     The name ("key") of the config value
   * @param mixed $default
   *     The default value
   * @param boolean $customize
   *     If true, allow users to create their
   *     own variant of the configuration setting
   * @param bool $final
   *     If true, the value cannot be modified after creation
   * @return bool True if preference was added, false if preference already existed
   * @throws \InvalidArgumentException
   */
  public function addPreference($key, $default, $customize=false, $final=false  )
  {
    return Yii::$app->config->addPreference( $this->configKeyPrefix . $key, $default, $customize, $final );
  }

  /**
   * Returns the preference namespaced with the module's prefix
   * @param string $key
   * @return mixed
   */
  public function getPreference($key)
  {
    return Yii::$app->config->getPreference( $this->configKeyPrefix . $key );
  }

  /**
   * Returns the preference namespaced with the module's prefix
   * @param string $key
   * @param mixed $value
   * @return void
   * @throws \InvalidArgumentException
   */
  public function setPreference($key, $value )
  {
    Yii::$app->config->setPreference( $this->configKeyPrefix . $key, $value );
  }
}
