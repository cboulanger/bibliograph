<?php
/**
 * Yii bootstrap file.
 * Used for enhanced IDE code autocompletion.
 */
class Yii extends \yii\BaseYii
{
  /**
   * @var WebApplication
   */
  public static $app;
}

/**
 * Class WebApplication
 * Include only Web application related components here
 *
 * @property \yii\web\User $user
 *    The user component. This property is read-only. Extended component.
 * @property \yii\log\FileTarget $log
 *    The log component
 * @property \lib\components\EventTransportResponse $response
 *    The response component. This property is read-only. Extended component.
 * @property \lib\components\Configuration $config
 *    Configuration component, managing file-based initialization (.ini) values and
 *    config data stored in the database
 * @property \Edvlerblog\Adldap2\Adldap2Wrapper $ldap
 *    A LDAP library wrapper class
 * @property \lib\components\LdapAuth $ldapAuth
 *    A component that handles LDAP authentication
 * @property \lib\components\AccessManager $accessManager
 *    A components with useful methods for access management
 * @property \lib\components\DatasourceManager $datasourceManager
 *    A component that manages creation, manipulation and deletion of datasources
 * @property \lib\components\EventQueue $eventQueue
 *    A component that manages the event queue
 * @property \lib\components\Utils $utils
 *    Will be renamed into "state"
 * @property \odannyc\Yii2SSE\LibSSE $sse
 *    Server-side events. Not working
 * @property \lib\channel\Component $channel
 *    Message channels. Not working
 */
class WebApplication extends yii\web\Application
{
}