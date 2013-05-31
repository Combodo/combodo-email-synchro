<?php
/////////////////////////////////////////////////////////////////////////////

/**
 * Default object for parametrization of the Ticket creation from eMails within
 * the standard iTop user interface.
 * Derive from this class to implement MailInboxes with a specific processing of
 * the messages, and also a additional configuration options linked with this behavior.
 *
 * @package     combodo-email-synchro
 */
abstract class MailInboxBase extends cmdbAbstractObject
{
	protected static $aExcludeAttachments = null; // list of attachment types (MimeTypes) that should not be attached to a ticket
	protected $iNextAction;
	
	public static function Init()
	{
		$aParams = array
		(
			"category" => "core/cmdb",
			"key_type" => "autoincrement",
			"name_attcode" => array("login"),
			"state_attcode" => "",
			"reconc_keys" => array('server', 'login', 'protocol', 'mailbox', 'port'),
			"db_table" => "mailinbox_base",
			"db_key_field" => "id",
			"db_finalclass_field" => "realclass",
			"display_template" => "",
			'icon' => utils::GetAbsoluteUrlModulesRoot().basename(dirname(__FILE__)).'/images/mailbox.png',
		);
		MetaModel::Init_Params($aParams);
		//MetaModel::Init_InheritAttributes();
		MetaModel::Init_AddAttribute(new AttributeString("server", array("allowed_values"=>null, "sql"=>"server", "default_value"=>null, "is_null_allowed"=>false, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeString("mailbox", array("allowed_values"=>null, "sql"=>"mailbox", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeString("login", array("allowed_values"=>null, "sql"=>"login", "default_value"=>null, "is_null_allowed"=>false, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributePassword("password", array("allowed_values"=>null, "sql"=>"password", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeEnum("protocol", array("allowed_values"=>new ValueSetEnum('pop3,imap'), "sql"=>"protocol", "default_value"=>'pop3', "is_null_allowed"=>false, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeInteger("port", array("allowed_values"=>null, "sql"=>"port", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeEnum("active", array("allowed_values"=>new ValueSetEnum('yes,no'), "sql"=>"active", "default_value"=>'yes', "is_null_allowed"=>false, "depends_on"=>array())));
		
		// Display lists
		MetaModel::Init_SetZListItems('details', array('server', 'mailbox', 'login', 'password', 'protocol', 'port')); // Attributes to be displayed for the complete details
		MetaModel::Init_SetZListItems('list', array('server', 'mailbox','protocol')); // Attributes to be displayed for a list
	}
	
	/**
	 * Add an extra tab showing the content of the mailbox...
	 * @see cmdbAbstractObject::DisplayBareRelations()
	 */
	function DisplayBareRelations(WebPage $oPage, $bEditMode = false)
	{
		parent::DisplayBareRelations($oPage, $bEditMode);
		if (!$bEditMode)
		{
			$oPage->SetCurrentTab(Dict::S('MailInbox:MailboxContent'));
			$oPage->add('<p><button type="button" id="mailbox_content_refresh">'.Dict::S(Dict::S('UI:Button:Refresh')).'</button></p>');
			$oPage->add('<div id="mailbox_content_output"></div>');
			$sAjaxUrl = addslashes(utils::GetAbsoluteUrlModulesRoot().basename(dirname(__FILE__)).'/ajax.php');
			$iId = $this->GetKey();
			$oPage->add_ready_script(
<<<EOF
$('#mailbox_content_refresh').click(function() {
	$('#mailbox_content_output').html('<img src="../images/indicator.gif"/>');
	$.post('$sAjaxUrl', {operation: 'mailbox_content', id: $iId }, function(data) {
		$('#mailbox_content_output').html(data);
		$("#mailbox_content_output .listResults").tablesorter( { widgets: ['myZebra']} ); // sortable and zebra tables
	});
});
$('#mailbox_content_refresh').trigger('click');
EOF
			);
		}
	}

	/**
	 * Overload the standard behavior to guarantee the unicity of either:
	 * login / server (pop3) or
	 * login / server / mailbox (imap)
	 * @see cmdbAbstractObject::DoCheckToWrite()
	 */
	public function DoCheckToWrite()
	{
		parent::DoCheckToWrite();

		// Note: This MUST be factorized later: declare unique keys (set of columns) in the data model
		$aChanges = $this->ListChanges();
		if (array_key_exists('login', $aChanges) || array_key_exists('server', $aChanges) || array_key_exists('mailbox', $aChanges) || array_key_exists('protocol', $aChanges))
		{
			$sNewLogin = $this->Get('login');
			$sNewServer = $this->Get('server');
			$sNewMailbox = $this->Get('mailbox');
			if ($this->Get('protocol') == 'pop3')
			{
				// Ignore the mailbox
				$sNewMailbox = '';
			}
			$oSearch = DBObjectSearch::FromOQL_AllData("SELECT MailInboxBase WHERE login = :newlogin AND server = :newserver AND ((protocol = 'imap' AND mailbox = :newmailbox) OR (protocol = 'pop3' AND '' = :newmailbox)) AND id != :id");
			$oSet = new DBObjectSet($oSearch, array(), array('id' => $this->GetKey(), 'newlogin' => $sNewLogin, 'newserver' => $sNewServer, 'newmailbox' => $sNewMailbox));
			if ($oSet->Count() > 0)
			{
				if ($this->Get('protocol') == 'pop3')
				{
					$this->m_aCheckIssues[] = Dict::Format('MailInbox:Login/ServerMustBeUnique', $sNewLogin, $sNewServer);
				}
				else
				{
					$this->m_aCheckIssues[] = Dict::Format('MailInbox:Login/Server/MailboxMustBeUnique', $sNewLogin, $sNewServer, $sNewMailbox);
				}
			}
		}		
	}	

	protected function SetNextAction($iNextAction)
	{
		return $this->iNextAction = $iNextAction;
	}
	
	public function GetNextAction()
	{
		return $this->iNextAction;
	}

	/**
	 * Initial dispatching of an incoming email: determines what to do with the email
	 * @param EmailReplica $oEmailReplica The EmailReplica associated with the email. null for a new (unread) mail
	 * @return int An action code from EmailProcessor
	 */
	public function DispatchEmail($oEmailReplica = null)
	{
		$this->SetNextAction(EmailProcessor::NO_ACTION);
		if ($oEmailReplica == null)
		{
			// New (unread) message, let's process it
			$this->SetNextAction(EmailProcessor::PROCESS_MESSAGE);
		}
		else
		{
			$iTicketId = $oEmailReplica->Get('ticket_id');
			$oTicket = MetaModel::GetObject('Ticket', $iTicketId, false /* => may not exist anymore */);
			if (is_object($oTicket))
			{
				if ($oTicket->Get('status') == 'closed')
				{
					// The corresponding ticket was closed, delete the email (and the replica)
					$this->SetNextAction(EmailProcessor::DELETE_MESSAGE);
				}
			}
			else
			{
				// The corresponding ticket was deleted, delete the email (and the replica)
				$this->SetNextAction(EmailProcessor::DELETE_MESSAGE);
			}
		}
		return $this->GetNextAction();
	}
	
	/**
	 * Process an new (unread) incoming email
	 * @param EmailSource $oSource The source from which this email was read
	 * @param int $index The index of the message in the source
	 * @param EmailMessage $oEmail The decoded email
	 * @return Ticket The ticket created or updated in response to the email
	 */
	public function ProcessNewEmail(EmailSource $oSource, $index, EmailMessage $oEmail)
	{
		$oTicket = null;
		$this->SetNextAction(EmailProcessor::NO_ACTION);
		$sContactQuery = 'SELECT Contact WHERE email = :email';
		$oSet = new DBObjectSet(DBObjectSearch::FromOQL($sContactQuery), array(), array('email' => $oEmail->sCallerEmail));
		$sAdditionalDescription = '';
		switch($oSet->Count())
		{
			case 1:
			// Ok, the caller was found in iTop
			$oCaller = $oSet->Fetch();
			break;
			
			case 0:
			// Here decide what to do ? Create a new user, assign the ticket to a 'fixed' Unknown Caller, reject the ticket...
			// For now: let's do nothing, just ignore the ticket, it will be processed again later... in case the caller gets created
			$this->Trace('No contact found for the email address ('.$oEmail->sCallerEmail.'), the ticket will NOT be created');
			break;
			
			default:
			$this->Trace('Found '.$oSet->Count().' callers with the same email address ('.$oEmail->sCallerEmail.'), the first one will be used...');
			// Multiple callers with the same email address !!!
		 	$sAdditionalDescription = "Warning:\nThere are ".$oSet->Count()." callers with the same email address (".$oEmail->sCallerEmail.") ";
			$sAdditionalDescription = ".\nThe ticket was assigned to the first one found (at random).\n\n";
			$oCaller = $oSet->Fetch();
		}
		
		// Check whether we need to create a new ticket or to update an existing one
		// First check if there are any iTop object mentioned in the headers of the eMail
		$oTicket = $oEmail->oRelatedObject;
		
		if (($oTicket != null) && !($oTicket instanceof Ticket))
		{
			// The object referenced by the email is not a ticket !!
			// => Forward the message and delete the ticket ??
			$this->Trace("iTop Simple Email Synchro: WARNING the message $index ({$oEmail->sUIDL}) contains a reference to a valid iTop object that is NOT a ticket !");
			$oTicket = null;
		}
		
		if ($oTicket == null)
		{
			// No associated ticket found by parsing the headers, check
			// if the subject does not match a specific pattern
			if(preg_match($this->sTitlePattern, $oEmail->sSubject, $aMatches))
			{
				$iTicketId = 0;
				sscanf($aMatches[1], '%d', $iTicketId);
				$this->Trace("iTop Simple Email Synchro: Retrieving ticket ".$iTicketId." (match by subject pattern)...");
				$oTicket = MetaModel::GetObject('Ticket', $iTicketId, false);
			}
		}
		
		if ($this->bCreateOnly || !is_object($oTicket))
		{
			// No ticket associated with the incoming email, let's create a new ticket
			$oTicket = $this->CreateTicketFromEmail($oEmail, $oCaller);
		}
		else
		{
			// Update the ticket with the incoming eMail
			$this->UpdateTicketFromEmail($oTicket, $oEmail, $oCaller);
		}
		
		return $oTicket;
	}
	
	
	/**
	 * If DispatchMessage tells to reprocess an email, this method is called
	 * @param EmailSource $oSource The source from which this email was read
	 * @param int $index The index of the message in the source
	 * @param EmailMessage $oEmail The decoded email
	 * @param EmailReplica $oEmailReplica The replica associated with this email
	 * @return void
	 */
	public function ReprocessOldEmail(EmailSource $oSource, $index, EmailMessage $oEmail, EmailReplica $oEmailReplica)
	{
		// Should not be called in this implementation, does nothing
		$this->SetNextAction(EmailProcessor::NO_ACTION);
	}
	
	/**
	 * Actual creation of the ticket from the incoming email. Overload this method
	 * to implement your own behavior, if needed
	 * @param EmailMessage $oEmail The decoded incoming email
	 * @param Contact $oCaller The contact corresponding to the "From" email address
	 * @return Ticket the created ticket or null in case of failure
	 */
	public function CreateTicketFromEmail(EmailMessage $oEmail, Contact $oCaller)
	{
		$oTicket = null;
		return $oTicket;
	}
	
	
	/**
	 * Actual update of a ticket from the incoming email. Overload this method
	 * to implement your own behavior, if needed
	 * @param Ticket $oTicket The ticket to update
	 * @param EmailMessage $oEmail The decoded incoming email
	 * @param Contact $oCaller The contact corresponding to the "From" email address
	 * @return void
	 */
	public function UpdateTicketFromEmail(Ticket $oTicket, EmailMessage $oEmail, Contact $oCaller)
	{
		
	}
	
	/**
	 * Error handler... what to do in case of error ??
	 * @param EmailMessage $oEmail
	 * @param string $sErrorCode
	 * @param RawEmailMessage $oRawEmail In case decoding failed or null
	 * @return int Next action: action code of the next action to execute
	 */
	public function HandleError(EmailMessage $oEmail, $sErrorCode, $oRawEmail = null)
	{
		$this->SetNextAction(EmailProcessor::NO_ACTION); // Ignore faulty emails
	}
	
	/**
	 * 
	 * Add the eMail's attachments to the given Ticket. Avoid duplicates (based on name/size/md5),
	 * avoid attaching blacklisted file types, and can potentially call an AntiVirus before adding the attachment
	 * @param Ticket $oTicket
	 * @param EmailMessage $oEmail
	 * @param CMDBChange $oMyChange The current change used to record the modifications (for iTop 1.x compatibility)
	 * @param bool $bNoDuplicates If true, don't add attachment that seem already attached to the ticket (same type, same name, same size, same md5 checksum)
	 * @return void
	 */
	protected function AddAttachments(Ticket $oTicket, EmailMessage $oEmail, CMDBChange $oMyChange, $bNoDuplicates = true)
	{
		// Process attachments (if any)
		$aPreviousAttachments = array();
		if ($bNoDuplicates)
		{
			$sOQL = "SELECT Attachment WHERE item_class = :class AND item_id = :id";
			$oAttachments = new DBObjectSet(DBObjectSearch::FromOQL($sOQL), array(), array('class' => get_class($oTicket), 'id' => $oTicket->GetKey()));
			while($oPrevAttachment = $oAttachments->Fetch())
			{
				$oDoc = $oPrevAttachment->Get('contents');
				$data = $oDoc->GetData();
				$aPreviousAttachments[] = array(
					'filename' => $oDoc->GetFileName(),
					'mimeType' => $oDoc->GetMimeType(),
					'size' => strlen($data),
					'md5' => md5($data),
				);
			}
		}
		foreach($oEmail->aAttachments as $aAttachment)
		{
			$bIgnoreAttachment =false;
			// First check if the type is allowed as an attachment...
			if (self::$aExcludeAttachments == null)
			{
				self::$aExcludeAttachments = MetaModel::GetModuleSetting('combodo-email-synchro', 'exclude_attachment_types', array());
			}
			if (!in_array($aAttachment['mimeType'], self::$aExcludeAttachments))
			{
				if ($bNoDuplicates)
				{
					// Check if an attachment with the same name/type/size/md5 already exists
					$iSize = strlen($aAttachment['content']);
					$sMd5 = md5($aAttachment['content']);
					foreach($aPreviousAttachments as $aPrevious)
					{
						if (($aAttachment['filename'] == $aPrevious['filename']) &&
						    ($aAttachment['mimeType'] == $aPrevious['mimeType']) &&
						    ($iSize == $aPrevious['size']) &&
						    ($sMd5 == $aPrevious['md5']) )
						{
							// Skip this attachment
							MailInboxesEmailProcessor::Trace("Info: Attachment {$aAttachment['filename']} skipped, already attached to the ticket.");
							$bIgnoreAttachment = true;
							break;
						}
						
						// Remember this attachment to avoid adding it twice (in case it is contained two times in the message)
						$aPreviousAttachments[] = array(
							'filename' => $aAttachment['filename'],
							'mimeType' => $aAttachment['mimeType'],
							'size' => $iSize,
							'md5' => $sMd5,
						);
					}
				}
				if ($this->ContainsViruses($aAttachment))
				{
					// Skip this attachment
					MailInboxesEmailProcessor::Trace("Info: Attachment {$aAttachment['filename']} is reported as containing a virus, skipped.");
					$bIgnoreAttachment = true;
				}
				if (!$bIgnoreAttachment)
				{
					$oAttachment = new Attachment;
					$oAttachment->Set('item_class', get_class($oTicket));
					$oAttachment->Set('item_id', $oTicket->GetKey());
					$oBlob = new ormDocument($aAttachment['content'], $aAttachment['mimeType'], $aAttachment['filename']);
					$oAttachment->Set('contents', $oBlob);
					$oAttachment->DBInsert();
					$oMyChangeOp = MetaModel::NewObject("CMDBChangeOpPlugin");
					$oMyChangeOp->Set("change", $oMyChange->GetKey());
					$oMyChangeOp->Set("objclass", get_class($oTicket));
					$oMyChangeOp->Set("objkey", $oTicket->GetKey());
					$oMyChangeOp->Set("description", Dict::Format('Attachments:History_File_Added', $aAttachment['filename']));
					$iId = $oMyChangeOp->DBInsertNoReload();
					MailInboxesEmailProcessor::Trace("Info: Attachment {$aAttachment['filename']} added to the ticket.");
				}
			}
			else
			{
				MailInboxesEmailProcessor::Trace("Info: The attachment {$aAttachment['filename']} was NOT added to the ticket because its type '{$aAttachment['mimeType']}' is excluded according to the configuration");
			}
		}
	}
	
	/**
	 * Check if the supplied attachment contains a virus: implement you own methods based on your antivirus...
	 * The following (inactive) code is just provided as an example
	 * @param hash $aAttachment
	 * @return bool True if the attachment contains a virus (and should be attached to the ticket), false otherwise
	 */
	protected function ContainsViruses($aAttachment)
	{
		// Not implemented, depends on your antivirus solution...
		$bResult = false;
		
		/*
		// Below is an untested example of such a check, using Clam AntiVirus and the php-clamv extension
		// (http://www.clamav.net/lang/en/ and http://php-clamav.sourceforge.net/)
		if (function_exists('cl_scanfile'))
		{
			// Save the attachment to a temporary file
			require_once(APPROOT.'setup/setuputils.class.inc.php');
			$sTempFile = tempnam(SetupUtils::GetTmpDir(), 'clamav-');
			@file_put_contents($sTempFile, $aAttachment['data']);
			
			// Scan the file
			$retcode = cl_scanfile($sTempFile, $sVirusName);
			if ($retcode == CL_VIRUS)
			{
				MailInboxesEmailProcessor::Trace("Virus '$sVirusName' found in the attachment {$aAttachment['filename']}");
				$bResult = true;
			}
			
			// Remove the temporary file
			unlink($sTempFile);
		}
		*/
		return $bResult;
	}

	/**
	 * Debug trace: activated/disabled by the configuration flag set for the base module...
	 * @param string $sText
	 */
	protected function Trace($sText)
	{
		MailInboxesEmailProcessor::Trace($sText);
	}
	
	/**
	 * Initializes an object from default values
	 * Each default value must be a valid value for the given field
	 * @param DBObject $oObj The object to update
	 * @param hash $aValues The values to set attcode => value
	 */
	protected function InitObjectFromDefaultValues($oObj, $aValues)
	{
		foreach($aValues as $sAttCode => $value)
		{
			if (!MetaModel::IsValidAttCode(get_class($oObj), $sAttCode))
		 	{
	 			$this->Trace("Warning: cannot set default value '$value'; '$sAttCode' is not a valid attribute of the class ".get_class($oObj).".");		 		
		 	}
		 	else
		 	{
			 	$oAttDef = MetaModel::GetAttributeDef(get_class($oObj), $sAttCode);
			 	if (!$oAttDef->IsWritable())
			 	{
			 		$this->Trace("Warning: cannot set default value '$value' for the non-writable attribute: '$sAttCode'.");		 		
			 	}
			 	else
			 	{
					$aArgs = array('this' => $oObj->ToArgs());
			 		$aValues = $oAttDef->GetAllowedValues($aArgs);
			 		if ($aValues == null)
			 		{
			 			// No special constraint for this attribute
				 		if ($oAttDef->IsExternalKey())
				 		{
				 			$oTarget = MetaModel::GetObjectByName($oAttDef->GetTargetClass(), $value, false);
				 			if (is_object($oTarget))
				 			{
				 				$oObj->Set($sAttCode, $oTarget->GetKey());
				 			}
				 			else
				 			{
					 			$this->Trace("Warning: cannot set default value '$value' for the external key: '$sAttCode'. Unable to find an object of class ".$oAttDef->GetTargetClass()." named '$value'.");
				 			}
				 		}
				 		else if($oAttDef->IsScalar())
				 		{
				 			$oObj->Set($sAttCode, $value);
				 		}
				 		else
				 		{
				 			$this->Trace("Warning: cannot set default value '$value' for the non-scalar attribute: '$sAttCode'.");
				 		}
			 		}
			 		else
			 		{
			 			// Check that the specified value is a possible/allowed value
				 		if ($oAttDef->IsExternalKey())
				 		{
				 			$bFound = false;
				 			$iIntVal = (int)$value;
				 			$bByKey = false;
				 			if (is_numeric($value) && ($iIntVal == $value))
				 			{
				 				// A numeric value is supposed to be the object's key
				 				$bByKey = true;
				 			}
				 			foreach($aValues as $id => $sName)
				 			{
								if ($bByKey)
								{
									if ($id === $iIntVal)
									{
					 					$bFound = true;
					 					$oObj->Set($sAttCode, $id);
					 					break;										
									}
								}
				 				else
				 				{
					 				if (strcasecmp($sName,$value) == 0)
					 				{
					 					$bFound = true;
					 					$oObj->Set($sAttCode, $id);
					 					break;
					 				}
				 				}
				 			}
				 		}
				 		else if($oAttDef->IsScalar())
				 		{
				 			foreach($aValues as $allowedValue)
				 			{
				 				if ($allowedValue == $value)
				 				{
				 					$bFound = true;
				 					$oObj->Set($sAttCode, $value);
				 					break;
				 				}
				 			}
				 		}
				 		else
				 		{
				 			$bFound = true;
				 			$this->Trace("Warning: cannot set default value '$value' for the non-scalar attribute: '$sAttCode'.");
				 		}
				 		
				 		if (!$bFound)
				 		{
				 			$this->Trace("Warning: cannot set the value '$value' for the field $sAttCode of the ticket. '$value' is not a valid value for $sAttCode.");		
				 		}
			 		}
				}
			}
		}
	}
	
	/**
	 * Get an EmailSource instance initialized according to the MailInbox configuration
	 * @throws Exception
	 * @return EmailSource The initialized EmailSource or an exception if the conneciton fails
	 */
	public function GetEmailSource()
	{
		$sProtocol = $this->Get('protocol');
		$sServer = $this->Get('server');
		$sPwd = $this->Get('password');
		$sLogin = $this->Get('login');
		$sMailbox = $this->Get('mailbox');
		$iPort = $this->Get('port');
		
		switch($sProtocol)
		{
			case 'imap':
			$aImapOptions = MetaModel::GetModuleSetting('combodo-email-synchro', 'imap_options', array('pop3'));
			self::Trace("Protocol: $sProtocol Mail server: $sServer, port: $iPort, login: $sLogin, password: $sPwd, mailbox: $sMailbox, options: /".implode('/', $aImapOptions));
			$oSource = new IMAPEmailSource($sServer, $iPort, $sLogin, $sPwd, $sMailbox, $aImapOptions);
			break;

			case 'pop3':
			$sPop3AuthOption = MetaModel::GetModuleSetting('combodo-email-synchro', 'pop3_auth_option', 'USER');
			self::Trace("Protocol: $sProtocol Mail server: $sServer, port: $iPort, login: $sLogin, password: $sPwd, auth_option: $sPop3AuthOption");
			$oSource = new POP3EmailSource($sServer, $iPort, $sLogin, $sPwd, $sPop3AuthOption);
			break;
			
			default:
			self::Trace("Error: unsupported protocol: $sProtocol - please use one of: pop3, imap.");	
		}
		return $oSource;
	}
}