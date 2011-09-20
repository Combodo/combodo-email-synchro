<?php
// Copyright (C) 2011 Combodo SARL
//
//   This program is free software; you can redistribute it and/or modify
//   it under the terms of the GNU General Public License as published by
//   the Free Software Foundation; version 3 of the License.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of the GNU General Public License
//   along with this program; if not, write to the Free Software
//   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
include 'Net/POP3.php';			// PEAR POP3
include APPROOT.'modules/combodo-email-synchro/mimeDecode.php';	// MODIFIED PEAR mimeDecode
/**
 * The raw Email/POP3 values and methods to decode it
 */
class RawEmailMessage
{
	protected $sUIDL;
	protected $sRawHeaders;
	protected $aParsedHeaders;
	protected $sBody;

	public function __construct($sUIDL, $sRawHeaders, $sBody)
	{
		$this->sUIDL = $sUIDL;
		$this->sRawHeaders = $sRawHeaders;
		$this->sBody = $sBody;
		$this->ParseHeaders();
		$this->aWarnings = array();
	}
	/**
	 * decodes an email from its parts
	 * @return EmailMessage
	 */
	public function Decode()
	{
		$sMessageId = $this->GetMessageId();
		$sCallerEmail = $this->GetSenderEmail();
		$sSubject = $this->GetSubject();
		$sCallerName = $this->GetCallerName();
		$oMime = new Mail_mimeDecode($this->sRawHeaders."\r\n".$this->sBody);
		$oStructure = $oMime->decode(array('include_bodies' => true, 'decode_bodies' => true));

		$sEncoding = $oStructure->headers['content-type'];
		$aMatches = array();
		$sCharset = 'US-ASCII';
		if (preg_match('/charset="?([^";]*)"?;?/', $sEncoding, $aMatches))
		{
			$sCharset = $aMatches[1];
		}
		$sSubject =iconv($sCharset, 'UTF-8//IGNORE//TRANSLIT', $oStructure->headers['subject']);
		
		// Search for the text/plain body part
		$iPartIndex = 0;
		$bFound = false;
		$sTextBody = '';
		$sReferences = isset($oStructure->headers['references']) ? $oStructure->headers['references'] : '';
		if (!isset($oStructure->parts) || count($oStructure->parts) == 0)
		{
			$sTextBody = iconv($sCharset, 'UTF-8//IGNORE//TRANSLIT', $oStructure->body);
		}
		else
		{
			// Multi-parts encoding, find a text or HTML part			
			$sTextBody = $this->ScanParts($oStructure->parts, 'text', 'plain');
			if (empty($sTextBody))
			{
				// Try to find an HTML part...
				$sTextBody = $this->ScanParts($oStructure->parts, 'text', 'html');
			}
		}
		
//if (empty($sTextBody))
//{
//	echo "Unable to find a suitable body in the message:\n";	
//	echo "==============================================\n";
//	print_r($oStructure);
//	echo "\n==============================================\n\n";
//}
//else
//{
//	echo "==============================================\n";
//	echo $sTextBody;
//	echo "\n==============================================\n\n";
//}
		
//echo "Body:\n";	
//print_r($sTextBody);
//echo "\noMime:\n";
//print_r($oMime);		
		
		$sRecipient = '';
		$aReferences = '';
		$aBodyParts = '';
		$aAttachments = '';
		$sDecodeStatus = '';
		
		return new EmailMessage($this->sUIDL, $sMessageId, $sSubject, $sCallerEmail, $sCallerName, $sRecipient, $aReferences, $aBodyParts, $aAttachments, $sDecodeStatus);
	}
	/**
	 * Saves the raw message to a file
	 */
	public function SaveToFile($sFileName)
	{
		$hFile = fopen($sFileName, 'wb');
		if ($hFile)
		{
			fwrite($hFile, $this->sRawHeaders);
			fwrite($hFile, "\r\n");
			fwrite($hFile, $this->sBody);
			fclose($hFile);
		}
	}
	/**
	 * Reads the raw message from a file previously created by SaveToFile
	 */
	public static function ReadFromFile($sFileName)
	{
		$sContent = file_get_contents($sFileName);
		$aMatches = array();
        if (preg_match("/^(.+?)\r?\n\r?\n(.*)/s", $sContent, $aMatches))
        {
			$sRawHeaders = $aMatches[1]."\r\n"; // restore the line termination on the last line of the header
			$sBody = $aMatches[2];
			return new RawEmailMessage(basename($sFileName), $sRawHeaders, $sBody);
        }
        return null;
	}

	/**
	 * Parses the raw message headers into a hash array
	 */
    protected function ParseHeaders()
    {
		$raw_headers = rtrim($this->sRawHeaders);
		$aParams['include_bodies'] = false;
		$aParams['decode_bodies'] = false;
		$aParams['decode_headers'] = true;
		$aParams['crlf'] = "\r\n";
		$aParams['input'] = $this->sRawHeaders.$aParams['crlf'];
		$oStructure = Mail_mimeDecode::decode( $aParams );
		$this->aParsedHeaders = $oStructure->headers;
//
//		if (!isset($oStructure->ctype_parameters['charset']))
//		{
//			$this->aWarnings[] = 'No character set found, using ISO-8859-1 by default';
//			$sCharset = 'ISO-8859-1';
//		}
//		else
//		{
//			$sCharset = $oStructure->ctype_parameters['charset'];
//		}
	}
	
	/**
	 * Get the address of the originator of the email
	 */
	protected function GetSenderEmail($aHeaders)
	{
		$sEmailPattern = '/([-_\.0-9a-zA-Z]+)@([-_\.0-9a-zA-Z]+)/';
		
		$aMatches = array();
		if (is_array($aHeaders['sender']) && preg_match($sEmailPattern, array_pop($this->aParsedHeaders['sender']), $aMatches))
		{
			$sEmail = $aMatches[1].'@'.$aMatches[2];		
		}
		else if (preg_match($sEmailPattern, trim($this->aParsedHeaders['from']), $aMatches))
		{
			$sEmail = $aMatches[1].'@'.$aMatches[2];
		}
		else if (preg_match($sEmailPattern, trim($this->aParsedHeaders['reply-to']), $aMatches))
		{
			$sEmail = $aMatches[1].'@'.$aMatches[2];
		}
			
		return $sEmail;
	}
	
	/**
	 * Get the subject / title of the message
	 */
	 protected function GetSubject()
	 {
	 	return $this->aParsedHeaders['subject'];
	 }

	/**
	 * Get messageId i.e. unique identifier of the message
	 */
	protected function GetMessageId()
	{
		return isset($this->aParsedHeaders['message-id']) ? $this->aParsedHeaders['message-id'] : 'ZZZNotFoundZZZ';
	}
	 
	protected function GetCallerName()
	{
		$sFrom = $this->aParsedHeaders['from'];
		if (preg_match("/(.+) <.+>$/", $sFrom, $aMatches))
		{
			$sName = $aMatches[1];
		}
		else if (preg_match("/.+ \(([^\)]+) at [^\)]+\)$/", $sFrom, $aMatches))
		{
			$sName = $aMatches[1];	
		}
		else if (preg_match("/^([^@]+)@.+$/", $sFrom, $aMatches))
		{
			$sName = $aMatches[1]; // Use the first part of the email address before the @
		}
		
		// Try to "pretty format" the names
		if (preg_match("/^([^\.]+)[\._]([^\.]+)$/", $sName, $aMatches))
		{
			// transform "john.doe" or "john_doe" into "john doe"
			$sName = $aMatches[1].' '.$aMatches[2];
		}

		if (preg_match("/^([^,]+), ([^,]+)$/", $sName, $aMatches))
		{
			// transform "doe, john" into "john doe"
			$sName = $aMatches[2].' '.$aMatches[1];
		}
		
		// Name are sometimes quoted by double quotes... remove them
		if (preg_match('/"(.+)"/', $sName, $aMatches))
		{
			$sName = $aMatches[1];
		}
		$sName = ucwords(strtolower($sName)); // Even prettier: make each first letter of each word - and only them - upper case
		return $sName;
	}
	
	/**
	 * Scans an array of 'parts' for a part of the given primary / secondary type
	 */
	protected function ScanParts($aParts, $sPrimaryType, $sSecondaryPart)
	{
		$index = 0;
		$sBody = '';
		while($index < count($aParts))
		{
			if (($aParts[$index]->ctype_primary == $sPrimaryType) &&
			   ($aParts[$index]->ctype_secondary == $sSecondaryPart))
			{
				if (preg_match('/charset="?([^";]*)"?;?/', $aParts[$index]->headers['content-type'], $aMatches))
				{
					$sCharset = strtoupper(trim($aMatches[1]));
					$sBody = iconv($sCharset, 'UTF-8//IGNORE//TRANSLIT', $aParts[$index]->body);
					if ($sBody == '')
					{
						// Unable to decode the character set... let's hope something is still readable as-is
						$sBody = $aParts[$index]->body;
						// TODO: log a warning 
					}
				}
				else
				{
					$sBody = $aParts[$index]->body;
				}
				// Found the desired body 
				break;
			}
			else if (is_array($aParts[$index]->parts))
			{
				$sBody = $this->ScanParts($aParts[$index]->parts, $sPrimaryType, $sSecondaryPart);
				if (!empty($sBody))
				{
					// Found the desired body 
					break;
				}
			}
			$index++;
		}
		return $sBody;	
	}
}

////////////////////////////////////////////////////////////////////
/**
 * A decoded message
 */
class EmailMessage {
	public $sUIDL;
	public $sMessageId;
	public $sSubject;
	public $sCallerEmail;
	public $sCallerName;
	public $sRecipient;
	public $aReferences;
	public $aBodyParts;
	public $aAttachments;
	public $sDecodeStatus;		
	
	public function __construct($sUIDL, $sMessageId, $sSubject, $sCallerEmail, $sCallerName, $sRecipient, $aReferences, $aBodyParts, $aAttachments, $sDecodeStatus)
	{
		$this->sUIDL = $sUIDL;
		$this->sMessageId = $sMessageId;
		$this->sSubject = $sSubject;
		$this->sCallerEmail = $sCallerEmail;
		$this->sCallerName = $sCallerName;
		$this->sRecipient = $sRecipient;
		$this->aReferences = $aReferences;
		$this->aBodyParts = $aBodyParts;
		$this->aAttachments = $aAttachments;
		$this->sDecodeStatus = $sDecodeStatus;		
	}
	/**
	 * Retrieve the body part of the message in the given format
	 * @param $sFormat string Either text or html
	 * @param $bNewPartOnly True to get only the 'new' (i.e reply) part of the text
	 */
	public function GetBody($sFormat, $bNewPartOnly = false)
	{
		
	}

	/**
	 * Archives the message into a file
	 */
	public function SaveToFile($sFile)
	{
		
	}
	/**
	 * Read the message from an archived file
	 */
	public function ReadFromFile($sFile)
	{
		
	}
}

////////////////////////////////////////////////////////////////////
/**
 * A source of messages either POP3 or Files
 */
abstract class EmailSource
{
	/**
	 * Get the number of messages to process
	 * @return integer The number of available messages
	 */
	abstract public function GetMessagesCount();
	
	/**
	 * Retrieves the message of the given index [0..Count]
	 * @param $index integer The index between zero and count
	 * @return EmailDecoder
	 */
	abstract public function GetMessage($index);

	/**
	 * Deletes the message of the given index [0..Count] from the mailbox
	 * @param $index integer The index between zero and count
	 */
	abstract public function DeleteMessage($index);
	
	/**
	 * Name of the eMail source
	 */
	 abstract public function GetName();
}

////////////////////////////////////////////////////////////////////
/**
 * Reads messages from files stored in a given folder, ordered by their creation date
 */
class TestEmailSource extends EmailSource
{
	protected $sSourceDir;
	protected $aMessages;
	
	public function __construct($sSourceDir)
	{
		$this->sSourceDir = $sSourceDir;
		$this->aMessages = array();
		$hDir = opendir($this->sSourceDir);
		while(($sFile = readdir($hDir)) !== false)
		{
			if (($sFile != '.') && ($sFile != '..'))
			{
				$sExtension = pathinfo($sFile,PATHINFO_EXTENSION);
				if ($sExtension == 'eml')
				{
					$this->aMessages[] = $sFile;
				}
			}
		}
		closedir($hDir);

		sort($this->aMessages);
	}	
	/**
	 * Get the number of messages to process
	 * @return integer The number of available messages
	 */
	public function GetMessagesCount()
	{
		return count($this->aMessages);
	}
	
	/**
	 * Retrieves the message of the given index [0..Count]
	 * @param $index integer The index between zero and count
	 * @return EmailDecoder
	 */
	public function GetMessage($index)
	{
		return RawEmailMessage::ReadFromFile($this->sSourceDir.'/'.$this->aMessages[$index]);
	}

	/**
	 * Simulates the deletion of the message of the given index [0..Count] from the mailbox... does nothing
	 * @param $index integer The index between zero and count
	 */
	public function DeleteMessage($index)
	{
		// Do nothing !
	}

	/**
	 * Name of the eMail source
	 */
	 public function GetName()
	 {
	 	return 'Test Source (from '.$this->sSourceDir.')';
	 }
	 
	/**
	 * Get the list (with their IDs) of all the messages
	 * @return Array An array of hashes: 'msg_id' => index 'uild' => message identifier
	 */
	 public function GetListing()
	 {
		$aListing = array();
		foreach($this->aMessages as $index => $sName)
		{
			$aListing[] = array('msd_id' => $index, 'uidl' => basename($sName));
		}
		return $aListing;
	 }
}

////////////////////////////////////////////////////////////////////
/**
 * Reads messages from a POP3 source
 */
class POP3EmailSource extends EmailSource
{
	protected $oPop3 = null;
	protected $sServer = '';
	protected $sLogin = '';
	
	public function __construct($sServer, $iPort, $sLogin, $sPwd)
	{
		$this->oPop3 = new Net_POP3();
		$this->sServer = $sServer;
		$this->sLogin = $sLogin;
		$bRet = $this->oPop3->connect($sServer, $iPort);
		if (!$bRet)
		{
			throw new Exception("Cannot connect to $sServer on port $iPort");
		}
		
		$bRet = $this->oPop3->login($sLogin, $sPwd);
		if (!$bRet)
		{
			throw new Exception("Cannot login using $sLogin with pwd: $sPwd");
		}
	}	

	/**
	 * Get the number of messages to process
	 * @return integer The number of available messages
	 */
	public function GetMessagesCount()
	{
		return $this->oPop3->numMsg();	
	}
	
	/**
	 * Retrieves the message of the given index [0..Count]
	 * @param $index integer The index between zero and count
	 * @return EmailDecoder
	 */
	public function GetMessage($index)
	{
		$sRawHeaders = $this->oPop3->getRawHeaders(1+$index);
		$sBody = $this->oPop3->getBody(1+$index);
		$sUIDL = $this->oPop3->_cmdUidl(1+$index);
		
		return new RawEmailMessage($sUIDL, $sRawHeaders, $sBody);
	}

	/**
	 * Deletes the message of the given index [0..Count] from the mailbox
	 * @param $index integer The index between zero and count
	 */
	public function DeleteMessage($index)
	{
		$this->oPop3->deleteMsg(1+$index);
	}
	
	/**
	 * Name of the eMail source
	 */
	 public function GetName()
	 {
	 	return 'POP3 Mailbox (server: '.$this->sServer.', login: '.$this->sLogin.')';
	 }
	 
	/**
	 * Get the list (with their IDs) of all the messages
	 * @return Array An array of hashes: 'msg_id' => index 'uild' => message identifier
	 */
	 public function GetListing()
	 {
		return $this->oPop3->_cmdUidl();
	 }
}

///////////////////////////////////////////////////////////////////////////////////////

abstract class EmailProcessor
{
	const NO_ACTION = 0;
	const DELETE_MESSAGE = 1;
	const PROCESS_MESSAGE = 2;
	
	abstract public function ListEmailSources();
	
	abstract public function DispatchMessage($index, $sUIDL);

	abstract public function ProcessMessage($index, EmailMessage $oEmail);
	
	abstract public function OnDecodeError(EmailMessage $oEmail);
}

/////////////////////////////////////////////////////////////////////////////

class TestEmailProcessor extends EmailProcessor
{
	public function ListEmailSources()
	{
		return array( 0 => new TestEmailSource(dirname(__FILE__).'/log'));
	}
	
	public function DispatchMessage($index, $sUIDL)
	{
		return EmailProcessor::PROCESS_MESSAGE;
	}
	
	public function ProcessMessage($index, EmailMessage $oEmail)
	{
		$sMessage = "[$index] ".$oEmail->sMessageId.' - From: '.$oEmail->sCallerEmail.' ['.$oEmail->sCallerName.']'.' Subject: '.$oEmail->sSubject;
		if (!utils::IsModeCLI())
		{
			$sMessage = '<p>'.htmlentities($sMessage, ENT_QUOTES, 'UTF-8').'</p>';
		}
		echo $sMessage."\n";
		return EmailProcessor::NO_ACTION;	
	}
	
	public function OnDecodeError(EmailMessage $oEmail)
	{
		
	}
}

/////////////////////////////////////////////////////////////////////////////////////

class EmailBackgroundProcess implements iBackgroundProcess
{
	static $aEmailProcessors = array();
	
	static public function RegisterEmailProcessor($sClassName)
	{
		self::$aEmailProcessors[] = $sClassName;
	}
	
	public function GetPeriodicity()
	{	
		return 30; // seconds
	}

	public function Process($iTimeLimit)
	{
		$iTotalMessages = 0;
		foreach(self::$aEmailProcessors as $sProcessorClass)
		{
			$oProcessor = new $sProcessorClass();
			$aSources = $oProcessor->ListEmailSources();
			foreach($aSources as $oSource)
			{
				$aMessages = $oSource->GetListing();
				
				$iMsgCount = count($aMessages);
				 
				for($iMessage = 0; $iMessage < $iMsgCount; $iMessage++)
				{
					$sUILD = $aMessages[$iMessage]['uild'];
					
					$iActionCode = $oProcessor->DispatchMessage($sUILD);
			
					switch($iActionCode)
					{
						case EmailProcessor::DELETE_MESSAGE:
						$oSource->DeleteMessage($iMessage);
						break;
						
						case EmailProcessor::PROCESS_MESSAGE;
						$oRawEmail = $oSource->GetMessage($iMessage);
						$oEmail = $oRawEmail->Decode(); 
						$iNextActionCode = $oProcessor->ProcessMessage($iMessage, $oEmail);
						switch($iNextActionCode)
						{
							case EmailProcessor::DELETE_MESSAGE:
							$oSource->DeleteMessage($iMessage);
							break;
							
							default:
							// Do nothing...
						}
						break;
			
						case EmailProcessor::NO_ACTION:
						default:
						// Do nothing
						break;
					}
					if (time() > $iTimeLimit) break; // We'll do the rest later
				}
				if (time() > $iTimeLimit) break; // We'll do the rest later
			}
			if (time() > $iTimeLimit) break; // We'll do the rest later
		}
		return "Nb message(s) processed: $iTotalMessages";
	}
}

?>