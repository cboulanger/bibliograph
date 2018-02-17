<?php

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class ApiTester extends \Codeception\Actor
{
  use _generated\ApiTesterActions;
  use JsonRpcTrait;

  /**
   * Enable LDAP authentication
   */
  public function enableLdapAuthentication(){
    $this->setLdapEnabled(true);
  }

  /**
   * Disable LDAP authentication
   */
  public function disableLdapAuthentication(){
    $this->setLdapEnabled(false);
  }  

  /**
   * Dis/enable LDAP support by replacing the setting in the 
   * test.ini.php file.
   *
   * @param bool $boolValue
   */
  protected function setLdapEnabled($boolValue){
    $path_to_file = __DIR__ . "/../test.ini.php";
    $file_contents = file_get_contents($path_to_file);
    $enabled = "yes;!ldap!"; $disabled = "no;!ldap!";
    $newValue = $boolValue ? $enabled : $disabled;
    $file_contents = str_replace([$enabled,$disabled],$newValue, $file_contents);
    file_put_contents($path_to_file,$file_contents);
  }
}
