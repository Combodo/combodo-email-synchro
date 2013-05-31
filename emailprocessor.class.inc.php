<?php
///////////////////////////////////////////////////////////////////////////////////////
/**
 * Abstract class which serves as a skeleton for implementing your own processor of emails
 *
 */
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
	 * Called, before deleting the message from the source when the decoding fails
	 */
	public function OnDecodeError(EmailSource $oSource, $sUIDL, EmailMessage $oEmail, RawEmailMessage $oRawEmail)
	{
		$sSubject = "iTop ticket creation or update from mail FAILED";
		$sMessage = "The message (".$sUIDL."), subject: '".$oEmail->sSubject."', was not decoded properly and therefore was not processed.\n";
		$sMessage .= "The original message is attached to this message.\n";
		$this->Trace($sMessage);
		EmailBackgroundProcess::ReportError($sSubject, $sMessage, $oRawEmail);		
	}
	
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
/**
 * Used for the unit test of the EmailMessage class
 * Simulates incoming messages by reading from a directory './log) containing .eml files
 * and processes them to check the decoding of the messages
 *
 */
class TestEmailProcessor extends EmailProcessor
{
	public function ListEmailSources()
	{
//		return array( 0 => new IMAPEmailSource('ssl0.ovh.net', 993, 'tickets@combodo.com', 'c8mb0do', '', array('imap', 'ssl', 'novalidate-cert')));
		return array( 0 => new TestEmailSource(dirname(__FILE__).'/log', 'test'));
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
			$sMessage .= "\n=====================================\nERROR: Empty subject for the message.\n";
		}
		if (empty($oEmail->sBodyText))
		{
			$sMessage .= "\n=====================================\nERROR: Empty body for the message.\n";
		}
		else
		{
			$sMessage .= "\n=====================================\nFormat:{$oEmail->sBodyFormat} \n{$oEmail->sBodyText}\n============================================.\n";
		}
		$index = 0;
		foreach($oEmail->aAttachments as $aAttachment)
		{
			$sMessage .= "\n\tAttachment #$index\n";
			if (empty($aAttachment['mimeType']))
			{
				$sMessage .= "\n=====================================\nERROR: Empty mimeType for attachment #$index of the message.\n";
			}
			else
			{
				$sMessage .= "\t\tType: {$aAttachment['mimeType']}\n";
			}
			if (empty($aAttachment['filename']))
			{
				$sMessage .= "\n=====================================\nERROR: Empty filename for attachment #$index of the message.\n";
			}
			else
			{
				$sMessage .= "\t\tName: {$aAttachment['filename']}\n";
			}
			if (empty($aAttachment['content']))
			{
				$sMessage .= "\n=====================================\nERROR: Empty CONTENT for attachment #$index of the message.\n";
			}
			else
			{
				$sMessage .= "\t\tContent: ".strlen($aAttachment['content'])." bytes\n";
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
}

/////////////////////////////////////////////////////////////////////////////
/**
 * Processes messages coming from email sources corresponding to instances
 * of MailInbox (and derived) classes. 1 instance = 1 email source
 *
 */
class MailInboxesEmailProcessor extends EmailProcessor
{
	protected static $bDebug;
	protected static $aExcludeAttachments;
	protected static $sBodyPartsOrder;
	protected static $sModuleName;
	protected $aInboxes;

	
	/**
	 * Construct a new EmailProcessor... some initialization, reading configuration parameters
	 */
	public function __construct()
	{
		self::$sModuleName = 'combodo-email-synchro';
		self::$bDebug = MetaModel::GetModuleSetting(self::$sModuleName, 'debug', false);
		self::$aExcludeAttachments = MetaModel::GetModuleSetting(self::$sModuleName, 'exclude_attachment_types', array());
		self::$sBodyPartsOrder = MetaModel::GetModuleSetting(self::$sModuleName, 'body_parts_order', 'text/html,text/plain');
		$this->aInboxes = array();
		
		EmailBackgroundProcess::SetMultiSourceMode(true); // make sure that we can support several email source with potentially overlapping UIDLs
	}
	
	/**
	 * Outputs some debug text if debugging is enabled from the configuration
	 * @param string $sText The text to output
	 * @return void
	 */
	public static function Trace($sText)
	{
		if (self::$bDebug)
		{
			echo $sText."\n";
		}
	}
	/**
	 * Initializes the email sources: one source is created and associated with each MailInboxBase instance
	 * @param void
	 * @return array An array of EmailSource objects
	 */
	public function ListEmailSources()
	{		
		$aSources = array();
		$oSearch = new DBObjectSearch('MailInboxBase');
		$oSearch->AddCondition('active', 'yes');
		$oSet = new DBObjectSet($oSearch);
		while($oInbox = $oSet->Fetch())
		{
			$this->aInboxes[$oInbox->GetKey()] = $oInbox;
			try
			{
				$oSource = $oInbox->GetEmailSource();
				$oSource->SetToken($oInbox->GetKey()); // to match the source and the inbox later on
				$oSource->SetPartsOrder(self::$sBodyPartsOrder); // in which order to decode the message's body
				$aSources[] = $oSource;
			}
			catch(Exception $e)
			{
				// Don't use Trace, always output the error so that the log file can be monitored for errors
				echo "Error - Failed to initialize the mailbox: ".$oInbox->GetName().", the mailbox will not be polled. Reason (".$e->getMessage().")";
			}
		}

		return $aSources;
	}
	
	/**
	 * Retrieves the MailInbox instance associated with the given EmailSource object
	 * @param EmailSource $oSource The EmailSource in which the messages are read
	 * @return MailInboxBase The instance associated with the source
	 * @throws Exception
	 */
	protected function GetInboxFromSource(EmailSource $oSource)
	{
		$iId = $oSource->GetToken();
		if (!array_key_exists($iId, $this->aInboxes))
		{
			self::Trace("Unknown MailInbox (id=$iId) for EmailSource '".$oSource->GetName()."'");
			throw new Exception("Unknown MailInbox (id=$iId) for EmailSource '".$oSource->GetName()."'");
		}
		return $this->aInboxes[$iId];
	}
	
	/**
	 * Returns a text message corresponding to the given action code
	 * @param int $iRetCode The action code from EmailProcessor
	 * @return string The textual code of the action
	 */
	protected function GetMessageFromCode($iRetCode)
	{
		$sRetCode = 'Unknown Code '.$iRetCode;
		switch($iRetCode)
		{
			case EmailProcessor::NO_ACTION:
			$sRetCode = 'NO_ACTION';
			break;
			
			case EmailProcessor::DELETE_MESSAGE;
			$sRetCode = 'DELETE_MESSAGE';
			break;
			
			case EmailProcessor::PROCESS_MESSAGE:
			$sRetCode = 'PROCESS_MESSAGE';
			break;
			
			case EmailProcessor::PROCESS_ERROR:
			$sRetCode = 'PROCESS_ERROR';
			break;
			
		}
		return $sRetCode;		
	}
	
	/**
	 * Decides whether a message should be downloaded and processed, deleted, or simply ignored
	 * (i.e left as-is in the mailbox)
	 */
	public function DispatchMessage(EmailSource $oSource, $index, $sUIDL, $oEmailReplica = null)
	{
		self::Trace("Combodo Email Synchro: dispatch of the message $index ($sUIDL)");

		$oInbox = $this->GetInboxFromSource($oSource);
		$iRetCode = $oInbox->DispatchEmail($oEmailReplica);
		$sRetCode = $this->GetMessageFromCode($iRetCode);

		self::Trace("Combodo Email Synchro: dispatch of the message $index ($sUIDL) returned $iRetCode ($sRetCode)");
		return $iRetCode;
	}

	/**
	 * Process the email downloaded from the mailbox.
	 * This implementation delegates the processing the MailInbox instances
	 * The caller (identified by its email) must already exists in the database
	 * @param EmailSource $oSource The source from which the email was read
	 * @param integer $index The index of the message in the mailbox
	 * @param EmailMessage $oEmail The downloaded/decoded email message
	 * @param EmailReplica $oEmailReplica The information associating a ticket to the email. Null for new emails
	 */
	public function ProcessMessage(EmailSource $oSource, $index, EmailMessage $oEmail, $oEmailReplica = null)
	{
		$oInbox = $this->GetInboxFromSource($oSource);
		self::Trace("Combodo Email Synchro: Processing message $index ({$oEmail->sUIDL})");
		if ($oEmailReplica == null)
		{
			$oTicket = $oInbox->ProcessNewEmail($oSource, $index, $oEmail);		
			
			if (is_object($oTicket))
			{
				// Create a replica to keep track that we've processed this email
				$oEmailReplica = new EmailReplica();
				if (EmailBackgroundProcess::IsMultiSourceMode())
				{
					
					$oEmailReplica->Set('uidl', $oSource->GetName().'_'.$oEmail->sUIDL);
				}
				else
				{
					$oEmailReplica->Set('uidl', $oEmail->sUIDL);	
				}
				$oEmailReplica->Set('message_id', $oEmail->sMessageId);
				$oEmailReplica->Set('ticket_id', $oTicket->GetKey());
				$oEmailReplica->DBInsert();
			}
			else
			{
				// Error ???
				self::Trace("Combodo Email Synchro: Failed to create a ticket for the incoming email $index ({$oEmail->sUIDL})");
			}	
		}
		else
		{
			$oInbox->ReprocessOldEmail($oSource, $index, $oEmail, $oEmailReplica);		
		}
		$iRetCode = $oInbox->GetNextAction();
		$sRetCode = $this->GetMessageFromCode($iRetCode);
		self::Trace("Combodo Email Synchro: End of processing of the new message $index ({$oEmail->sUIDL}) retCode: ".$sRetCode);

		return $iRetCode;
	}
	
	/**
	 * Called, before deleting the message from the source when the decoding fails
	 */
	public function OnDecodeError(EmailSource $oSource, $sUIDL, EmailMessage $oEmail, RawEmailMessage $oRawEmail)
	{
		$oInbox = $this->GetInboxFromSource($oSource);
		self::Trace("Combodo Email Synchro: failed to decode the message ($sUIDL})");
		$oInbox->HandleError($oEmail, 'decode_failed', $oRawEmail);
		// message will be deleted from the source
	}
}
