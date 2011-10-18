<?php
// Copyright (C) 2010 Combodo SARL
//
include APPROOT.'modules/combodo-email-synchro/POP3.php';			// PEAR POP3
include APPROOT.'modules/combodo-email-synchro/mimeDecode.php';	// MODIFIED PEAR mimeDecode

/**
 * Extension to to keep track of the emails thread associated with any Ticket
 *
 * @author      Erwan Taloc <erwan.taloc@combodo.com>
 * @author      Romain Quetiez <romain.quetiez@combodo.com>
 * @author      Denis Flaven <denis.flaven@combodo.com>
 * @license     http://www.opensource.org/licenses/gpl-3.0.html LGPL
 */

class EmailReplica extends DBObject
{
	public static function Init()
	{
		$aParams = array
		(
			"category" => "requestmgmt",
			"key_type" => "autoincrement",
			"name_attcode" => "uidl",
			"state_attcode" => "",
			"reconc_keys" => array("message_id"),
			"db_table" => "email_replica",
			"db_key_field" => "id",
			"db_finalclass_field" => "",
		);
		MetaModel::Init_Params($aParams);

		MetaModel::Init_AddAttribute(new AttributeInteger("ticket_id", array("allowed_values"=>null, "sql"=>"ticket_id", "default_value"=>0, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeString("uidl", array("allowed_values"=>null, "sql"=>"uidl", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeString("message_id", array("allowed_values"=>null, "sql"=>"message_id", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeText("message_text", array("allowed_values"=>null, "sql"=>"message_text", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeText("references", array("allowed_values"=>null, "sql"=>"references", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeText("thread_index", array("allowed_values"=>null, "sql"=>"thread_index", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeDateTime("message_date", array("allowed_values"=>null, "sql"=>"message_date", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
	}
	
	/**
	 * Generate an initial 'thread-index' header, compatible with MS Outlook
	 * and containing a reference to the iTop ticket it is related to
	 * @param $iTicketId integer The iD of the ticket
	 * @param $sTicketClass string The class of the ticket
	 * @return string The content of the thread-index header
	 */
	protected static function MakeMSThreadIndex($oObject)
	{
		// 'Thread-index' is a Microsoft specific heqder used by some versions (2003 / XP) of Outlook
		// instead of relying on the 'References' header. It is made of 27 bytes (random ??) which look
		// like a BASE64 string, and then for each new message in the thread 5 more 'base64-like' chars
		// are added at the end

		// Let's generate something that looks like a valid thread-index and can be decoded into a reference
		// to an iTop object. Since all thread-index I've seen seem to start with Ac... let's do it. Then
		// put iTop to sign it, then put the id of the ticket on 5 hex characters (zero padded) which allows up
		// to 1048575 tickets, then the name of the class, right-padded with zeroes to 16 characters !!
		// For example: AciTop000f100000UserRequest means UserRequest ticket id = 0xf1 = 241
		return sprintf("AciTop%05x%'0-16s", $oObject->GetKey(), get_class($oObject));
	}
	
	/**
	 * Get a valid Thread-index header for the ticket
	 * @param $iTicketId integer The identifier of the ticket
	 * @param $sTicketClass string The class of the ticket
	 * @return string The content of the thread-index header
	 */
	public static function GetNextMSThreadIndex($oTicket)
	{
		$oSearch = new DBObjectSearch('EmailReplica');
		$oSearch->AddCondition('ticket_id', $oTicket->GetKey());
		$oSet = new DBObjectSet($oSearch, array('message_date' => false));
		if ($oSet->Count() == 0)
		{
			$sThreadIndex = self::MakeMSThreadIndex($oTicket);
		}
		else
		{
			$oLatestReplica = $oSet->Fetch();
			$sLatestThreadIndex = $oLatestReplica->Get('thread_index');
			if ($sLatestThreadIndex == '')
			{
				$sThreadIndex = self::MakeMSThreadIndex($oTicket);
			}
			else
			{
				// The new index is obtained by appending 5 (random ?) base64 characters
				// at the end of the previous thread index
				$sThreadIndex = $sLatestThreadIndex.substr( base64_encode(sprintf('%010x', time())), 0, 5);
			}
		}
		return $sThreadIndex;
	}
	
	/**
	 * Find the ticket corresponding to the given MSThreadIndex either by decoding it
	 * or by finding an Emailreplica object in the same discussion thread
	 *
	 */
	public static function FindTicketFromMSThreadIndex($sMSThreadIndex)
	{
		$sShortIndex = substr($sMSThreadIndex, 0, 27);
		$oTicket = null;
		if (preg_match('/AciTop(-[0-9a-f]{5})(.{16})$/', $sShortIndex, $aMatches))
		{
			// Found a thread-index that seems generated by iTop
			$sClass = $aMatches[2];
			$iTicketId = $aMatches[1];
			if (MetaModel::IsValidClass($sClass))
			{
				$oTicket = MetaModel::GetObject($sClass, $iTicketId, false /* Caution the ticket may not exist */);
			}
		}
		if ($oTicket == null)
		{
			$oSearch = new DBObjectSearch('EmailReplica');
			$oSearch->AddCondition('thread_index', $sMSThreadIndex.'%', 'Like');
			$oSet = new DBObjectSet($oSearch, array('message_date' => false));
			if ($oSet->Count() != 0)
			{
				$oReplica = $oSet->Fetch();
				$iTicketId = $oReplica->Get('ticket_id');
				$oTicket = MetaModel::GetObject('Ticket', $iTicketId, false /* Caution the ticket may not exist */);
			}
		}
		return $oTicket;
	}
	
	public static function MakeReferencesHeader($sInitialMessageId, $oObject)
	{
		$sReferences = '';
		if ($sInitialMessageId != '')
		{
			$sReferences .= $sInitialMessageId.' ';
		}
		$sReferences .= self::MakeMessageId($oObject);
		return $sReferences;
	}
	
	public static function MakeMessageId($oObject)
	{
		$sMessageId = sprintf('<iTop_%s_%d_%f@%s.openitop.org>', get_class($oObject), $oObject->GetKey(), microtime(true /* get as float*/), MetaModel::GetConfig()->Get('session_name'));
		return $sMessageId;
	}
}

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
		
		// Search for the text/plain body part
		$iPartIndex = 0;
		$bFound = false;
		$sBodyText = '';
		$sReferences = isset($oStructure->headers['references']) ? $oStructure->headers['references'] : '';
		if (!isset($oStructure->parts) || count($oStructure->parts) == 0)
		{
			$sBodyText = iconv($sCharset, 'UTF-8//IGNORE//TRANSLIT', $oStructure->body);
		}
		else
		{
			// Multi-parts encoding, find a text or HTML part			
			$sBodyText = $this->ScanParts($oStructure->parts, 'text', 'plain');
			if (empty($sBodyText))
			{
				// Try to find an HTML part...
				$sBodyText = $this->ScanParts($oStructure->parts, 'text', 'html');
				$sBodyFormat = 'text/html';
			}
			else
			{
				$sBodyFormat = 'text/plain';
			}
		}
		
		$sRecipient = '';
		$aReferences = explode(' ', $sReferences );
		$sThreadIndex = $this->GetMSThreadIndex();
		$aBodyParts = '';
		$aAttachments = $this->GetAttachments($oStructure->parts);
		$sDecodeStatus = '';
		$oRelatedObject = $this->GetRelatedObject();
		
		return new EmailMessage($this->sUIDL, $sMessageId, $sSubject, $sCallerEmail, $sCallerName, $sRecipient, $aReferences, $sThreadIndex, $sBodyText, $sBodyFormat, $aAttachments, $oRelatedObject, $sDecodeStatus);
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
	 
	/**
	 * Get MS Thread-index for this message
	 */
	protected function GetMSThreadIndex()
	{
		return isset($this->aParsedHeaders['thread-index']) ? $this->aParsedHeaders['thread-index'] : '';
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
		// Warning: the line below generates incorrect utf-8 for the character 'é' when running on Windows/PHP 5.3.6
		//$sName = ucwords(strtolower($sName)); // Even prettier: make each first letter of each word - and only them - upper case
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
			   ($aParts[$index]->ctype_secondary == $sSecondaryPart) &&
			   ($aParts[$index]->disposition != 'attachment') )
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
//echo "Unable to decode the body...\n";
					}
				}
				else
				{
					$sBody = $aParts[$index]->body;
				}
				// Found the desired body 
//echo "Found a body...\n";
				break;
			}
			else if (is_array($aParts[$index]->parts))
			{
//echo "The part $index contain sub-parts, recursing...\n";
				$sBody = $this->ScanParts($aParts[$index]->parts, $sPrimaryType, $sSecondaryPart);
				if (!empty($sBody))
				{
					// Found the desired body 
//echo "Found a body...\n";
					break;
				}
			}
			$index++;
		}
		return $sBody;	
	}
	
	function GetAttachments($aParts, $aAttachments = array())
	{
		$index = 0;
		foreach($aParts as $aPart)
		{
			if (($aPart->disposition == 'attachment') || ( ($aPart->ctype_primary != 'multipart') && ($aPart->ctype_primary != 'text')) )
			{

				$sMimeType = $aPart->ctype_primary.'/'.$aPart->ctype_secondary;
				$sFileName = @$aPart->d_parameters['filename'];
				if (empty($sFileName))
				{
					$sFileName = @$aPart->ctype_parameters['name'];
				}
				if (empty($sFileName))
				{
					$sFileName = sprintf('%s%03d.%s',$aPart->ctype_primary, $index, $aPart->ctype_secondary); // i.e. image001.jpg
				}
				$sContent = $aPart->body;
				$aAttachments[] = array('mimeType' => $sMimeType, 'filename' => $sFileName, 'content' => $sContent);				
			}
			else if (is_array($aPart->parts))
			{
				$aAttachments = $this->GetAttachments($aPart->parts, $aAttachments);
			}
			$index++;
		}
		return $aAttachments;
	}
	
	public function SendAsAttachment($sTo, $sFrom, $sSubject, $sTextMessage)
	{
  		$sDelimiter = '== iTopEmailPart--- '.md5(date('r', time()))." ==";
 		$aHeaders = array('Content-Type' => "multipart/mixed;\r\n boundary=\"{$sDelimiter}\"\r\n");
  		
  		$sTextHeader = "Content-Type: text/plain; charset='utf-8'\nContent-Transfer-Encoding: 8bit\r\n";
  		$sAttachmentHeader = "Content-Type: text/plain;\r\n Name=\"Original Message.eml\"\r\n";
  		$sAttachmentHeader .= "Content-Transfer-Encoding: base64\r\nContent-Disposition: attachment;\r\n filename=\"Message.eml\"\r\n";
  		$sAttachmentHeader .= "\r\n";
  		$sAttachment = 	$this->sRawHeaders."\r\n".$this->sBody;
		$sAttachment = chunk_split(base64_encode($sAttachment));
  		$sBody = "This is a multi-part message in MIME format.\r\n--".$sDelimiter."\r\n".$sTextHeader."\r\n".$sTextMessage."\r\n--".$sDelimiter."\r\n".$sAttachmentHeader.$sAttachment."\r\n--".$sDelimiter."--";
  		$oEmail = new Email($sTo, $sSubject, $sBody, $aHeaders);
  		$aIssues = array();
  		$oEmail->SetRecipientFrom($sFrom);
  		$oEmail->Send($aIssues, true /* bForceSynchronous */, null /* $oLog */);
	}
	
	protected function ParseMessageId($sMessageId)
	{
		$aMatches = array();
		$ret = false;
		if (preg_match('/^<iTop_(.+)_([0-9]+)_.+@.+openitop\.org>$/', $sMessageId, $aMatches))
		{
			$ret = array('class' => $aMatches[1], 'id' => $aMatches[2]);
		}
		return $ret;
	}
	
	/**
	 * Find-out (by analyzing the headers) if the message is related to an iTop object
	 * @return mixed Either the related object or null if none
	 */
	protected function GetRelatedObject()
	{
		// First look if the message is not a direct reply to a message sent by iTop
		if (isset($this->aParsedHeaders['in-reply-to']))
		{
			$ret = $this->ParseMessageId($this->aParsedHeaders['in-reply-to']);
			if ($ret !== false)
			{
				if (MetaModel::IsValidClass($ret['class']))
				{
					$oObject = MetaModel::GetObject($ret['class'], $ret['id'], false /* Caution the object may not exist */);
					if ($oObject != null) return $oObject;
				}
			}
		}

		// Second chance, look if a message sent by iTop is listed in the references
		$sReferences = isset($oStructure->headers['references']) ? $oStructure->headers['references'] : '';
		$aReferences = explode(' ', $sReferences );
		foreach($aReferences as $sReference)
		{
			$ret = $this->ParseMessageId($sReference);
			if ($ret !== false)
			{
				if (MetaModel::IsValidClass($ret['class']))
				{
					$oObject = MetaModel::GetObject($ret['class'], $ret['id'], false /* Caution the object may not exist */);
					if ($oObject != null) return $oObject;
				}
			}
		}
		
		// Third attempt: check the MS thread-index header, either via a direct pattern match
		// or by finding a similar message already processed
		return EmailReplica::FindTicketFromMSThreadIndex($sMSThreadIndex);
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
	public $sThreadIndex;
	public $sBodyText;
	public $sBodyFormat;
	public $aAttachments;
	public $oRelatedObject;
	public $sDecodeStatus;		
	
	public function __construct($sUIDL, $sMessageId, $sSubject, $sCallerEmail, $sCallerName, $sRecipient, $aReferences, $sThreadIndex, $sBodyText, $sBodyFormat, $aAttachments, $oRelatedObject, $sDecodeStatus)
	{
		$this->sUIDL = $sUIDL;
		$this->sMessageId = $sMessageId;
		$this->sSubject = $sSubject;
		$this->sCallerEmail = $sCallerEmail;
		$this->sCallerName = $sCallerName;
		$this->sRecipient = $sRecipient;
		$this->aReferences = $aReferences;
		$this->sThreadIndex = $sThreadIndex;
		$this->sBodyText = $sBodyText;
		$this->sBodyFormat = $sBodyFormat;
		$this->aAttachments = $aAttachments;
		$this->oRelatedObject = $oRelatedObject;
		$this->sDecodeStatus = $sDecodeStatus;		
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
	
	public function IsValid()
	{
		$bValid = !empty($this->sUIDL) && !empty($this->sSubject) && !empty($this->sCallerEmail) && !empty($this->sCallerName);
		foreach($this->aAttachments as $aAttachment)
		{
			$bValid = $bValid && !empty($aAttachment['mimeType']) && !empty($aAttachment['filename']) && !empty($aAttachment['content']);
		}
		return $bValid;
	}
	
	/**
	 * Produce a plain-text version of the body of the message
	 * @return string The plain-text version of the text
	 */
	public function StripTags($sText = null)
	{
		if ($sText == null)
		{
			$sText = $this->sBodyText;
		}
		// Process line breaks: remove carriage returns / line feeds that have no meaning in HTML => replace them by a plain space
		$sBodyText = str_replace(array('\n', '\r'), ' ',$sText);
		// Replace <p...>...</p> and <br/> by a carriage return
		$sBodyText = preg_replace('/<p[^>]*>/', '', $sBodyText);
		$sBodyText = str_replace(array('</br>', '<br/>', '<br>', '</p>'), "\n", $sBodyText);
		// remove tags (opening and ending tags MUST match!)
		$sBodyText = strip_tags($sBodyText);
		// Process some usual entities
		$sBodyText = html_entity_decode($sBodyText, ENT_QUOTES, 'UTF-8');
		
		return $sBodyText;
	}
	
	/**
	 * When the message is a reply or forward of another message, this method
	 * (tries to) extract the "new" part of the body
	 */
	public function GetNewPart()
	{
		$aIntroductoryPatterns = MetaModel::GetModuleSetting('combodo-email-synchro', 'introductory-patterns',
			array(
				'/^le .+ a écrit :$/i', // Thunderbird French
				'/^on .+ wrote:$/i', // Thunderbird English
				'|^[0-9]{4}/[0-9]{1,2}/[0-9]{1,2} .+:$|', // Gmail style
			)
		);
		
		if ($this->sBodyFormat == 'text/html')
		{
			// In HTML the "quoted" text is supposed to be inside "<blockquote....>.....</blockquote>"
			$sNewText = preg_replace("|<blockquote.+</blockquote>|iU", '', $this->sBodyText);
		}
		else // assume text/plain
		{
			// In plain text mode, exclude all lines starting with >
			$aLines = explode("\n", $this->sBodyText);
			$sPrevLine = '';
			foreach($aLines as $index => $sLine)
			{
				$sLine = trim($sLine);
				if (substr($sLine,0,1) == '>')
				{
					// Check if the line above contains one of the introductory pattern
					// like: On 10/09/2010 john.doe@test.com wrote:
					if ($index > 0)
					{
						$sPrevLine = trim($aLines[$index-1]);
						foreach($aIntroductoryPatterns as $sPattern)
						{
							if (preg_match($sPattern, trim($sPrevLine)))
							{
								// remove the introductary line
								unset($aLines[$index-1]);
								break;
							}
						}
					}
					unset($aLines[$index]);
				}
			}
			$sNewText = trim(implode("\n", $aLines));
		}
		return $sNewText;
	}
}

////////////////////////////////////////////////////////////////////
/**
 * A source of messages either POP3 or Files
 */
abstract class EmailSource
{
	protected $sLastErrorSubject;
	protected $sLastErrorMessage;
	
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
	/**
	 * Disconnect from the server
	 */
	 abstract public function Disconnect();

	public function GetLastErrorSubject()
	{
		return $this->sLastErrorSubject;
	}
	
	public function GetLastErrorMessage();
	{
		return $this->sLastErrorMessage;
	}
}

////////////////////////////////////////////////////////////////////
/**
 * Reads messages from files stored in a given folder, ordered by their creation date
 */
class TestEmailSource extends EmailSource
{
	protected $sSourceDir;
	protected $aMessages;
	protected $sName;
	
	public function __construct($sSourceDir, $sName)
	{
		$this->$sLastErrorSubject = '';
		$this->$sLastErrorMessage = '';
		$this->sSourceDir = $sSourceDir;
		$this->sName = $sName;
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
	 	if (!empty($this->sName))
	 	{
		 	return $this->sName;
	 	}
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
	 
	 public function Disconnect()
	 {
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
	
	public function __construct($sServer, $iPort, $sLogin, $sPwd, $authOption = true)
	{
		$this->$sLastErrorSubject = '';
		$this->$sLastErrorMessage = '';
		$this->oPop3 = new Net_POP3();
		$this->sServer = $sServer;
		$this->sLogin = $sLogin;
		$bRet = $this->oPop3->connect($sServer, $iPort);
		if ($bRet !== true)
		{
			throw new Exception("Cannot connect to $sServer on port $iPort");
		}
		
		$bRet = $this->oPop3->login($sLogin, $sPwd, $authOption);
		if ($bRet !== true)
		{
			throw new Exception("Cannot login using $sLogin with pwd: $sPwd ");
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
		$aUIDL = $this->oPop3->_cmdUidl(1+$index);
		
		return new RawEmailMessage($aUIDL['uidl'], $sRawHeaders, $sBody);
	}

	/**
	 * Deletes the message of the given index [0..Count] from the mailbox
	 * @param $index integer The index between zero and count
	 */
	public function DeleteMessage($index)
	{
		$ret = $this->oPop3->deleteMsg(1+$index);
		return $ret;

	}
	
	/**
	 * Name of the eMail source
	 */
	 public function GetName()
	 {
	 	return $this->sLogin;
	 }
	 
	/**
	 * Get the list (with their IDs) of all the messages
	 * @return Array An array of hashes: 'msg_id' => index 'uild' => message identifier
	 */
	 public function GetListing()
	 {
		$ret = $this->oPop3->_cmdUidl();
		if ($ret == null)
		{
			$ret = array();
		}
		return $ret;
	 }
	 
	 public function Disconnect()
	 {
	 	$this->oPop3->disconnect();
	 }
}

///////////////////////////////////////////////////////////////////////////////////////

abstract class EmailProcessor
{
	const NO_ACTION = 0;
	const DELETE_MESSAGE = 1;
	const PROCESS_MESSAGE = 2;
	const PROCESS_ERROR = 3;
	
	abstract public function ListEmailSources();
	
	abstract public function DispatchMessage(EmailSource $oSource, $index, $sUIDL, $oEmailReplica = null);

	abstract public function ProcessMessage(EmailSource $oSource, $index, EmailMessage $oEmail, $oEmailReplica = null);
	
	/**
	 * Not used yet !!!
	 */
	abstract public function OnDecodeError(EmailSource $oSource, $index, EmailMessage $oEmail);
	
	/**
	 * @var string To be set by ProcessMessage in case of error
	 */
	protected $sLastErrorSubject;
	/**
	 * @var string To be set by ProcessMessage in case of error
	 */
	protected $sLastErrorMessage;
	 
	/**
	 * Returns the subject for the last error when process ProcessMessage returns PROCESS_ERROR
	 * @return string The subject for the error message email
	 */
	public function GetLastErrorSubject()
	{
		return $this->sLastErrorSubject;
	}
	/**
	 * Returns the body of the message for the last error when process ProcessMessage returns PROCESS_ERROR
	 * @return string The body for the error message email
	 */
	public function GetLastErrorMessage()
	{
		return $this->sLastErrorMessage;
	}
}

/////////////////////////////////////////////////////////////////////////////

class TestEmailProcessor extends EmailProcessor
{
	public function ListEmailSources()
	{
		return array( 0 => new TestEmailSource(dirname(__FILE__).'/log'));
	}
	
	public function DispatchMessage(EmailSource $oSource, $index, $sUIDL, $oEmailReplica = null)
	{
		return EmailProcessor::PROCESS_MESSAGE;
	}
	
	public function ProcessMessage(EmailSource $oSource, $index, EmailMessage $oEmail, $oEmailReplica = null)
	{
		$sMessage = "[$index] ".$oEmail->sMessageId.' - From: '.$oEmail->sCallerEmail.' ['.$oEmail->sCallerName.']'.' Subject: '.$oEmail->sSubject.' - '.count($oEmail->aAttachments).' attachment(s)';
		if (empty($oEmail->sSubject))
		{
			$sMessage .= "\n=====================================\nERROR: Empty body for the message.\n";
		}
		$index = 0;
		foreach($oEmail->aAttachments as $aAttachment)
		{
			if (empty($aAttachment['mimeType']))
			{
				$sMessage .= "\n=====================================\nERROR: Empty mimeType for attachment #$index of the message.\n";
			}
			if (empty($aAttachment['filename']))
			{
				$sMessage .= "\n=====================================\nERROR: Empty filename for attachment #$index of the message.\n";
			}
			if (empty($aAttachment['content']))
			{
				$sMessage .= "\n=====================================\nERROR: Empty CONTENT for attachment #$index of the message.\n";
			}
			$index++;
		}
		if (!utils::IsModeCLI())
		{
			$sMessage = '<p>'.htmlentities($sMessage, ENT_QUOTES, 'UTF-8').'</p>';
		}
		echo $sMessage."\n";
		return EmailProcessor::NO_ACTION;	
	}
	
	public function OnDecodeError(EmailSource $oSource, $index, EmailMessage $oEmail)
	{
		
	}
}

/////////////////////////////////////////////////////////////////////////////////////

class EmailBackgroundProcess implements iBackgroundProcess
{
	protected static $aEmailProcessors = array();
	protected static $sSaveErrorsTo = '';
	protected static $sNotifyErrorsTo = '';
	protected static $sNotifyErrorsFrom = '';
	protected $bDebug;
	
	static public function RegisterEmailProcessor($sClassName)
	{
		self::$aEmailProcessors[] = $sClassName;
	}
	
	public function __construct()
	{
		$this->bDebug = MetaModel::GetModuleSetting('combodo-email-synchro', 'debug', false);
		self::$sSaveErrorsTo = MetaModel::GetModuleSetting('combodo-email-synchro', 'save_errors_to', '');
		self::$sNotifyErrorsTo = MetaModel::GetModuleSetting('combodo-email-synchro', 'notify_errors_to', '');
		self::$sNotifyErrorsFrom = MetaModel::GetModuleSetting('combodo-email-synchro', 'notify_errors_from', '');
	}

	protected function Trace($sText)
	{
		if ($this->bDebug)
		{
			echo $sText."\n";
		}
	}
	
	public function GetPeriodicity()
	{	
		return 30; // seconds
	}

	public function ReportError($sSubject, $sMessage, $oRawEmail)
	{
		if ( (self::$sNotifyErrorsTo != '') && (self::$sNotifyErrorsFrom != ''))
		{
			$oRawEmail->SendAsAttachment(self::$sNotifyErrorsTo, self::$sNotifyErrorsFrom, $sSubject, $sMessage);
			//@mail(self::$sNotifyErrorsTo, $sSubject, $sMessage, 'From: '.self::$sNotifyErrorsFrom);
		}
	}
	
	public function Process($iTimeLimit)
	{
		$iTotalMessages = 0;
		$iTotalProcessed = 0;
		$iTotalDeleted = 0;
		foreach(self::$aEmailProcessors as $sProcessorClass)
		{
			$oProcessor = new $sProcessorClass();
			$aSources = $oProcessor->ListEmailSources();
			foreach($aSources as $oSource)
			{
				$iMsgCount = $oSource->GetMessagesCount();
				$this->Trace("GetMessagesCount returned: $iMsgCount");			

				if ($iMsgCount != 0)
				{
					$aMessages = $oSource->GetListing();
					$iMsgCount = count($aMessages);

					// Get the corresponding EmailReplica object for each message
					$aUIDLs = array();
					for($iMessage = 0; $iMessage < $iMsgCount; $iMessage++)
					{
						$aUIDLs[] = $aMessages[$iMessage]['uidl'];
					}
					$sOQL = 'SELECT EmailReplica WHERE uidl IN ('.implode(',', CMDBSource::Quote($aUIDLs)).')';
					$this->Trace("Searching EmailReplicas: '$sOQL'");
					$oReplicaSet = new DBObjectSet(DBObjectSearch::FromOQL($sOQL));
					while($oReplica = $oReplicaSet->Fetch())
					{
						$aReplicas[$oReplica->Get('uidl')] = $oReplica;
					}				 
					for($iMessage = 0; $iMessage < $iMsgCount; $iMessage++)
					{
						$iTotalMessages++;
						$sUIDL = $aMessages[$iMessage]['uidl'];
						
						$oEmailReplica = array_key_exists($sUIDL, $aReplicas) ? $aReplicas[$sUIDL] : null;
	
						if ($oEmailReplica == null)
						{
							$this->Trace("Dispatching new message: $sUIDL");
						}
						else
						{
							$this->Trace("Dispatching old (already read) message: $sUIDL");
						}
						
						$iActionCode = $oProcessor->DispatchMessage($oSource, $iMessage, $sUIDL, $oEmailReplica);
				
						switch($iActionCode)
						{
							case EmailProcessor::DELETE_MESSAGE:
							$iTotalDeleted++;
							$this->Trace("Deleting message (and replica): $sUIDL");
							$oSource->DeleteMessage($iMessage);
							if (is_object($oEmailReplica))
							{
								$oEmailReplica->DBDelete();
							}
							break;
							
							case EmailProcessor::PROCESS_MESSAGE:
							$iTotalProcessed++;
							if ($oEmailReplica == null)
							{
								$this->Trace("Processing new message: $sUIDL");
							}
							else
							{
								$this->Trace("Processing old (already read) message: $sUIDL");
							}
	
	
							$oRawEmail = $oSource->GetMessage($iMessage);
							//$oRawEmail->SaveToFile(dirname(__FILE__)."/log/$sUIDL.eml"); // Uncomment the line to keep a local copy if needed
							$oEmail = $oRawEmail->Decode();
							if (!$oEmail->IsValid())
							{
								$sSubject = "iTop ticket creation or update from mail FAILED";
								$sMessage = "The message (".$sUIDL."), subject: '".$oEmail->sSubject."', was not decoded properly and therefore was not processed.\n";
								$sMessage .= "The original message is attached to this message.\n";
								$this->Trace($sMessage);
								EmailBackgroundProcess::ReportError($sSubject, $sMessage, $oRawEmail);
								$this->Trace("Deleting message (and replica): $sUIDL");
								$oSource->DeleteMessage($iMessage);
								if (is_object($oEmailReplica))
								{
									$oEmailReplica->DBDelete();
								}
							}
							else
							{
								$iNextActionCode = $oProcessor->ProcessMessage($oSource, $iMessage, $oEmail, $oEmailReplica);
								switch($iNextActionCode)
								{
									case EmailProcessor::DELETE_MESSAGE:
									$iTotalDeleted++;
									$this->Trace("Deleting message (and replica): $sUIDL");
									$oSource->DeleteMessage($iMessage);
									if (is_object($oEmailReplica))
									{
										$oEmailReplica->DBDelete();
									}
									break;
									
									case EmailProcessor::PROCESS_ERROR:
									$sSubject = $oSource->GetLastErrorSubject();
									$sMessage = $oSource->GetLastErrorMessage();
									EmailBackgroundProcess::ReportError($sSubject, $sMessage, $oRawEmail);
									$iTotalDeleted++;
									$this->Trace("Deleting message (and replica): $sUIDL");
									$oSource->DeleteMessage($iMessage);
									if (is_object($oEmailReplica))
									{
										$oEmailReplica->DBDelete();
									}
									break;

									default:
									// Do nothing...
								}
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
				$oSource->Disconnect();
			}
			if (time() > $iTimeLimit) break; // We'll do the rest later
		}
		return "Message(s) read: $iTotalMessages, message(s) processed: $iTotalProcessed, message(s) deleted: $iTotalDeleted";
	}
}

// For testing: uncomment the line below to process test messages stored as files in the 'log' directory
//EmailBackgroundProcess::RegisterEmailProcessor('TestEmailProcessor');
?>
