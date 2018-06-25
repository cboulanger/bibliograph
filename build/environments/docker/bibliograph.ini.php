;;<?php exit; ?>;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;; Leave the above line to make sure nobody can access this file ;;
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;; These are the server-side default configuration values
;; for your application. They are unchangeable by the application
;; and typically should not be exposed to the client.
;;
;; You can access a configuration value with
;;    $app = qcl_application_Application::getInstance();
;;    $myconfigValue = $app->getIniValue( "sectionname.keyname" );
;;
;; The section name is found in square brackets below, the key name
;; is the string before the "=" character.
;;
;; Please note:
;;   - If values contain whitespace, they have to be quoted.
;;   - values "on" and "yes" will be converted in boolean true,
;;     values "off" and "no" into boolean false.
;;   - you can use macros (see below in the section 'macros' to
;;     compose values from other values. Note that the values
;;     contained in the macro have to be defined before the macro.
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

[database]

;; database.type
;; the database type. currently, only mysql is supported and tested. Since
;; the PDO abstraction layer is used, it will be easy to support other backends.
type = mysql

;; database.host
;; the database host, usually localhost
host = 0.0.0.0

;; database.port
;; the port on which the database listens for requests, ususally 3306
port = 3306

;; database.adminname and database.adminpassw
;; The name and password of the database user. Both "normal" user and admin must be the same for the moment.
;; This user needs ALL rights in the databases named below. If you want to make backup snapshots, it also
;; needs the global RELOAD privilege
adminname  = bibliograph
adminpassw = bibliograph
username  = ${adminname}
userpassw = ${adminpassw}

;; database.admindb
;; The name of the database holding all the tables with global and
;; administrative information. Must exist before the application is started
admindb = bibliograph_admin

;; database.userdb
;; The name of the databases that contains the user data.
;; Can be the same as database.admindb, but if you have access
;; to more than one database, is recommended to keep a separate
;; database for this. Must exist before the application is started.
userdb = bibliograph_user

;; database.tmpdb
;; The name of the database holding all the tables with temporary data.
;; Can be the same as database.admindb, but if you have access
;; to more than one database, is recommended to keep a separate
;; database for this. Must exist before the application is started.
tmp_db = bibliograph_tmp

;; database.tableprefix
;; A global prefix for all tables that are created, which makes
;; it possible to keep the data of several applications in one
;; database. you can omit this if no prefix is needed.
tableprefix =

;; database.encoding
;; The default encoding scheme of the database. It is recommended
;; to use the default utf8.
encoding  = utf8

[service]

;; service.event_transport
;; Whether the server response should contain messages and events
;; for the qooxdoo application
;; values are on/off
event_transport = on

[access]
;; whether users are attached to roles directly ("yes") or have roles
;; dependent on the group they belong to ("no"), i.e. users can have
;; different roles in different groups. You can still define global
;; roles when using group-specific roles. On the other hand, if you set this
;; value to "yes", group-specific roles will be ignored.
global_roles_only = no

;; whether authentication should be possible only via https.
;; this option gets set as a read-only configuration value
;; (access.enforce_https_login) also which is available on the
;; client.
enforce_https_login = no

[email]

;; The email address of the administrator of this particular installation.
;; Must be set, otherwise setup process will not complete
admin = "nobody@example.com"

;; The email address of the developer of the application. Don't change
;; this unless you are a developer
developer = "info@bibliograph.org"

[ldap]
;; whether ldap authentication is enabled, values: yes/no
enabled = no

;; whether to use ldap groups. values yes/no
use_groups = yes

;; the host of the ldap server
host = ldap.example.com

;; the port listening for ldap connections
port = 389

;; base dn to which the user name is added for authentication
user_base_dn = "ou=people,dc=example,dc=com"

;; attribute name that is used for the user name, usually uid.
user_id_attr = uid

;; base dn of group data
group_base_dn = "ou=groups,dc=example,dc=com"

;; attribute for members of the group
member_id_attr = memberUid

;; attribute for the group name, usually description or displayName
group_name_attr = description

;; if the LDAP database only stores the user name part of the users'
;; e-mail address, you can provide the domain part here
;;mail_domain = example.com

;; ================================================================
;; Don't touch anything beyond this point
;; ================================================================

[macros]

;; DSN information. Since the ";" character cannot be used in value
;; definitions, it is replaced by "&" in the the dsn string.
dsn_user  = "${database.type}:host=${database.host}&port=${database.port}&dbname=${database.userdb}"
dsn_admin = "${database.type}:host=${database.host}&port=${database.port}&dbname=${database.admindb}"
dsn_tmp   = "${database.type}:host=${database.host}&port=${database.port}&dbname=${database.tmp_db}"

;; ================================================================
;; End
;; ================================================================