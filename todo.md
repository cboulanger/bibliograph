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
- [x] Re-implement extended fields datasource
- [x] Move the .idea folder out of version control
- [x] Fix Drag & Drop
- [x] Translate ini files for tests to .toml
- [x] Add & test packaging scripts mechanism
- [x] BUG: Drag & Drop is enabled in table for anonymous! - not reproduceable
- [x] BUG: Title label is not updated
- [x] BUG: Datasource list window is not updated after adding datasource
- [x] FEAT: Reimplement options window
- [x] FEAT: Reimplement field selection for editor
- [x] BUG: Fix LDAP authentication
- [x] BUG: Fix translation of search words 
- [x] FEAT: Add "Orphaned" folder
- [x] BUG: Fix backend locale switching
- [x] BUG: Suppress z3950 permission error during setup
- [x] FEAT: implement virtual folders with indexes 
- [ ] BUG: Search doesn't work on rewi


## v3.0.0-beta 

### Priority: high
- [ ] FEAT: Check log email target to report fatal errors
- [ ] BUG: module autoloader requires debug mode (trying to serialize closures breaks app)
- [ ] BUG: Disallow drop of references on folders in which they are already contained. 
- [ ] BUG: Z3950-Import in "Main" funktioniert nicht
- [ ] BUG: Boolean columns must be NOT NULL DEFAULT 0 (z.B. data_User.anonymous)
- [ ] FEAT: Tests: Make Travis ~~great~~ work again
- [ ] FEAT: Re-implement Export
- [ ] FEAT: Clear up the conceptual mess concerning "schema": datasource schema/reference schema
- [ ] FEAT: Allow migrations via permission, not prod/dev-mode
- [ ] FEAT: Transform compile.json into compile.js (to allow to update env var "app.version" etc.)

### Priority: normal
- [ ] FEAT: respect folder `position` value
- [ ] FEAT: Reimplement Search Help
- [ ] FEAT: Add missing translations
- [ ] FEAT: Make UserErrorException a JSONRPC error which is caught on the client, instead of a Dialog event. 
- [ ] FEAT: Re-implement "New User" in Access Control Tool and System dialogs
- [ ] FEAT: Add "Re-install modules" button in Systems menu
- [ ] FEAT: Remove migrations table for deleted datasources
- [ ] FEAT: Implement setup Wizard
- [ ] FEAT: Rewrite Yii2 configuration using M1/Var
- [ ] FEAT: "Orphaned" folder should contain references that do not have a parent

### Priority: low
- [ ] FEAT: Add "serverOnly" column to data_Config (true/false/null) and remove from config data sent to client
- [ ] FEAT: Backend: Streamline API to get Datasource & typed model
- [ ] FEAT: Frontend: Rename widget ids, replace with native qooxdoo ids, see https://github.com/qooxdoo/qooxdoo/issues/9422
- [ ] FEAT: Change app state separator and assignment chars
- [ ] FEAT: Re-implement reference editor title label
- [ ] FEAT: Re-implement Docker image for testing
- [ ] FEAT: Frontend: Rename item view "metadata" page
- [ ] FEAT: Implement field selection for editor as checkboxes
- [ ] FEAT: Reimplememt help window
- [ ] FEAT: validate email configuration 
- [ ] FEAT: Change virtual folder icon

### Code cleanup
- [ ] Move config data from migration to config/prefs
- [ ] Revert return value of ConsoleAppHelper to simple string
- [ ] Backend: Model validation: accept booleans for MySql SmallInt columns
- [ ] Re-implement Table Actions Interface
- [ ] Add correct @return information to the JSONRPC methods/actions
- [ ] Convert static icon resouce paths into aliases that are resolved in bibliograph.theme.Icon
- [ ] Move config/test.php to tests/config.php 
- [ ] Replace calls to Yii::$app->eventQueue->add() with calls to message API
- [ ] Datasource-specific migration namespace should be in the Datasource Schema
- [ ] Move static methods out of \app\models\Datasource into DatasourceManager component
- [ ] Rename "Datasource" to "Repository" (see https://dev.to/remojansen/implementing-the-onion-architecture-in-nodejs-with-typescript-and-inversifyjs-10ad)
- [ ] Replace message names with constants
- [ ] Tests: Fix bootstrap loading issue
- [ ] \app\controllers\AppController::require* methods should throw a specialized Exception (i.e. MethodNotAllowedException) instead of \JsonRpc2\Exception
- [ ] Add @jsonrpc controller-id/action-id tag in controller actions
- [ ] Replace ::findOne(['namedid'=>'foo']) with ::findByNamedId('foo')
- [ ] Rename Yii::$app->utils to Yii::$app->state
- [ ] Use UserErrorException consistently and replace \Exception
- [ ] Have actions return an informative message when they don't return a result
- [ ] Fix 'property is unused' inspection alert
- [ ] Add missing id, created, modified to model rules()
- [ ] Remove qx prefix in generated code (s/qx([A-Z])/\L$1/)
- [ ] Convert config to YAML: https://packagist.org/packages/sergeymakinen/yii2-config
- [ ] Change URL params separators

## v3.0.0.RC.X (only bug fixes)

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
- [ ] Implement drag&drop folder positioning

### Priority: low
- [ ] Enable print item view: bibliograph.ui.main.ItemView#print()
- [ ] Implement config.d - style configuration