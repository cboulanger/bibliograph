<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2015 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */



/**
 * Model to do XSLT - Transformations in PHP4 and PHP5
 **/
class qcl_data_xml_Transformer
  extends qcl_core_Object
{
    var $error;


  /**
   * transforms xml data with xsl stylesheet
   * @param mixed $xml string or filename of xml file to transform
   * @param mixed $xsl string or filename of xslt file to transform xml with
   * @param array $params an associated array to pass to the xsl as top-level parameters
   * @throws LogicException
   * @return string transformed xml
   */
  function transform ($xml,$xsl,$params=null)
  {
		if ( PHP_VERSION < 5 )
		{
			if ( function_exists( "domxml_open_file" ) )
			{
				return $this->_useDomXml($xml,$xsl,$params=null,$debugfile=null);
			}
  		else
  		{
  			throw new LogicException("dom_xml extension is not installed.");
  		}
		}
		else
		{
			return $this->_useLibxslt($xml,$xsl,$params=null,$debugfile=null);
		}
  }

	/**
	 * Transforms xml data with xsl stylesheet using the php domxml extension (XSLT 1.0, PHP4 only)
	 * @param mixed 	$xml 		string or filename of xml file to transform
	 * @param mixed 	$xsl 		string or filename of xslt file to transform xml with
	 * @param array 	$params 	an associated array to pass to the xsl as top-level parameters
	 * @param string 	$debugfile 	file to write debug information to
	 * @return string transformed xml
	 */
  function _useDomXml($xml,$xsl,$params=null,$debugfile=null)
  {
		$domXmlObj 	= qcl_is_file($xml) ?
			domxml_open_file($xml):
			domxml_open_mem ($xml);
		if ( ! is_object($domXmlObj) )
		{
			file_put_contents($debugfile,$xml);
			$this->error = ("Invalid xml data");
			return false;
		}

		$domXsltObj = qcl_is_file($xsl) ?
			domxml_xslt_stylesheet_file($xsl) :
			domxml_xslt_stylesheet_doc(domxml_open_mem ($xsl));

		if ( ! is_object($domXmlObj) )
		{
			file_put_contents($debugfile,$xsl);
			$this->error = ("Invalid xsl data");
			return false;
		}

		$domTranObj = $domXsltObj->process($domXmlObj,$params,false,$debugfile);

		/*
		 * process
		 */
		if ( is_object ($domTranObj) )
		{
			return $domXsltObj->result_dump_mem($domTranObj);
		}
		else
		{
			$message = "Transformation failed";
			$this->error = ($message);
			return false;
		}
  }

  /**
	 * Transforms xml data with xsl stylesheet using the php libxslt extension (XSLT 1.0, PHP5 only)
	 * this requires the presence of the JavaBridge extension
	 * @param mixed 	$xml 		string or filename of xml file to transform
	 * @param mixed 	$xsl 		string or filename of xslt file to transform xml with
	 * @param array 	$params 	an associated array to pass to the xsl as top-level parameters
	 * @return string transformed xml
	 */
  function _useLibxslt($xml,$xsl,$params=null)
  {
		$doc = new DOMDocument();

		/*
		 * xsl
		 */
		if ( qcl_is_file($xsl) )
    {
		  $doc->load($xsl);
    }
		else
		{
		  $doc->loadXML($xsl);
		}
		$xsl = new XSLTProcessor();
		$xsl->importStyleSheet($doc);

		/*
		 * xml
		 */
    if ( qcl_is_file($xml) )
    {
      $doc->load($xml);
    }
    else
    {
      $doc->loadXML($xml);
    }
    if ( is_array($params) )
    {
      foreach($params as $key => $value )
      {
        $xsl->setParameter('', $key, $value);
      }
    }
		return  $xsl->transformToXML($doc);

  }

}