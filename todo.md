# TODO

## v3.0.0-beta 

### v3.0.0.beta1
- [x] BUG: anonymous users are not purged
- [x] BUG: Fix Group permissions
- [x] FEAT: ACT: changing element type must clear filter
- [x] BUG: Import in "Main" funktioniert nicht
- [x] FEAT: Reimplemented import for BibTex-UTF8

### Priority: urgent

### Priority: high
- [ ] BUG: Delete, Export, Copy, Move buttons enabled for Anonymous
- [ ] BUG: Drag & Drop not enabled after re-login as authorized user
- [ ] BUG: Move/Copy Windows blank
- [ ] BUG: Empty Trash does not delete references
- [ ] BUG: module autoloader requires debug mode (trying to serialize closures breaks app)
- [ ] BUG: Disallow drop of references on folders in which they are already contained. 
- [ ] BUG: Query `author begins with "A"` -> "missing term"
- [ ] BUG: Boolean columns must be NOT NULL DEFAULT 0 (z.B. data_User.anonymous)
- [ ] FEAT: User::getAllPermissions returns always the highest available set of permissions, regardless of the permissions of the group the current datasource belongs to. Requires rewriting of  `$this->requirePermission` to pass the datasource / datasource name
- [ ] FEAT: Re-implement Export
- [ ] FEAT: Allow adding top folders
- [ ] FEAT: Tests: Make Travis ~~great~~ work again

### Priority: normal
- [ ] FEAT: Transform compile.json into compile.js (to allow to update env var "app.version" etc.)
- [ ] FEAT: Reimplement account management via email
- [ ] FEAT: Check log email target to report fatal errors
- [ ] FEAT: Reimplement Search Help
- [ ] FEAT: Add missing translations, replace %s by {1}
- [ ] FEAT: Make UserErrorException a JSONRPC error which is caught on the client, instead of a Dialog event. 
- [ ] FEAT: Re-implement Backup
- [ ] FEAT: Reimplement "New User" in Access Control Tool and System dialogs
- [ ] FEAT: Add "Re-install modules" button in Systems menu
- [ ] FEAT: Remove migrations table for deleted datasources
- [ ] FEAT: Allow migrations via permission, not prod/dev-mode
- [ ] FEAT: Implement setup Wizard
- [ ] FEAT: "Orphaned" folder should contain references that do not have a parent
- [ ] FEAT: Add "Active" Checkbox to user editor
- [ ] FEAT: Reimplement Search Help window

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
- [ ] FEAT: Rewrite Yii2 configuration using M1/Var

### Code cleanup
- [ ] Clear up the conceptual mess concerning "schema": datasource schema/reference schema -> "repository"?
- [ ] FactoryClass("datasource","reference") proxying ActiveRecord methods
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
- [ ] Clean, future-proof OO-Rewrite of the Rendering the tree in SimpleDataModel format
 
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