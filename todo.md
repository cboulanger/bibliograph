# TODO

- [ ] Add event transport on the level of the reponse, not the service
- [ ] Repair event dispatch, now we have  Yii::$app->eventQueue->add( new Event()) and $this->dispatch/broadcast
(http://www.yiiframework.com/doc-2.0/guide-runtime-responses.html#sending-response)
- [ ] Add datasource access control when getting the datasource, not in the service methods
- [ ] Create a JsonRpcServiceException and replace \Exception
- [ ] Re-implement translation (http://www.yiiframework.com/doc-2.0/guide-tutorial-i18n.html)
- [ ] Convert static icon resouce paths into aliases that are resolved in bibliograph.theme.Icon
- [ ] Streamline API to get Datasource & typed model