<?php

class AccessConfigControllerCest
{

  public function tryTestTypeData(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
    $I->sendJsonRpcRequest('access-config','types');
    $expected = '[{"icon":"icon/16/apps/preferences-users.png","label":"Users","value":"user"},{"icon":"icon/16/apps/internet-feed-reader.png","label":"Roles","value":"role"},{"icon":"icon/16/actions/address-book-new.png","label":"Groups","value":"group"},{"icon":"icon/16/apps/preferences-security.png","label":"Permissions","value":"permission"},{"icon":"icon/16/apps/internet-transfer.png","label":"Datasources","value":"datasource"}]';
    $I->compareJsonRpcResultWith(json_decode($expected));
  }

  public function tryTestElementData(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->sendJsonRpcRequest('access-config','elements',['user']);
    $expected = '[{"icon":"icon/16/apps/preferences-users.png","label":"Administrator","params":"user,admin","type":"user","value":"admin"},{"icon":"icon/16/apps/preferences-users.png","label":"Manager","params":"user,manager","type":"user","value":"manager"},{"icon":"icon/16/apps/preferences-users.png","label":"User","params":"user,user","type":"user","value":"user"}]';
    $I->compareJsonRpcResultWith(json_decode($expected));
  }

  public function tryTestTreeData(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->sendJsonRpcRequest('access-config','tree',['user','admin']);
    $expected = '{"icon":"icon/16/apps/utilities-network-manager.png","label":"Relations","action":null,"value":null,"type":null,"children":[{"icon":"icon/16/apps/internet-feed-reader.png","label":"Roles","type":"role","action":null,"value":null,"children":[{"icon":"icon/16/actions/address-book-new.png","label":"In all groups","type":"group","action":"link","value":"user=admin","children":[{"icon":"icon/16/apps/internet-feed-reader.png","label":"Administrator role","type":"role","action":"unlink","value":"role=admin","children":[]},{"icon":"icon/16/apps/internet-feed-reader.png","label":"Manager role","type":"role","action":"unlink","value":"role=manager","children":[]},{"icon":"icon/16/apps/internet-feed-reader.png","label":"Normal user","type":"role","action":"unlink","value":"role=user","children":[]}]}]},{"icon":"icon/16/actions/address-book-new.png","label":"Groups","type":"group","action":"link","value":"user=admin","children":[]},{"icon":"icon/16/apps/internet-transfer.png","label":"Datasources","type":"datasource","action":"link","value":"user=admin","children":[]}]}';
    $I->compareJsonRpcResultWith(json_decode($expected));
  }


  
}
