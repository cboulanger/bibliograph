# TODO

## v3.0.0-beta

### Priority: urgent

### Priority: high
- [x] BUG: Delete, Export, Copy, Move buttons enabled for Anonymous
- [x] BUG: Move/Copy Windows blank
- [x] BUG: Drag & Drop not enabled after re-login as authorized user
- [x] BUG: Empty Trash does not delete references
- [x] FEAT: Re-implement Export
- [x] BUG: module autoloader requires debug mode (trying to serialize closures breaks app)
- [x] BUG: CSV export must only export standard bibtex fields when unauthenticated!
- [x] BUG: Z39.50 Records were not deleted on logout
- [x] FEAT: Allow adding/moving/deleting top folders
- [ ] BUG: Query `author begins with "A"` -> "missing term"
- [ ] BUG: Boolean columns must be NOT NULL DEFAULT 0 (z.B. data_User.anonymous)
- [ ] BUG: Disallow drop of references on folders in which they are already contained.
- [ ] FEAT: Cache export formats for HTML view, make configurable 
- [ ] FEAT: User::getAllPermissions returns always the highest available set of permissions, regardless of the permissions of the group the current datasource belongs to. Requires rewriting of  `$this->requirePermission` to pass the datasource / datasource name

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
- [ ] FEAT: Rewrite Yii2 configuration using M1/Var, maybe convert config to YAML: https://packagist.org/packages/sergeymakinen/yii2-config ?
- [ ] FEAT: Ctrl+A to select all (visible?) references.
- [ ] FEAT: Change URL params separators to & and = 

### Code cleanup / refactoring
- [ ] Clear up the conceptual mess concerning "schema": datasource schema/reference schema -> "repository"?
- [ ] Rename "Datasource" to "Repository" (see https://dev.to/remojansen/implementing-the-onion-architecture-in-nodejs-with-typescript-and-inversifyjs-10ad)
- [ ] FactoryClass("datasource","reference") proxying ActiveRecord methods
- [ ] Replace calls to Yii::$app->eventQueue->add() with calls to message API
- [ ] Rename 'converters' module
- [ ] Move ImportController and UploadController into renamed 'converter' module
- [ ] Move static methods out of \app\models\Datasource into DatasourceManager component
- [ ] Replace message names with constants
- [ ] Rename factory functions from getXxx to createXxx
- [ ] Datasource-specific migration namespace should be in the Datasource Schema
- [ ] Move config data from migration to config/prefs
- [ ] Revert return value of ConsoleAppHelper to simple string
- [ ] Backend: Model validation: accept booleans for MySql SmallInt columns
- [ ] Re-implement Table Actions Interface
- [ ] Add correct @return information to the JSONRPC methods/actions
- [ ] Convert static icon resouce paths into aliases that are resolved in bibliograph.theme.Icon
- [ ] \app\controllers\AppController::require* methods should throw a specialized Exception (i.e. MethodNotAllowedException) instead of \JsonRpc2\Exception
- [ ] Replace ::findOne(['namedid'=>'foo']) with ::findByNamedId('foo')
- [ ] Rename Yii::$app->utils to Yii::$app->state
- [ ] Use UserErrorException consistently and replace \Exception
- [ ] Have actions return an informative message when they don't return a result
- [ ] Fix 'property is unused' inspection alert
- [ ] Add missing id, created, modified to model rules()
- [ ] Remove qx prefix in generated code (s/qx([A-Z])/\L$1/)
- [ ] Remove verbose logging in Drag & Drop

### Testing, CI and distribution
- [ ] Make Travis ~~great~~ work again
- [ ] Use eslint on Travis: see https://github.com/ITISFoundation/qx-iconfont-material/blob/master/package.json
- [ ] Add dockerized setup, see https://github.com/ITISFoundation/qx-iconfont-material
- [ ] Tests: Fix bootstrap loading issue
- [ ] Move config/test.php to tests/config.php 

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
- [ ] Clean, future-proof OO-Rewrite of the Rendering the tree in SimpleDataModel format

### Priority: low
- [ ] Enable print item view: bibliograph.ui.main.ItemView#print()
- [ ] Implement config.d - style configuration