;;<?php exit; ?>;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;; Leave the above line to make sure nobody can access this file ;;
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

[database]
type = mysql
host = localhost
port = 3306
adminname  = root
adminpassw = 
username  = ${database.adminname}
userpassw = ${database.adminpassw}

admindb = tests
userdb = tests
tmp_db = tests
tableprefix =
encoding  = utf8

[service]
event_transport = on

[access]
global_roles_only = no
enforce_https_login = no

[email]
admin = "info@bibliograph.org"
developer = "info@bibliograph.org"

[ldap]
enabled = yes
use_groups = yes
host = ldap.forumsys.com
port = 389
bind_dn = "cn=read-only-admin"
bind_password = password
user_base_dn = "dc=example,dc=com"
user_id_attr = uid
group_base_dn = "dc=example,dc=com"
member_id_attr = memberUid
group_name_attr = description
mail_domain = example.com
