<?php
$ini = require('ini.php');
$db = (object) $ini['ldap'];
return  [
  'class' => 'Edvlerblog\Adldap2\Adldap2Wrapper',

  /*
    * Set the default provider to one of the providers defined in the
    * providers array.
    *
    * If this is commented out, the entry 'default' in the providers array is
    * used.
    *
    * See https://github.com/Adldap2/Adldap2/blob/master/docs/connecting.md
    * Setting a default connection
    *
    */
    // 'defaultProvider' => 'another_provider',

  /*
    * Adlapd2 can handle multiple providers to different Active Directory sources.
    * Each provider has it's own config.
    *
    * In the providers section it's possible to define multiple providers as listed as example below.
    * But it's enough to only define the "default" provider!
    */
  'providers' => [
    /*
      * Always add a default provider!
      *
      * You can get the provider with:
      * $provider = \Yii::$app->ldap->getDefaultProvider();
      * or with $provider = \Yii::$app->ldap->getProvider('default');
      */
    'default' => [ //Providername default
      // Connect this provider on initialisation of the LdapWrapper Class automatically
      'autoconnect' => true,

      // The provider's schema. Default is \Adldap\Schemas\ActiveDirectory set in https://github.com/Adldap2/Adldap2/blob/master/src/Connections/Provider.php#L112
      // You can make your own https://github.com/Adldap2/Adldap2/blob/master/docs/schema.md or use one from https://github.com/Adldap2/Adldap2/tree/master/src/Schemas
      // Example to set it to OpenLDAP:
      // 'schema' => new \Adldap\Schemas\OpenLDAP(),

      // The config has to be defined as described in the Adldap2 documentation.
      // https://github.com/Adldap2/Adldap2/blob/master/docs/configuration.md
      'config' => [
        // The account suffix, usually the mail domain
        'account_suffix' => "@" . $ldap->mail_domain,

        // You can use the host name or the IP address of your controllers.
        'domain_controllers' => [$ldap->host],

        // the port 
        'port' => $ldap->port,

        // Your base DN. This is usually your account suffix.
        'base_dn' => $ldap->user_base_dn,

        // The account to use for querying / modifying users. This
        // does not need to be an actual admin account.
        //'admin_username' => 'username_ldap_access',
        //'admin_password' => 'password_ldap_access!',

        // To enable SSL/TLS read https://github.com/edvler/yii2-adldap-module/blob/master/docs/SSL_TLS_AD.md
        // and uncomment the variables below
        // @todo move into bibliograph.ini.php
        //'use_ssl' => true,
        //'use_tls' => true,                                
      ]
    ]
  ], // close providers array
];