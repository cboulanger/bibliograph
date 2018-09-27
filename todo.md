# TODO

## v3.0.0-beta

### Priority: urgent

### Priority: high
- [x] FEAT: User::getAllPermissions returns always the highest available set of permissions, regardless of the permissions of the group the current datasource belongs to. Requires rewriting of  `$this->requirePermission` to pass the datasource / datasource name
- [ ] BUG: Test if translations work in searches (it doesn't in unit test)
- [ ] FEAT: Reimplement account management via email

### Priority: normal
- [ ] BUG: Reimplement User::checkFormPassword
- [ ] FEAT: Cache export formats for HTML view, make configurable 
- [ ] FEAT: Reimplement Search Help 
- [ ] FEAT: Reimplement "New User" in Access Control Tool and System dialogs
- [ ] FEAT: "Orphaned" folder should contain references that do not have a parent
- [ ] FEAT: Reimplement Search Help window
- [ ] FEAT: Z39.50 Plugin: Preference option to configure timeout 
- [ ] FEAT: Reimplement "In which folders..."
- [ ] FEAT: add GUI for 'app.access.userdatabase.defaultrole' config
- [ ] FEAT: Change app state separator and assignment chars, i.e. to ; and = 

### Priority: low
- [ ] BUG: Remove non-printable chars from Z39.50 import, see also https://github.com/cboulanger/bibliograph/issues/189
- [ ] FEAT: Re-implement reference editor title label
- [ ] FEAT: Frontend: Rename item view "metadata" page
- [ ] FEAT: Preferences: Implement field selection for editor as checkboxes
- [ ] FEAT: Reimplememt help window
- [ ] FEAT: Validate email configuration 
- [ ] FEAT: Change virtual folder icon
- [ ] FEAT: Rewrite Yii2 configuration using M1/Var, maybe convert config to YAML: https://packagist.org/packages/sergeymakinen/yii2-config ?
- [ ] FEAT: Add "Re-install modules" button in Systems menu
- [ ] FEAT: Remove migrations table for deleted datasources
- [ ] FEAT: Add "Active" Checkbox to user editor
- [ ] FEAT: Alert errors during import ("x references skipped...")
- [ ] FEAT: Add a silent abort error that can be used to terminate a request without an error message
- [ ] FEAT: Reimplement support for the admindb, userdb and tmpdb settings in app.conf.toml -> use DSNs instead of single settings or TOML nested arrays.
- [ ] FEAT: Add some initial references

### Code cleanup / refactoring
- [ ] use DatasourceTrait::datasource() consistently instead of Dataource::getInstanceFor(), since it provides access control!
- [ ] run npm audit fix on all npm dependencies, change where possible, or change in global package.json
- [ ] Move dispatchClientMessage/broadcast to Yii::$app->message component
- [ ] Move scripts in "bin" to "build/script" and test scripts to "test/script"; adapt npm run commands
- [ ] Make UserErrorException a JSONRPC error which is caught on the client, instead of a Dialog event.
- [ ] Use Session Cache instead of File Cache for Storing file path in importer
- [ ] Transform compile.json into compile.js (to allow to update env var "app.version" etc.)
- [ ] Remove UTF-8 hack from EventResponse
- [ ] Fix compiler warnings
- [ ] Exclude .todo and test files from being copied into the distribution package
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
- [ ] Model validation: accept booleans for MySql SmallInt columns
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
- [ ] Move method implementations from AccessController into AccessManager leaving only the action stubs 
- [ ] Folder: remove "searchfolder" column, "type" column should contain all information
- [ ] qcl.application.MWidgetId: put registry in mixin, not in application, and app.getWidgetById -> this.getWidgetById
- [ ] Update D&D when drag type and data are available in dragover event handler
- [ ] Move general traits into lib/traits
- [ ] Module::$version conflicts with Module::getVersion -> overrride Module::defaultVersion()
- [ ] Remove datasource_role link

### Compiler errors

qcl.access.MPermissions: [275,51] Unresolved use of symbol permission.update
qcl.access.MPermissions: [285,14] Unresolved use of symbol permission.addCondition
qcl.access.MPermissions: [286,73] Unresolved use of symbol permission.getNamedId
qcl.ui.dialog.ServerProgress: [122,6] Unresolved use of symbol params.id
qcl.ui.dialog.ServerProgress: [123,6] Unresolved use of symbol params.auth_token
bibliograph.ui.window.AccessControlTool: [128,4] Unresolved use of symbol bibliograph._actRpcSendProxy
biblograph.ui.main.MultipleTreeView: [461,8] Unresolved use of symbol model.label
bibliograph.ui.main.MultipleTreeView: [467,38] Unresolved use of symbol model.data.id
bibliograph.ui.main.TableView: [506,9] Unresolved use of symbol node.label
bibliograph.ui.main.TableView: [527,93] Unresolved use of symbol node.label
bibliograph.ui.main.TableView: [509,36] Unresolved use of symbol node.data.id
bibliograph.ui.main.TableView: [529,36] Unresolved use of symbol node.data.id
qcl.ui.MChildWidget: [44,36] Unresolved use of symbol id.charAt


### Testing, CI and distribution
- [ ] Make Travis ~~great~~ work again
- [ ] FEAT: Re-implement Docker image for testing
- [ ] Use eslint on Travis: see https://github.com/ITISFoundation/qx-iconfont-material/blob/master/package.json
- [ ] Add dockerized setup, see https://github.com/ITISFoundation/qx-iconfont-material
- [ ] Tests: Fix bootstrap loading issue
- [ ] Move config/test.php to tests/config.php 
- [ ] Replace compile.json by compile.js to dynamically include plugin code
- [ ] Check log email target to be able to remotely monitor fatal errors

## v3.0.0.RC.X (only bug fixes)

## v3.0.0

## v3.1

### Priority: high
- [ ] FEAT: Implement setup Wizard
- [ ] FEAT: Allow migrations via permission, not prod/dev-mode
- [ ] FEAT: Re-enable item view / formatted item
- [ ] FEAT: Re-enable item view / record info
- [ ] FEAT: Re-enable item view / duplicates search
- [ ] FEAT: Re-enable system menu commands
- [ ] FEAT: Re-implement message broadcast
    - [ ] FEAT: Config key change should be broadcasted
    - [ ] FEAT: Broadcast "reference.changeData" message (with datasource info) to update connected clients' tableView
- [ ] FEAT: Ctrl+A to select all (visible?) references.


### Priority: normal
- [ ] FEAT: Add "serverOnly" column to data_Config (true/false/null) and remove from config data sent to client
- [ ] FEAT: Disallow drop of references on folders in which they are already contained.
- [ ] FEAT: Improve keyword import from webservices
- [ ] FEAT: Update CQL operators to conform to the specs (http://www.loc.gov/standards/sru/cql/contextSets/theCqlContextSet.html)
- [ ] FEAT: Reimplement HTML editor integration for notes
- [ ] FEAT: Implement drag&drop folder positioning
- [ ] FEAT: Clean, future-proof OO-Rewrite of the Rendering the tree in SimpleDataModel format
- [ ] FEAT: integrate https://clipboardjs.com/
- [ ] FEAT: rewrite build scripts with node, using https://codewithhugo.com/how-to-make-beautiful-simple-cli-apps-with-node/
- [ ] FEAT: Frontend: Rename widget ids, replace with native qooxdoo ids, see https://github.com/qooxdoo/qooxdoo/issues/9422

### Priority: low
- [ ] FEAT: Enable print item view: bibliograph.ui.main.ItemView#print()
- [ ] FEAT: Click on "Citation key" refreshes it
