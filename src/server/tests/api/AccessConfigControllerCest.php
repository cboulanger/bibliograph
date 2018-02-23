<?php

class AccessConfigControllerCest
{

  public function tryToGetTypeData(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
    $I->sendJsonRpcRequest('access-config', 'types');
    $expected = '[{"icon":"icon/16/apps/preferences-users.png","label":"Users","value":"user"},{"icon":"icon/16/apps/internet-feed-reader.png","label":"Roles","value":"role"},{"icon":"icon/16/actions/address-book-new.png","label":"Groups","value":"group"},{"icon":"icon/16/apps/preferences-security.png","label":"Permissions","value":"permission"},{"icon":"icon/16/apps/internet-transfer.png","label":"Datasources","value":"datasource"}]';
    $I->compareJsonRpcResultWith(json_decode($expected));
  }

  public function tryToGetElementData(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->sendJsonRpcRequest('access-config', 'elements', ['user']);
    $expected = '[{"icon":"icon/16/apps/preferences-users.png","label":"Administrator","params":"user,admin","type":"user","value":"admin"},{"icon":"icon/16/apps/preferences-users.png","label":"Manager","params":"user,manager","type":"user","value":"manager"},{"icon":"icon/16/apps/preferences-users.png","label":"User","params":"user,user","type":"user","value":"user"}]';
    $I->compareJsonRpcResultWith(json_decode($expected));
  }

  public function tryToGetTreeData(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->sendJsonRpcRequest('access-config', 'tree', ['user', 'admin']);
    $I->dontSeeJsonRpcError();
    //$expected = '{"icon":"icon/16/apps/utilities-network-manager.png","label":"Relations","action":null,"value":null,"type":null,"children":[{"icon":"icon/16/apps/internet-feed-reader.png","label":"Roles","type":"role","action":null,"value":null,"children":[{"icon":"icon/16/actions/address-book-new.png","label":"In all groups","type":"group","action":"link","value":"user=admin","children":[{"icon":"icon/16/apps/internet-feed-reader.png","label":"Administrator role","type":"role","action":"unlink","value":"role=admin","children":[]},{"icon":"icon/16/apps/internet-feed-reader.png","label":"Manager role","type":"role","action":"unlink","value":"role=manager","children":[]},{"icon":"icon/16/apps/internet-feed-reader.png","label":"Normal user","type":"role","action":"unlink","value":"role=user","children":[]}]}]},{"icon":"icon/16/actions/address-book-new.png","label":"Groups","type":"group","action":"link","value":"user=admin","children":[]},{"icon":"icon/16/apps/internet-transfer.png","label":"Datasources","type":"datasource","action":"link","value":"user=admin","children":[]}]}';
    //$I->compareJsonRpcResultWith(json_decode($expected));
  }

  public function tryToAddUsers(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->amGoingTo("create users user2 and user3");
    $I->sendJsonRpcRequest('access-config', 'add', ['user', 'user2', false]);
    $I->sendJsonRpcRequest('access-config', 'elements', ['user']);
    $expected = [
      "icon" => "icon/16/apps/preferences-users.png",
      "label" => "user2",
      "params" => "user,user2",
      "type" => "user",
      "value" => "user2"
    ];
    $I->compareJsonRpcResultWith($expected, 3);
    $I->sendJsonRpcRequest('access-config', 'add', ['user', 'user3', false]);
    $I->sendJsonRpcRequest('access-config', 'elements', ['user']);
    $expected = [
      "icon" => "icon/16/apps/preferences-users.png",
      "label" => "user3",
      "params" => "user,user3",
      "type" => "user",
      "value" => "user3"
    ];
    $I->compareJsonRpcResultWith($expected, 4);
  }

  public function tryToAddExistingUser(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->amGoingTo("create a user2, which already exists, and expect an error message");
    $I->sendJsonRpcRequest('access-config', 'add', ['user', 'user2', false]);
    $expected = "A user named 'user2' already exists. Please pick another name.";;
    $I->compareJsonRpcResultWith($expected, "events.0.data.properties.message");
  }


  public function tryToAddDatasources(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->amGoingTo("create datasources datasource3 and datasource4");
    $I->sendJsonRpcRequest('access-config', 'add', ['datasource', 'datasource3', false]);
    $I->sendJsonRpcRequest('access-config', 'elements', ['datasource']);
    $I->seeResponseJsonMatchesJsonPath("$.result[?(@.value=datasource3)]");
    $I->sendJsonRpcRequest('access-config', 'add', ['datasource', 'datasource4', false]);
    $I->sendJsonRpcRequest('access-config', 'elements', ['datasource']);
    $I->seeResponseJsonMatchesJsonPath("$.result[?(@.value=datasource4)]");
  }

  public function tryToAddGroups(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->amGoingTo("create groups group1 and group2");
    $I->sendJsonRpcRequest('access-config', 'add', ['group', 'group1', false]);
    $I->sendJsonRpcRequest('access-config', 'elements', ['group']);
    $expected = [
      "icon" => "icon/16/actions/address-book-new.png",
      "label" => "group1",
      "params" => "group,group1",
      "type" => "group",
      "value" => "group1"
    ];
    $I->compareJsonRpcResultWith($expected, 0);
    $I->sendJsonRpcRequest('access-config', 'add', ['group', 'group2', false]);
    $I->sendJsonRpcRequest('access-config', 'elements', ['group']);
    $expected = [
      "icon" => "icon/16/actions/address-book-new.png",
      "label" => "group2",
      "params" => "group,group2",
      "type" => "group",
      "value" => "group2"
    ];
    $I->compareJsonRpcResultWith($expected, 1);
  }

  public function tryToCreateUserForm(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->sendJsonRpcRequest('access-config', 'edit', ['user', 'user2']);
    $I->dontSeeJsonRpcError();
    $I->compareJsonRpcResultWith("form", "events.0.data.type");
  }

  public function tryToDeleteModels(ApiTester $I, \Codeception\Scenario $scenario)
  {
    foreach (['user', 'role', 'group', 'permission' ] as $type) {
      $namedId = $type . "Dummy";
      $I->amGoingTo("create a $type '$namedId'");
      $I->sendJsonRpcRequest('access-config', 'add', [$type, $namedId, false]);
      $I->dontSeeJsonRpcError();
      $I->expect("to get a form dialog to edit $type data");
      $I->sendJsonRpcRequest('access-config', 'edit', [$type, $namedId]);
      $I->dontSeeJsonRpcError();
      $I->compareJsonRpcResultWith("form", "events.0.data.type");
      $I->amGoingTo("delete this $type.");
      $I->sendJsonRpcRequest('access-config', 'delete', [$type, $namedId]);
      $I->dontSeeJsonRpcError();
      $I->expect("the $type not to exist any more.");
      $I->sendJsonRpcRequest('access-config', 'edit', [$type, $namedId]);
      $I->dontSeeJsonRpcError();
      //result":{"type":"ServiceResult","events":[{"name":"dialog","data":{"type":"alert","properties":{"message":,
      $I->compareJsonRpcResultWith(
        "An object of type $type and id $namedId does not exist.",
        "events.0.data.properties.message"
      );
    }
  }

  public function tryToDeleteDatasource(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->amGoingTo("create a datasource 'datasourceDummy'");
    $I->sendJsonRpcRequest('access-config', 'add', ['datasource', 'datasourceDummy', false]);
    $I->amGoingTo("delete this datasource.");
    $I->sendJsonRpcRequest('access-config', 'delete', ['datasource', 'datasourceDummy']);
    $I->expect("to get a confirmation dialog.");
    //result":{"type":"ServiceResult","events":[{"name":"dialog","data":{"type":"alert","properties":{"message":,
    $I->compareJsonRpcResultWith("confirm", "events.0.data.type");
  }

  public function tryToCreateValidGroupForm(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->amGoingTo("check the form dialog to edit group data");
    $I->sendJsonRpcRequest('access-config', 'edit', ['group','group1']);
    $I->dontSeeJsonRpcError();
    $I->expectTo("see a select box with role data");
    $expected = json_decode( '{"type":"selectbox","label":"Default role for new users","options":[{"label":"No role","value":""},{"label":"Administrator role","value":"admin"},{"label":"Anonymous user","value":"anonymous"},{"label":"Manager role","value":"manager"},{"label":"Normal user","value":"user"}],"width":300,"value":null}',true);
    $I->compareJsonRpcResultWith( $expected, "events.0.data.properties.formData.defaultRole");
  }

  public function tryToSaveForm(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->amGoingTo("submit form data");
    $data = json_decode('{
      "name":"Group 1",
      "description":"This is the first group",
      "defaultRole":"user",
      "active":1
    }', true);
    $I->sendJsonRpcRequest('access-config', 'save', [$data, "group", "group1"]);
    $I->dontSeeJsonRpcError();
    $I->expectTo("see the saved data");
    $I->sendJsonRpcRequest('access-config', 'data', [ 'group', 'group1' ]);
    $expected = [
      'id' => 1,
      'namedId' => 'group1',
      'name' => 'Group 1',
      'description' => 'This is the first group',
      'ldap' => 0,
      'defaultRole' => "user",
      'active' => 1,
      'protected' => 0
    ];
    $I->compareJsonRpcResultWith( $expected );
  }

  public function tryToAddUserToGroup(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->amGoingTo("Add user2 to group1");
    $I->sendJsonRpcRequest(
      "access-config",'link',
      ["group=group1","user","user2"]
    );
    $I->expectTo("see them linked now.");
    $I->sendJsonRpcRequest('access-config', 'tree', ['user', 'user2']);
    $path = "$.result.children[?(@.type=group)].children[?(@.label='Group 1')]";
    $I->seeResponseJsonMatchesJsonPath($path);
  }

  public function tryToAssignGlobalUserRole(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->sendJsonRpcRequest(
      "access-config",'link',
      ["role=admin","user","user2"]
    );
    $I->sendJsonRpcRequest('access-config', 'tree', ['user', 'user2']);
    $path = "$.result.children[?(@.type=role)].children[?(@.label='In all groups')].children[?(@.value='role=admin')]";
    $I->seeResponseJsonMatchesJsonPath($path);
  }

  public function tryToAssignGroupRole(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->amGoingTo("assign user3 the admin role in group2.");
    $I->sendJsonRpcRequest(
      "access-config",'link',
      ["group=group2","user","user3"]
    );
    $I->sendJsonRpcRequest(
      "access-config",'link',
      ["group=group2,role=admin","user","user3"]
    );
    $I->sendJsonRpcRequest('access-config', 'tree', ['user', 'user3']);
    $path = "$.result.children[?(@.type=role)].children[?(@.label='In group2')].children[?(@.value='group=group2,role=admin')]";
    $I->seeResponseJsonMatchesJsonPath($path);
  }

}
