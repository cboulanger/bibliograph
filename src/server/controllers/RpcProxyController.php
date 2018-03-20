<?php

namespace app\controllers;

use Go\ParserReflection\ReflectionFile;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlockFactory;
use yii\helpers\Inflector;

/**
 * Creates rpc proxy stub methods
 * @package app\controllers
 */
class RpcProxyController extends \yii\console\Controller
{
  
  const ECMASCRIPT_RESERVED_KEYWORDS = [
    'do', 'if', 'in', 'for', 'let', 'new', 'try', 'var', 'case', 'else', 'enum', 'eval', 'null', 'this', 'true', 'void',
    'with', 'await', 'break', 'catch', 'class', 'const', 'false', 'super', 'throw', 'while', 'yield', 'delete', 
    'export', 'import', 'public', 'return', 'static', 'switch', 'typeof', 'default', 'extends', 'finally', 'package',
    'private', 'continue', 'debugger', 'function', 'arguments', 'interface', 'protected', 'implements', 'instanceof'
  ];
  
  /**
   * Creates stubs
   */
  public function actionCreate()
  {
    // find controller source code files
    $files = [];
    $target_dir = realpath(__DIR__ . "/../../client/bibliograph/source/class/rpc");
    $source_dirs = [__DIR__, __DIR__ . "/../modules/z3950/controllers"];
    foreach ($source_dirs as $dir) {
      foreach (scandir($dir) as $file) {
        if (ends_with($file, "Controller.php")) {
          $files[] = "$dir/$file";
        }
      }
    }

    // iterator over files
    foreach ($files as $file) {
      $parsedFile = new ReflectionFile($file);
      require_once $file;
      $fileNameSpaces = $parsedFile->getFileNamespaces();
      $docblockfactory = DocBlockFactory::createInstance();
      foreach ($fileNameSpaces as $namespace) {
        $classes = $namespace->getClasses();
        $found = false;

        // iterate through declared classes
        foreach ($classes as $class) {

          $class_name = str_replace("Controller", "", $class->getShortName());
          $controllerId = Inflector::camel2id($class_name);
          echo "Found class: ", $class->getName(), ", controller-ID: $controllerId", PHP_EOL;

          $doc_comment = $class->getDocComment();
          if ( $doc_comment ){
            $docblock = $docblockfactory->create($doc_comment);
            $class_comment = $docblock->getSummary() . PHP_EOL . $docblock->getDescription();
          }

          // javascript
          $js = [];
          $js[] = "/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */";
          $js[] = "";
          $js[] = "/**";
          $js[] = $this->formatDocblockContent($docblock->getSummary() . PHP_EOL.PHP_EOL . $docblock->getDescription());
          $js[] = " * @see " . $class->getName();
          $js[] = " * @file $file";
          $js[] = " */";
          $js[] = "qx.Class.define(\"rpc.$class_name\",";
          $js[] = "{ ";
          $js[] = "  type: 'static',";
          $js[] = "  statics: {";

          // analyze class
          $methods = $class->getMethods();
          foreach ($methods as $method) {
            $methodName = $method->getName();
            // we only use actions
            if ($methodName === "actions" or !starts_with($methodName, "action")) {
              continue;
            }

            $proxymethod = lcfirst(substr($method->getName(), strlen("action")));
            $actionId = Inflector::camel2id($proxymethod);
            echo " - Found class method: ", $class->getShortName(), '::', $method->getName(), "(): proxy rpc.$proxymethod(), action-ID $actionId", PHP_EOL;
            $found = true;
            $parameters = [];

            // comment
            $phpcomment = $method->getDocComment();
            if( $phpcomment ){
              $docblock = $docblockfactory->create($phpcomment);
              /** @var Param[] $param_tags */
              $param_tags = $docblock->getTagsByName('param');
            } else {
              $param_tags = [];
            }
            // analyze php method signature
            foreach ($method->getParameters() as $parameter) {
              $phpname = $parameter->getName();
              $jsname = in_array( $phpname, self::ECMASCRIPT_RESERVED_KEYWORDS) ? "\$$phpname" : $phpname;
              $type = $parameter->getType();
              /** @var Param $param_tag */
              $param_tag = array_first($param_tags,function(Param $param) use ($phpname) {
                return $param->getVariableName() == $phpname;
              });
              // use type information from docblock
              if( $param_tag ){
                $doc_type = (string) $param_tag->getType();
                if( $doc_type ){
//                  if( $doc_type and $type and $doc_type != $type  ){
//                    throw new \RuntimeException("Parameter type mismatch: Signature type is '$type', documented type is '$doc_type'.");
//                  }
                  if( !$type ) $type = $doc_type;
                }
              }
              switch ($type) {
                case 'object':
                case 'string':
                case 'array':
                  $doc = ucfirst($type);
                  $assert = ucfirst($type);
                  break;
                case 'integer':
                case 'int':
                  $doc = "Number";
                  $assert = "Number";
                  break;
                case 'boolean':
                case 'bool':
                  $doc = "Boolean";
                  $assert = "Boolean";
                  break;
                default:
                  $doc = null;
                  $assert = null;
              }
              $param = [
                'name' => $jsname,
                'allowsNull' => $parameter->allowsNull(),
                'doc' => $doc,
                'assert' => $assert,
                'description' => $param_tag instanceof Param ? $param_tag->getDescription() : null
              ];
              $parameters[] = $param;
            }

            // build jsdoc
            $js[] = "    /**";
            if( $phpcomment ){
              $description = $docblock->getSummary() . PHP_EOL.PHP_EOL . (string) $docblock->getDescription();
              $js[] = $this->formatDocblockContent( $description,"    ");
            }
            $js = array_merge($js, array_map(function ($param) {
              return $this->formatDocblockContent(
                "@param " . $param['name'] . " " .
                  ($param['doc'] ? '{' . $param['doc'] . '} ' : "") .
                  ($param['description'] ? $param['description'] : ""),
                "    "
              );
              }, $parameters)
            );
            $js[] = "     * @return {Promise}";
            $js[] = "     * @see " . $class->getShortName() . '::' . $method->getName();
            $js[] = "     */";

            // build javascript method
            $js[] = "    $proxymethod : function(" .
              implode(", ",
                array_map(function ($param) {
                  return $param['name'] .
                    ($param['allowsNull'] ? "=null" : "");
                }, $parameters)) . "){";
            $args = implode(", ", array_map(function ($param) {
              return $param['name'];
            }, $parameters));
            $js = array_merge($js, array_map(function ($param) use ($class, $method) {
              return $param['assert']
                ? "      qx.core.Assert.assert" . $param['assert'] . "(" . $param['name'] . ");"
                : "      // @todo Document type for '" . $param['name'] . "' in " . $class->getName() . '::'. $method->getName();
            }, $parameters));
            $js[] = "      return this.getApplication().getRpcClient(\"$controllerId\").send(\"$actionId\", [$args]);";
            $js[] = "    },";
            $js[] = "";
          }
          if ($found) {
            array_pop($js);
            array_pop($js);
            $js[] = "    }";
            $js[] = "  }";
            $js[] = "});";
            $target_path  = "$target_dir/$class_name.js";
            $file_content = implode(PHP_EOL, $js);
            //  write to file if content has changed
            if( file_get_contents($target_path) !== $file_content ){
              file_put_contents( $target_path, $file_content );
            }
          }
        }
      }
    }
  }

  /**
   * Re-wraps docblock content
   * @param string $content
   * @param string $indentation
   * @return string
   */
  protected function formatDocblockContent( string $content, string $indentation="", int $width=75 )
  {
    $prefix = $indentation . " * ";
    $content = str_replace(PHP_EOL.PHP_EOL, "\\n\\n",$content);
    //$lines   = explode(PHP_EOL, $content);
    //$content = wordwrap( implode(" ", $lines ), $width );
    $lines   = explode (PHP_EOL, $content );
    $content = $prefix . implode( PHP_EOL.$prefix, $lines );
    $content = str_replace( "\\n\\n", PHP_EOL.$prefix, $content );
    return $content;
  }
}