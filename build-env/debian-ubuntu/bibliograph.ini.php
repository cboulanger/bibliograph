;;<?php exit; ?>;

[database]

;; database.type
;; the database type. currently, only mysql is supported and tested. Since
;; the PDO abstraction layer is used, it will be easy to support other backends.
type = mysql

;; database.host
;; the database host, usually localhost
host = localhost

;; database.port
;; the port on which the database listens for requests, ususally 3306
port = 3306

;; database.adminname and database.adminpassw
;; The name and password of the database user. Both "normal" user and admin must be the same for the moment.
;; This user needs ALL rights in the databases named below. If you want to make backup snapshots, it also
;; needs the global RELOAD privilege
adminname  = root
adminpassw = 

;; you usually do not have to change the following settings
username  = ${adminname}
userpassw = ${adminpassw}
admindb = bibliograph
userdb = bibliograph
tmp_db = bibliograph
tableprefix =
encoding  = utf8

[service]
event_transport = on

[access]
global_roles_only = no
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

;; attribute for the group name, usually description or displayName
group_name_attr = description

;; attribute for members of the group, was called member_id_attr earlier
group_member_attr = memberUid

;; if the LDAP database only stores the user name part of the users'
;; e-mail address, you can provide the domain part here
mail_domain = example.com