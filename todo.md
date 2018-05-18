# TODO

## v3.0.0-beta

### Priority: urgent

### Priority: high
- [ ] FEAT: User::getAllPermissions returns always the highest available set of permissions, regardless of the permissions of the group the current datasource belongs to. Requires rewriting of  `$this->requirePermission` to pass the datasource / datasource name
 
### Priority: normal
- [ ] FEAT: Cache export formats for HTML view, make configurable 
- [ ] FEAT: Disallow drop of references on folders in which they are already contained.
- [ ] FEAT: Reimplement Search Help
- [ ] FEAT: Make UserErrorException a JSONRPC error which is caught on the client, instead of a Dialog event. 
- [ ] FEAT: Re-implement Backup
- [ ] FEAT: Reimplement "New User" in Access Control Tool and System dialogs
- [ ] FEAT: Reimplement account management via email
- [ ] FEAT: "Orphaned" folder should contain references that do not have a parent
- [ ] FEAT: Reimplement Search Help window
- [ ] FEAT: Update CQL operators to conform to the specs (http://www.loc.gov/standards/sru/cql/contextSets/theCqlContextSet.html)
- [ ] FEAT: Config key change should be broadcasted
- [ ] FEAT: Implement setup Wizard
- [ ] FEAT: Allow migrations via permission, not prod/dev-mode
- [ ] FEAT: Check log email target to report fatal errors

### Priority: low
- [ ] BUG: Remove non-printable chars from Z39.50 import, see also https://github.com/cboulanger/bibliograph/issues/189
- [ ] FEAT: Add "serverOnly" column to data_Config (true/false/null) and remove from config data sent to client
- [ ] FEAT: Backend: Streamline API to get Datasource & typed model
- [ ] FEAT: Frontend: Rename widget ids, replace with native qooxdoo ids, see https://github.com/qooxdoo/qooxdoo/issues/9422
- [ ] FEAT: Change app state separator and assignment chars
- [ ] FEAT: Re-implement reference editor title label
- [ ] FEAT: Re-implement Docker image for testing
- [ ] FEAT: Frontend: Rename item view "metadata" page
- [ ] FEAT: Preferences: Implement field selection for editor as checkboxes
- [ ] FEAT: Reimplememt help window
- [ ] FEAT: validate email configuration 
- [ ] FEAT: Change virtual folder icon
- [ ] FEAT: Rewrite Yii2 configuration using M1/Var, maybe convert config to YAML: https://packagist.org/packages/sergeymakinen/yii2-config ?
- [ ] FEAT: Ctrl+A to select all (visible?) references.
- [ ] FEAT: Change URL params separators to & and = 
- [ ] FEAT: Broadcast "reference.changeData" message (with datasource info) to update connected clients' tableView
- [ ] FEAT: Click on "Citation key" refreshes it
- [ ] FEAT: Add "Re-install modules" button in Systems menu
- [ ] FEAT: Remove migrations table for deleted datasources
- [ ] FEAT: Add "Active" Checkbox to user editor
- [ ] FEAT: Alert errors during import ("x references skipped...")

### Code cleanup / refactoring
- [ ] Use Session Cache instead of File Cache for Storing file path in importer
- [ ] Transform compile.json into compile.js (to allow to update env var "app.version" etc.)
- [ ] Remove UTF-8 hack from EventResponse
- [ ] Fix compiler warnings
- [ ] Exclude .todo files from being copied into the distribution package
- [ ] Clear up the conceptual mess concerning "schema": datasource schema/reference schema -> "repository"?
- [ ] Rename "Datasource" to "Repository" (see https://dev.to/remojansen/implementing-the-onion-architecture-in-nodejs-with-typescript-and-inversifyjs-10ad)
- [ ] FactoryClass("datasource","reference") proxying ActiveRecord methods
- [ ] Replace calls to Yii::$app->eventQueue->add() with calls to message API
- [ ] Rename 'converters' module
- [ ] Fix Boolean/smallinteger issues
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
- [ ] Remove forks from composer.json that have been updated upstream
- [ ] Move module translations into module https://www.yiiframework.com/doc/guide/2.0/en/tutorial-i18n#module-translation

### Testing, CI and distribution
- [ ] Make Travis ~~great~~ work again
- [ ] Use eslint on Travis: see https://github.com/ITISFoundation/qx-iconfont-material/blob/master/package.json
- [ ] Add dockerized setup, see https://github.com/ITISFoundation/qx-iconfont-material
- [ ] Tests: Fix bootstrap loading issue
- [ ] Move config/test.php to tests/config.php 
- [ ] Replace compile.json by compile.js to dynamically include plugin code

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