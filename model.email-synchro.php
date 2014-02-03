<?php
// Copyright (C) 2010-2013 Combodo SARL
//
//   This program is free software; you can redistribute it and/or modify
//   it under the terms of the GNU Lesser General Public License as published by
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

require_once(dirname(__FILE__).'/rawemailmessage.class.inc.php');
require_once(dirname(__FILE__).'/main.email-synchro.php');

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
			'indexes' => array(
					array('uidl'), // Index on UIDLs for faster search
			),		
		);
		MetaModel::Init_Params($aParams);

		MetaModel::Init_AddAttribute(new AttributeInteger("ticket_id", array("allowed_values"=>null, "sql"=>"ticket_id", "default_value"=>0, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeString("uidl", array("allowed_values"=>null, "sql"=>"uidl", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeString("message_id", array("allowed_values"=>null, "sql"=>"message_id", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeText("message_text", array("allowed_values"=>null, "sql"=>"message_text", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeText("references", array("allowed_values"=>null, "sql"=>"references", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeText("thread_index", array("allowed_values"=>null, "sql"=>"thread_index", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeDateTime("message_date", array("allowed_values"=>null, "sql"=>"message_date", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeEnum("status", array("allowed_values"=>new ValueSetEnum('ok,error'), "sql"=>"status", "default_value"=>'ok', "is_null_allowed"=>false, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeText("error_message", array("allowed_values"=>null, "sql"=>"error_message", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		
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



////////////////////////////////////////////////////////////////////
/**
 * A source of messages either POP3 or Files
 */
abstract class EmailSource
{
	protected $sLastErrorSubject;
	protected $sLastErrorMessage;
	protected $sPartsOrder;
	protected $token;
	
	public function __construct()
	{
		$this->sPartsOrder = 'text/plain,text/html'; // Default value can be changed via SetPartsOrder
		$this->token  =null;
	}
	
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
	
	public function GetLastErrorMessage()
	{
		return $this->sLastErrorMessage;
	}
	
	/**
	 * Preferred order for retrieving the mail "body" when scanning a multiparts emails
	 * @param $sPartsOrder string A comma separated list of MIME types e.g. text/plain,text/html
	 */
	public function SetPartsOrder($sPartsOrder)
	{
		$this->sPartsOrder = $sPartsOrder;
	}
	/**
	 * Preferred order for retrieving the mail "body" when scanning a multiparts emails
	 * @return string A comma separated list of MIME types e.g. text/plain,text/html
	 */
	public function GetPartsOrder()
	{
		return $this->sPartsOrder;
	}
	/**
	 * Set an opaque reference token for use by the caller...
	 * @param mixed $token
	 */
 	public function SetToken($token)
 	{
 		$this->token = $token;
 	}
 	/**
 	 * Get the reference token set earlier....
 	 * @return mixed The token set by SetToken()
 	 */
 	public function GetToken()
 	{
 		return $this->token;
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
		parent::__construct();
		//$this->sPartsOrder = 'text/html,text/plain'; // Default value can be changed via SetPartsOrder
		
		$this->sLastErrorSubject = '';
		$this->sLastErrorMessage = '';
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
		return MessageFromMailbox::FromFile($this->sSourceDir.'/'.$this->aMessages[$index]);
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
		parent::__construct();
		$this->sLastErrorSubject = '';
		$this->sLastErrorMessage = '';
		
		require_once(dirname(__FILE__).'/POP3.php'); //Include this file only if needed since PEAR desactivates the error reporting
		
		$this->oPop3 = new Net_POP3();
		$this->sServer = $sServer;
		$this->sLogin = $sLogin;
		$bRet = $this->oPop3->connect($sServer, $iPort);
		if ($bRet !== true)
		{
			if (class_exists('EventHealthIssue'))
			{
				EventHealthIssue::LogHealthIssue('combodo-email-synchro', "Cannot connect to POP3 server: '$sServer' on port $iPort");
			}
			throw new Exception("Cannot connect to $sServer on port $iPort");
		}
		
		$bRet = $this->oPop3->login($sLogin, $sPwd, $authOption);
		if ($bRet !== true)
		{
			if (class_exists('EventHealthIssue'))
			{
				EventHealthIssue::LogHealthIssue('combodo-email-synchro', "Cannot login on server '$sServer' using '$sLogin' with pwd: $sPwd");
			}
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
		$aUIDL = $this->oPop3->_cmdUidl(1+$index);
		
		return new MessageFromMailbox($aUIDL['uidl'], $sRawHeaders, $sBody);
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

/**
 * Read messages from an IMAP mailbox using PHP's IMAP extension
 * Note: in theory PHP IMAP methods can also be used to connect to
 *       a POP3 mailbox, but in practice the missing emulation of
 *       actual unique identifiers (UIDLs) for the messages makes
 *       this unusable for our particular purpose
 */
class IMAPEmailSource extends EmailSource
{
	protected $rImapConn = null;
	protected $sLogin = '';
	
	public function __construct($sServer, $iPort, $sLogin, $sPwd, $sMailbox, $aOptions)
	{
		parent::__construct();
		$this->sLastErrorSubject = '';
		$this->sLastErrorMessage = '';
		$this->sLogin = $sLogin;

		$sOptions = '';
		if (count($aOptions) > 0)
		{
			$sOptions = '/'.implode('/',$aOptions);
		}
		
		$sIMAPConnStr = "{{$sServer}:{$iPort}$sOptions}$sMailbox";
		$this->rImapConn = imap_open($sIMAPConnStr, $sLogin, $sPwd );
		if ($this->rImapConn === false)
		{
			if (class_exists('EventHealthIssue'))
			{
				EventHealthIssue::LogHealthIssue('combodo-email-synchro', "Cannot connect to IMAP server: '$sIMAPConnStr', with credentials: '$sLogin'/'$sPwd'");
			}
			print_r(imap_errors());
			throw new Exception("Cannot connect to IMAP server: '$sIMAPConnStr', with credentials: '$sLogin'/'$sPwd'");
		}
	}	

	/**
	 * Get the number of messages to process
	 * @return integer The number of available messages
	 */
	public function GetMessagesCount()
	{
		$oInfo = imap_check($this->rImapConn);
		if ($oInfo !== false) return $oInfo->Nmsgs;
		
		return 0;	
	}
	
	/**
	 * Retrieves the message of the given index [0..Count]
	 * @param $index integer The index between zero and count
	 * @return EmailDecoder
	 */
	public function GetMessage($index)
	{
		$sRawHeaders = imap_fetchheader($this->rImapConn, 1+$index);
		$sBody = imap_body($this->rImapConn, 1+$index, FT_PEEK);
		$aOverviews = imap_fetch_overview($this->rImapConn, 1+$index);
		$oOverview = array_pop($aOverviews);
		return new MessageFromMailbox($oOverview->uid, $sRawHeaders, $sBody);
	}

	/**
	 * Deletes the message of the given index [0..Count] from the mailbox
	 * @param $index integer The index between zero and count
	 */
	public function DeleteMessage($index)
	{
		$ret = imap_delete($this->rImapConn, (1+$index).':'.(1+$index));
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
	 	$ret = null;
	 	
	 	$oInfo = imap_check($this->rImapConn);
        if ($oInfo !== false)
        {
        	$sRange = "1:".$oInfo->Nmsgs;

        	$ret = array();
			$aResponse = imap_fetch_overview($this->rImapConn,$sRange);
			
			foreach ($aResponse as $aMessage)
			{
				$ret[] = array('msg_id' => $aMessage->msgno, 'uidl' => $aMessage->uid);
			}
        }
        
		return $ret;
	 }
	 
	 public function Disconnect()
	 {
	 	imap_close($this->rImapConn, CL_EXPUNGE);
	 	$this->rImapConn = null; // Just to be sure
	 }
}

// For testing: uncomment the line below to process test messages stored as files in the 'log' directory
//EmailBackgroundProcess::RegisterEmailProcessor('TestEmailProcessor');

