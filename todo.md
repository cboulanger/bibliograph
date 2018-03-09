# TODO

## v3.0.0-alpha
- [x] Add event transport on the level of the reponse, not the service
- [x] Repair event dispatch
- [x] Re-implement setup
- [x] Re-implement LDAP support
- [x] Update docs to allow test installation at Humboldt-University
- [x] Re-implement datasource restrictions and access check
- [x] Re-implement user error handling: New user error without stack trace, alert to user and log it
- [x] Re-implement ACL Tool
- [x] Add column 'protected' to user/group etc. to prevent records from being deleted
- [x] Re-implement Datasource Schemas
- [x] Re-implement Client UI updates via events (e.g. in editor)
- [x] Re-implement cql search
- [x] Re-implement translation (http://www.yiiframework.com/doc-2.0/guide-tutorial-i18n.html)
- [ ] Re-implemen Plugins as Yii2 modules
- [ ] Re-implement Z3950 import
- [ ] Add "locale" property to search folders for parsing queries
- [ ] Re-implement Remove/Move/Copy references
- [ ] Re-implement Add/Remove/Move/Copy folders
- [ ] Re-implement Export
- [ ] Tests: Make Travis ~~great~~ work again

## v3.0.0-beta 

### Priority: high
- [ ] Add distribution mechanism 

### Priority: normal
- [ ] Frontend: Rename item view "metadata" page
- [ ] Backend: Model validation: accept booleans for MySql SmallInt columns
- [ ] Implement reference model schema
- [ ] Add missing translations
- [ ] Re-implement reference editor title label
- [ ] Use UserErrorException consistently and replace \Exception
- [ ] Add log email target to report fatal errors, adapt UserErrorException to report fatal errors via email

### Priority: low
- [ ] Backend: Streamline API to get Datasource & typed model
- [ ] Frontend: Convert static icon resouce paths into aliases that are resolved in bibliograph.theme.Icon
- [ ] Frontend: Rename widget ids, replace with native qooxdoo ids, see https://github.com/qooxdoo/qooxdoo/issues/9422
- [ ] Add correct @return information to the JSONRPC methods/actions
- [ ] Change app state separator and assignment chars
- [ ] Re-implement Table Actions Interface

### Code cleanup
- [ ] Remove unnecessary packages from npm and composer
- [ ] Tests: Fix bootstrap loading issue
- [ ] \app\controllers\AppController::require* methods should throw a specialized
      Exception (i.e. MethodNotAllowedException) instead of \JsonRpc2\Exception
- [ ] Add @jsonrpc controller-id/action-id tag in controller actions
- [ ] Replace ::findOne(['namedid'=>'foo']) with ::findByNamedId('foo')
- [ ] Rename Yii::$app->utils to Yii::$app->state
- [ ] Have actions return an informative message when they don't return a result
- [ ] Move the .idea folder out of version control (see [instructions](https://intellij-support.jetbrains.com/hc/en-us/articles/207240985-Changing-IDE-default-directories-used-for-config-plugins-and-caches-storage))
- [ ] Fix 'property is unused' inspection alert
- [ ] Add missing id, created, modified to model rules()

## v3.0.0

## v3.1

### Priority: high
- [ ] Re-implement Backup plugin
- [ ] Re-enable item view / formatted item
- [ ] Re-enable item view / record info
- [ ] Re-enable item view / duplicates search
- [ ] Re-enable system menu commands
- [ ] Re-implement message broadcast

### Priority: normal
- [ ] Reimplement HTML editor integration for notes

### Priority: low
- [ ] Enable print item view: bibliograph.ui.main.ItemView#print()