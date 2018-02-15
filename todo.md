# TODO

## v3.0.0

### Major tasks
- [ ] Tests: Make Travis ~~great~~ work again
- [x] Add event transport on the level of the reponse, not the service
- [x] Repair event dispatch
- [x] Re-implement setup
- [x] Re-implement LDAP support
- [ ] Re-implement cql search
- [ ] Re-implement Remove/Move/Copy references
- [ ] Re-implement Add/Remove/Move/Copy folders
- [ ] Re-implement ACL Tool
- [ ] Re-implement Import
- [ ] Re-implement Export
- [ ] Add datasource access control when getting the datasource, not in the service methods
- [ ] Re-implement translation (http://www.yiiframework.com/doc-2.0/guide-tutorial-i18n.html)
- [ ] Frontend: Convert static icon resouce paths into aliases that are resolved in bibliograph.theme.Icon
- [ ] Frontend: Rename widget ids, replace with native qooxdoo ids, see https://github.com/qooxdoo/qooxdoo/issues/9422

### Minor tasks
- [ ] Backend: Streamline API to get Datasource & typed model
- [ ] Backend: Create a JsonRpcServiceException and replace \Exception
- [ ] Frontend: Rename item view "metadata" page
- [ ] Tests: Fix bootstrap loading issue
- [ ] Backend: Model validation: accept booleans for MySql SmallInt columns


## v3.1

### Major tasks
- [ ] Re-enable item view / formatted item
- [ ] Re-enable item view / record info
- [ ] Re-enable item view / duplicates search
- [ ] Re-enable system menu commands
- [ ] Re-implement message broadcast
- [ ] Reimplement HTML editor integration for notes

### Minor tasks
- [ ] Enable print item view: bibliograph.ui.main.ItemView#print()