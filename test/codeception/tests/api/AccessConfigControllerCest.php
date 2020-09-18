<?php

use lib\exceptions\UserErrorException;

class AccessConfigControllerCest
{

  public function tryToGetTypeData(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
    $I->sendJsonRpcRequest('access-config', 'types');
    $I->expectDataforMethod(__METHOD__);
    $I->logout();
  }

  public function tryToGetElementData(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
    $I->sendJsonRpcRequest('access-config', 'elements', ['user']);
    $I->expectDataforMethod(__METHOD__);
    $I->logout();
  }

  public function tryToGetTreeData(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
    $I->sendJsonRpcRequest('access-config', 'tree', ['user', 'admin']);
    $I->dontSeeJsonRpcError();
    $I->expectDataforMethod(__METHOD__);
    $I->logout();
  }

  public function tryToAddUsers(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
    $I->amGoingTo("create users user3 and user4");
    $I->sendJsonRpcRequest('access-config', 'add', ['user', 'user3', false]);
    $I->sendJsonRpcRequest('access-config', 'elements', ['user']);
    $expected = [
      "icon" => "icon/16/apps/preferences-users.png",
      "label" => "user3",
      "params" => "user,user3",
      "type" => "user",
      "value" => "user3"
    ];
    $I->compareJsonRpcResultWith($expected, 3);
    $I->sendJsonRpcRequest('access-config', 'add', ['user', 'user4', false]);
    $I->sendJsonRpcRequest('access-config', 'elements', ['user']);
    $expected = [
      "icon" => "icon/16/apps/preferences-users.png",
      "label" => "user4",
      "params" => "user,user4",
      "type" => "user",
      "value" => "user4"
    ];
    $I->compareJsonRpcResultWith($expected, 4);
    $I->logout();
  }

  /**
   * @param ApiTester $I
   * @param \Codeception\Scenario $scenario
   */
  public function tryToAddExistingUser(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
    $I->amGoingTo("create a user2, which already exists, and expect an error message");
    $I->sendJsonRpcRequest('access-config', 'add', ['user', 'user2', false],true);

    $I->seeUserError();
    $I->logout();
  }

  /**
   * @param ApiTester $I
   * @param \Codeception\Scenario $scenario
   *
   */
  public function tryToAddDatasources(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
    $I->amGoingTo("create datasources datasource3 and datasource4");
    $I->sendJsonRpcRequest('access-config', 'add', ['datasource', 'datasource3', false]);
    $I->sendJsonRpcRequest('access-config', 'elements', ['datasource']);
    $I->seeRequestedResponseMatchesJsonPath("$.result[?(@.value=datasource3)]");
    $I->sendJsonRpcRequest('access-config', 'add', ['datasource', 'datasource4', false]);
    $I->sendJsonRpcRequest('access-config', 'elements', ['datasource']);
    $I->seeRequestedResponseMatchesJsonPath("$.result[?(@.value=datasource4)]");
    $I->logout();
  }

  public function tryToAddGroups(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
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
    $I->logout();
  }

  public function tryToCreateUserForm(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
    $I->sendJsonRpcRequest('access-config', 'edit', ['user', 'user2']);
    $I->seeServerEvent("dialog", new JsonPathType("$.properties.formData"));
    $I->logout();
  }

  public function tryToDeleteModels(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
    foreach (['user', 'role', 'group', 'permission' ] as $type) {
      $namedId = $type . "Dummy";
      $I->amGoingTo("create a $type '$namedId'");
      $I->sendJsonRpcRequest('access-config', 'add', [$type, $namedId, false]);
      $I->expect("to get a form dialog to edit $type data");
      $I->sendJsonRpcRequest('access-config', 'edit', [$type, $namedId]);
      $I->seeServerEvent("dialog", new JsonPathType("$.properties.formData"));
      $I->amGoingTo("delete this $type.");
      $I->sendJsonRpcRequest('access-config', 'delete', [$type, $namedId]);
      $I->expect("the $type not to exist any more.");
      $I->sendJsonRpcRequest('access-config', 'edit', [$type, $namedId], true);
      $I->seeUserError("type $type and id $namedId does not exist");
    }
    $I->logout();
  }

  public function tryToDeleteDatasource(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
    $I->amGoingTo("create a datasource 'datasourceDummy'");
    $I->sendJsonRpcRequest('access-config', 'add', ['datasource', 'datasourceDummy', false],true);
    $I->amGoingTo("delete this datasource.");
    $I->sendJsonRpcRequest('access-config', 'delete', ['datasource', 'datasourceDummy']);
    $I->expect("to get a confirmation dialog.");
    $I->seeServerEvent("dialog");
    $I->logout();
  }

  public function tryToCreateValidGroupForm(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
    $I->amGoingTo("check the form dialog to edit group data");
    $I->sendJsonRpcRequest('access-config', 'edit', ['group','group1']);
    $I->expectTo("see a select box with role data");
    $expected = $I->loadExpectedData(__METHOD__,  "api", true);
    $I->seeServerEvent("dialog", new JsonExpressionType($expected));
    $I->logout();
  }

  public function tryToSaveForm(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
    $I->amGoingTo("submit form data");
    $data = json_decode('{
      "name":"Group 1",
      "description":"This is the first group",
      "defaultRole":"user",
      "active":1
    }', true);
    $I->sendJsonRpcRequest('access-config', 'save', [$data, "group", "group1"]);
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
    $I->logout();
  }

  public function tryToAddUserToGroup(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
    $I->amGoingTo("Add user2 to group1");
    $I->sendJsonRpcRequest(
      "access-config",'link',
      ["group=group1","user","user2"]
    );
    $I->expectTo("see them linked now.");
    $I->sendJsonRpcRequest('access-config', 'tree', ['user', 'user2']);
    $path = "$.result.children[?(@.type=group)].children[?(@.label='Group 1')]";
    $I->seeRequestedResponseMatchesJsonPath($path);
    $I->logout();
  }

  public function tryToAssignGlobalUserRole(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
    $I->sendJsonRpcRequest(
      "access-config",'link',
      ["role=admin","user","user2"]
    );
    $I->sendJsonRpcRequest('access-config', 'tree', ['user', 'user2']);
    $path = "$.result.children[?(@.type=role)].children[?(@.label='In all groups')].children[?(@.value='role=admin')]";
    $I->seeRequestedResponseMatchesJsonPath($path);
    $I->logout();
  }

  public function tryToAssignGroupRole(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAsAdmin();
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
    $I->seeRequestedResponseMatchesJsonPath($path);
    $I->logout();
  }
}
