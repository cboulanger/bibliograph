<?php
namespace app\controllers;
use SebastianBergmann\CodeCoverage\Report\PHP;
use Yii;
use app\controllers\dto\AuthResult;
use Go\ParserReflection\ReflectionFile;
use yii\helpers\Inflector;

/**
 * Creates rpc proxy stub methods
 * @package app\controllers
 */
class RpcProxyController extends \yii\console\Controller
{
  /**
   * Creates stubs
   */
  public function actionCreate(){
    $files = [];
    $target_dir = realpath( __DIR__ . "/../../client/bibliograph/source/class/rpc" );
    $source_dirs = [ __DIR__, __DIR__ . "/../modules/z3950/controllers"];
    foreach ($source_dirs as $dir){
      foreach (scandir($dir) as $file) {
        if (ends_with($file, "Controller.php")) {
          $files[] = "$dir/$file";
        }
      }
    }

    foreach ($files as $file){
      $parsedFile = new ReflectionFile($file);
      require_once $file;
      $fileNameSpaces = $parsedFile->getFileNamespaces();
      foreach ($fileNameSpaces as $namespace) {
        $classes = $namespace->getClasses();
        $found = false;
        foreach ($classes as $class) {
          echo "Found class: ", $class->getName(), PHP_EOL;
          $class_name = str_replace( "Controller", "", $class->getShortName() );
          $target_path = ("$target_dir/$class_name.js");
          $js = "qx.Class.define(\"rpc.$class_name\"," . PHP_EOL;
          $js .= "{ " . PHP_EOL;
          $js .= "  extend: qx.core.Object," . PHP_EOL;
          $js .= "  statics: {" . PHP_EOL;

          // analyze class
          foreach ($class->getMethods() as $method) {
            $methodName = $method->getName();
            if( $methodName === "actions" or ! starts_with($methodName, "action")) {
              continue;
            }
            $action = lcfirst(substr($method->getName(),strlen("action")));
            $id = Inflector::camel2id($action);
            echo "Found class method: ", $class->getName(), '::', $method->getName(), ", action $action, ID $id", PHP_EOL;
            $found = true;
            $parameters = [];
            $phpcomment = $method->getDocComment();

            // analyze php method signature
            foreach( $method->getParameters() as $parameter){
              $phpname = $parameter->getName();
              $jsname = $phpname;
              $type = $parameter->getType();
              switch( $type ){
                case 'object':
                case 'string':
                case 'array':
                  $doc     = ucfirst($type);
                  $assert  = ucfirst($type);
                  break;
                case 'integer':
                case 'int':
                  $doc     = "Number";
                  $assert  = "Number";
                  break;
                case 'boolean':
                case 'bool':
                  $doc     = "Boolean";
                  $assert  = "Boolean";
                  break;
                default:
                  $doc     = null;
                  $assert  = null;
              }
              $param= [
                'name'       => $jsname,
                'allowsNull' => $parameter->allowsNull(),
                'doc'        => $doc,
                'assert'     => $assert
              ];
              $parameters[]=$param;
            }

            // build jsdoc
            $js .= PHP_EOL . "    /**" . PHP_EOL .
              implode( PHP_EOL, array_map( function($param){
                return "     * @param " . $param['name'] .
                  ( $param['doc'] ? ' {' . $param['doc'] . '}' : "");
              }, $parameters)) . PHP_EOL;
            $js .= "     * @return {Promise}" . PHP_EOL;
            $js .= "     */" . PHP_EOL;

            // build javascript method
            $js .= "    $action : function(" .
              implode(", ",
              array_map(function($param){
                return $param['name'] .
                  ( $param['allowsNull'] ? "=null":"");
              }, $parameters) ). "){" . PHP_EOL;
            $service = Inflector::camel2id($class_name);
            $args = implode(", ", array_map(function($param){
              return $param['name'];
            },$parameters));
            $js .= implode(PHP_EOL, array_map(function($param){
              return $param['assert'] ?
                "      qx.core.Assert.assert" . $param['assert'] . "(" . $param['name'] . ");" : "";
            },$parameters)) . PHP_EOL;
            $js .= "      return this.getApplication().getRpcClient(\"$service\").send(\"$id\", [$args]);". PHP_EOL;
            $js .= "    },". PHP_EOL;
          }
          if( $found ){
            $js .= "    ___eof : null" . PHP_EOL . "  }" . PHP_EOL . "});";
            file_put_contents($target_path,$js);
          }
        }
      }
    }
  }
}