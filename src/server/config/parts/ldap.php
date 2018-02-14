<?php
$ini = require('ini.php');
$ldap = (object) $ini['ldap'];
if( ! $ldap->enabled ) return;
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
    'default' => [ 
      // Connect this provider on initialisation of the LdapWrapper Class automatically
      // Must be false otherwise the app will break if the server isn't available
      'autoconnect' => false,

      // The provider's schema. 
      // You can make your own https://github.com/Adldap2/Adldap2/blob/master/docs/schema.md 
      // or use one from https://github.com/Adldap2/Adldap2/tree/master/src/Schemas
      // Example to set it to OpenLDAP:
      'schema' => new \Adldap\Schemas\OpenLDAP(),

      // The config has to be defined as described in the Adldap2 documentation.
      // https://github.com/Adldap2/Adldap2/blob/master/docs/configuration.md
      'config' => [
        
        // You can use the host name or the IP address of your controllers.
        'domain_controllers' => [$ldap->host],

        // the port 
        'port' => $ldap->port,

        // Your base DN. This is usually your account suffix.
        'base_dn' => $ldap->user_base_dn,

        // The account to use for 
        //   a) comnecting / querying (This usually does not need to be an actual admin account)
        //   b) modifying / creating users (An account with admin priviledges is required)
        // See https://github.com/Adldap2/Adldap2/tree/master/docs
        'admin_username' => $ldap->bind_dn,
        'admin_password' => $ldap->bind_password,

        // The account suffix, if set, will be added to all CNs
        'account_suffix' => "," . $ldap->user_base_dn,
        
        // To enable SSL/TLS read 
        // https://github.com/edvler/yii2-adldap-module/blob/master/docs/SSL_TLS_AD.md
        // and uncomment the variables below
        //'use_ssl' => true,
        //'use_tls' => true,             
        
        // Optional Configuration Options
        //'account_prefix'        => 'ACME-',
        //'admin_account_prefix'  => 'ACME-ADMIN-',
        //'admin_account_suffix'  => '@acme.org',
        //'follow_referrals'      => false,
        //'timeout'               => 5,
        
        // Custom LDAP Options
        // See: http://php.net/ldap_set_option
        //'custom_options'        => [
        //]        
      ]
    ]
  ], // close providers array
];