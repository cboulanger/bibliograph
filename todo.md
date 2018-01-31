# TODO

## v3.0.0

### Major tasks
- [ ] Make Travis ~~great~~ work again
- [ ] Add event transport on the level of the reponse, not the service
- [ ] Repair event dispatch, now we have  Yii::$app->eventQueue->add( new Event()) and $this->dispatch/broadcast
(http://www.yiiframework.com/doc-2.0/guide-runtime-responses.html#sending-response)
- [ ] Re-implement cql search
- [ ] Re-implement Remove/Move/Copy references
- [ ] Re-implement Add/Remove/Move/Copy folders
- [ ] Re-implement ACL Tool
- [ ] Re-implement Import
- [ ] Re-implement Export
- [ ] Add datasource access control when getting the datasource, not in the service methods
- [ ] Re-implement translation (http://www.yiiframework.com/doc-2.0/guide-tutorial-i18n.html)
- [ ] Convert static icon resouce paths into aliases that are resolved in bibliograph.theme.Icon
- [ ] Rename widget ids, replace with native qooxdoo ids, see https://github.com/qooxdoo/qooxdoo/issues/9422

### Minor tasks
- [ ] Streamline API to get Datasource & typed model
- [ ] Create a JsonRpcServiceException and replace \Exception
- [ ] Rename item view "metadata" page

## v3.X
- [ ] Re-enable item view / formatted item
- [ ] Re-enable item view / record info
- [ ] Re-enable item view / duplicates search
- [ ] Re-enable system menu commands
- [ ] Reimplement HTML editor integration for notes
- [ ] Enable print item view: bibliograph.ui.main.ItemView#print()
