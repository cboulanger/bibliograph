<?
/*
 *
 * Phomet: a php comet client
 *
 * Copyright (C) 2008 Morgan 'ARR!' Allen <morganrallen@gmail.com> http://morglog.alleycatracing.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *

*/
require_once "qcl/core/Object.php";

class qcl_io_remote_cometd_Client
  extends qcl_core_Object
{
	private $oCurl = '';

	private $nNextId = 0;

	public $sUrl = '';

	/**
	 * Constructor, establishes a connection to the cometd server at the
	 * provided url
	 * @param $sUrl
	 * @return unknown_type
	 */
	function __construct($sUrl)
	{

		$this->sUrl = $sUrl;

		$this->oCurl = curl_init();

		$aHeaders = array();
		$aHeaders[] = 'Connection: Keep-Alive';

		curl_setopt($this->oCurl, CURLOPT_URL, $sUrl);
		curl_setopt($this->oCurl, CURLOPT_HTTPHEADER, $aHeaders);
		curl_setopt($this->oCurl, CURLOPT_HEADER, 0);
		curl_setopt($this->oCurl, CURLOPT_POST, 1);
		curl_setopt($this->oCurl, CURLOPT_RETURNTRANSFER,1);

		$this->handShake();

	}

	function __destruct()
	{
		$this->disconnect();
	}

	/**
	 * Establishes a data connection to the cometd server
	 * @return void
	 */
	function handShake()
	{
		$msgHandshake = array();
		$msgHandshake['channel'] = '/meta/handshake';
		$msgHandshake['version'] = "1.0";
		$msgHandshake['minimumVersion'] = "0.9";
		$msgHandshake['id'] = $this->nNextId++;


		curl_setopt($this->oCurl, CURLOPT_POSTFIELDS, "message=".urlencode(str_replace('\\', '', json_encode(array($msgHandshake)))));

		$data = curl_exec($this->oCurl);

		if( curl_errno($this->oCurl) )
		{
			$this->raiseError("Error: " . curl_error( $this->oCurl) );
		}

		$oReturn = json_decode($data);
		$oReturn = $oReturn[0];

		$bSuccessful = ($oReturn->successful) ? true : false;

		if( $bSuccessful )
		{
			$this->clientId = $oReturn->clientId;

			$this->connect();
		}
	}

	/**
	 * Connects to a cometd server
	 * @return unknown_type
	 */
	public function connect()
	{
		$aMsg['channel'] = '/meta/connect';
		$aMsg['id'] = $this->nNextId++;
		$aMsg['clientId'] = $this->clientId;
		$aMsg['connectionType'] = 'long-polling';

		curl_setopt($this->oCurl, CURLOPT_POSTFIELDS, "message=".urlencode(str_replace('\\', '', json_encode(array($aMsg)))));

		$data = curl_exec($this->oCurl);
	}

	/**
	 * Disconnects from the cometd server
	 * @return void
	 */
	function disconnect()
	{
		$msgHandshake = array();
		$msgHandshake['channel'] = '/meta/disconnect';
		$msgHandshake['id'] = $this->nNextId++;
		$msgHandshake['clientId'] = $this->clientId;

		curl_setopt($this->oCurl, CURLOPT_POSTFIELDS, "message=".urlencode(str_replace('\\', '', json_encode(array($msgHandshake)))));

		curl_exec($this->oCurl);
	}

	/**
	 * Publishes to a channel
	 * @param $sChannel
	 * @param $oData
	 * @return resource
	 */
	function publish($sChannel, $oData)
	{
		if(!$sChannel || !$oData)
			return;

		$aMsg = array();

		$aMsg['channel'] = $sChannel;
		$aMsg['id'] = $this->nNextId++;
		$aMsg['data'] = $oData;
		$aMsg['clientId'] = $this->clientId;

		curl_setopt($this->oCurl, CURLOPT_POSTFIELDS, "message=".urlencode(str_replace('\\', '', json_encode(array($aMsg)))));

		return curl_exec( $this->oCurl );
	}
}
?>