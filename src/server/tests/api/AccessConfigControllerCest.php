<?php

class AccessConfigControllerCest
{

  public function tryToGetTypeData(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
    $I->sendJsonRpcRequest('access-config','types');
    $expected = '[{"icon":"icon/16/apps/preferences-users.png","label":"Users","value":"user"},{"icon":"icon/16/apps/internet-feed-reader.png","label":"Roles","value":"role"},{"icon":"icon/16/actions/address-book-new.png","label":"Groups","value":"group"},{"icon":"icon/16/apps/preferences-security.png","label":"Permissions","value":"permission"},{"icon":"icon/16/apps/internet-transfer.png","label":"Datasources","value":"datasource"}]';
    $I->compareJsonRpcResultWith(json_decode($expected));
  }

  public function tryToGetElementData(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->sendJsonRpcRequest('access-config','elements',['user']);
    $expected = '[{"icon":"icon/16/apps/preferences-users.png","label":"Administrator","params":"user,admin","type":"user","value":"admin"},{"icon":"icon/16/apps/preferences-users.png","label":"Manager","params":"user,manager","type":"user","value":"manager"},{"icon":"icon/16/apps/preferences-users.png","label":"User","params":"user,user","type":"user","value":"user"}]';
    $I->compareJsonRpcResultWith(json_decode($expected));
  }

  public function tryToGetTreeData(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->sendJsonRpcRequest('access-config','tree',['user','admin']);
    $expected = '{"icon":"icon/16/apps/utilities-network-manager.png","label":"Relations","action":null,"value":null,"type":null,"children":[{"icon":"icon/16/apps/internet-feed-reader.png","label":"Roles","type":"role","action":null,"value":null,"children":[{"icon":"icon/16/actions/address-book-new.png","label":"In all groups","type":"group","action":"link","value":"user=admin","children":[{"icon":"icon/16/apps/internet-feed-reader.png","label":"Administrator role","type":"role","action":"unlink","value":"role=admin","children":[]},{"icon":"icon/16/apps/internet-feed-reader.png","label":"Manager role","type":"role","action":"unlink","value":"role=manager","children":[]},{"icon":"icon/16/apps/internet-feed-reader.png","label":"Normal user","type":"role","action":"unlink","value":"role=user","children":[]}]}]},{"icon":"icon/16/actions/address-book-new.png","label":"Groups","type":"group","action":"link","value":"user=admin","children":[]},{"icon":"icon/16/apps/internet-transfer.png","label":"Datasources","type":"datasource","action":"link","value":"user=admin","children":[]}]}';
    $I->compareJsonRpcResultWith(json_decode($expected));
  }

  public function tryToAddUsers(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->comment("create users user2 and user3");
    $I->sendJsonRpcRequest('access-config','add',['user','user2',false]);
    $I->sendJsonRpcRequest('access-config','elements',['user']);
    $expected = [
      "icon"   => "icon/16/apps/preferences-users.png",
      "label"  => "user2",
      "params" => "user,user2",
      "type"   => "user",
      "value"  => "user2"
    ];
    $I->compareJsonRpcResultWith( $expected, 3);
    $I->sendJsonRpcRequest('access-config','add',['user','user3',false]);
    $I->sendJsonRpcRequest('access-config','elements',['user']);
    $expected = [
      "icon"   => "icon/16/apps/preferences-users.png",
      "label"  => "user3",
      "params" => "user,user3",
      "type"   => "user",
      "value"  => "user3"
    ];
    $I->compareJsonRpcResultWith( $expected, 4);
  }

  public function tryToAddExistingUser(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->comment("create a user2, which already exists, and expect an error message");
    $I->sendJsonRpcRequest('access-config','add',['user','user2',false]);
    $expected = "A user named 'user2' already exists. Please pick another name.";;
    $I->compareJsonRpcResultWith( $expected, "events.0.data.properties.message" );
  }


  public function tryToAddDatasources(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->comment("create datasources datasource1 and datasource2");
    $I->sendJsonRpcRequest('access-config','add',['datasource','datasource1',false]);
    $I->sendJsonRpcRequest('access-config','elements',['datasource']);
    $expected = [
      "icon"   => "icon/16/apps/internet-transfer.png",
      "label"  => "datasource1",
      "params" => "datasource,datasource1",
      "type"   => "datasource",
      "value"  => "datasource1"
    ];
    $I->compareJsonRpcResultWith( $expected, 0);
    $I->sendJsonRpcRequest('access-config','add',['datasource','datasource2',false]);
    $I->sendJsonRpcRequest('access-config','elements',['datasource']);
    $expected = [
      "icon"   => "icon/16/apps/internet-transfer.png",
      "label"  => "datasource2",
      "params" => "datasource,datasource2",
      "type"   => "datasource",
      "value"  => "datasource2"
    ];
    $I->compareJsonRpcResultWith( $expected, 1);
  }

  public function tryToAddGroups(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->comment("create groups group1 and group2");
    $I->sendJsonRpcRequest('access-config','add',['group','group1',false]);
    $I->sendJsonRpcRequest('access-config','elements',['group']);
    $expected = [
      "icon"   => "icon/16/actions/address-book-new.png",
      "label"  => "group1",
      "params" => "group,group1",
      "type"   => "group",
      "value"  => "group1"
    ];
    $I->compareJsonRpcResultWith( $expected, 0);
    $I->sendJsonRpcRequest('access-config','add',['group','group2',false]);
    $I->sendJsonRpcRequest('access-config','elements',['group']);
    $expected = [
      "icon"   => "icon/16/actions/address-book-new.png",
      "label"  => "group2",
      "params" => "group,group2",
      "type"   => "group",
      "value"  => "group2"
    ];
    $I->compareJsonRpcResultWith( $expected, 1);
  }

  public function tryToCreateUserForm(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
    $I->sendJsonRpcRequest('access-config','edit',['user','user2']);
  }
  
}
