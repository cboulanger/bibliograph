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
- [x] Re-implemen Plugins as Yii2 modules
- [x] Re-implement Z3950 import
- [x] Re-implement Add/Remove/Move/Copy folders
- [x] Re-implement Remove/Move/Copy references
- [x] Implement move/copy references with drag & drop
- [x] Re-implement deleting references and emptying trash
- [ ] Re-implement extended fields datasource

## v3.0.0-beta 

### Priority: high
- [ ] Add distribution mechanism
- [ ] module autoloader requires debug mode (trying to serialize closures breaks app)
- [ ] Tests: Make Travis ~~great~~ work again

### Priority: normal
- [ ] Add "locale" property to search folders for parsing queries
- [ ] Clear up the conceptual mess concerning "schema": datasource schema/reference schema
- [ ] Re-implement Export
- [ ] Move static methods out of \app\models\Datasource into DatasourceManager component
- [ ] Replace calls to Yii::$app->eventQueue->add() with calls to message API
- [ ] Frontend: Rename item view "metadata" page
- [ ] Backend: Model validation: accept booleans for MySql SmallInt columns
- [ ] Add missing translations
- [ ] Add log email target to report fatal errors, adapt UserErrorException to report fatal errors via email
- [ ] Make UserErrorException a JSONRPC error which is caught on the client, instead of a Dialog event. 
- [ ] Re-implement "New User" & "New Datasource" in Access Control Tool and System dialogs
- [ ] Add "Re-install modules" button in Systems menu
- [ ] Remove migrations table for deleted datasources

### Priority: low
- [ ] Add "serverOnly" column to data_Config (true/false/null) and remove from config data sent to client
- [ ] Backend: Streamline API to get Datasource & typed model
- [ ] Frontend: Convert static icon resouce paths into aliases that are resolved in bibliograph.theme.Icon
- [ ] Frontend: Rename widget ids, replace with native qooxdoo ids, see https://github.com/qooxdoo/qooxdoo/issues/9422
- [ ] Add correct @return information to the JSONRPC methods/actions
- [ ] Change app state separator and assignment chars
- [ ] Re-implement Table Actions Interface
- [ ] Add compiler-based splash screen for loading
- [ ] Re-implement reference editor title label

### Code cleanup
- [ ] Replace message names with constants
- [ ] Tests: Fix bootstrap loading issue
- [ ] \app\controllers\AppController::require* methods should throw a specialized
      Exception (i.e. MethodNotAllowedException) instead of \JsonRpc2\Exception
- [ ] Add @jsonrpc controller-id/action-id tag in controller actions
- [ ] Replace ::findOne(['namedid'=>'foo']) with ::findByNamedId('foo')
- [ ] Rename Yii::$app->utils to Yii::$app->state
- [ ] Use UserErrorException consistently and replace \Exception
- [ ] Have actions return an informative message when they don't return a result
- [ ] Move the .idea folder out of version control (see [instructions](https://intellij-support.jetbrains.com/hc/en-us/articles/207240985-Changing-IDE-default-directories-used-for-config-plugins-and-caches-storage))
- [ ] Fix 'property is unused' inspection alert
- [ ] Add missing id, created, modified to model rules()
- [ ] Remove qx prefix in generated code (s/qx([A-Z])/\L$1/)
- [ ] Convert config to YAML: https://packagist.org/packages/sergeymakinen/yii2-config

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
- [ ] Implement boolean database columns (supported by Yii)
- [ ] Reimplement HTML editor integration for notes

### Priority: low
- [ ] Enable print item view: bibliograph.ui.main.ItemView#print()