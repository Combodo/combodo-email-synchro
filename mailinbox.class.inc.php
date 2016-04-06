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
	protected static $iMinImageWidth = null;
	protected static $iMinImageHeight = null;
	protected static $iMaxImageWidth = null;
	protected static $iMaxImageHeight = null;
	protected $iNextAction;
	protected $iMaxAttachmentSize;
	protected $sBigFilesDir;
	public $sLastError;
	
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
	
	public function __construct($aRow = null, $sClassAlias = '', $aAttToLoad = null, $aExtendedDataSpec = null)
	{
		parent::__construct($aRow, $sClassAlias, $aAttToLoad, $aExtendedDataSpec);
		$aData = CMDBSource::QueryToArray('SELECT @@global.max_allowed_packet');
		$this->iMaxAttachmentSize = (int)$aData[0]['@@global.max_allowed_packet'] - 500; // Keep some room for the rest of the SQL query
		$this->sBigFilesDir = MetaModel::GetModuleSetting('combodo-email-synchro', 'big_files_dir', '');
		$this->sLastError = '';
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
			$sForm = Dict::Format('MailInbox:Display_X_eMailsStartingFrom_Y', '<input type="text" size="3" id="mailbox_count" value="10"/>', '<input type="text" size="3" id="mailbox_start_index" value="0"/>');
			$oPage->add('<p><form onsubmit="return false;">'.$sForm.'&nbsp;<button type="submit" id="mailbox_content_refresh">'.Dict::S(Dict::S('UI:Button:Refresh')).'</button></form></p>');
			$oPage->add('<div id="mailbox_content_output"></div>');
			$sAjaxUrl = addslashes(utils::GetAbsoluteUrlModulesRoot().basename(dirname(__FILE__)).'/ajax.php');
			$iId = $this->GetKey();
			$oPage->add_script(
<<<EOF
function MailboxUpdateActionButtons()
{
	if( $(".mailbox_item:checked").length > 0 )
	{
		$('.mailbox_button').prop('disabled', false);
	}
	else
	{
		$('.mailbox_button').prop('disabled', true);
	}	
}
					
function MailboxRefresh(data)
{
	$('#mailbox_content_output').html(data);
	$('#mailbox_content_refresh').removeAttr('disabled');
	$("#mailbox_content_output .listResults").tablesorter( { headers: { 0: {sorter: false}}, widgets: ['myZebra']} ); // sortable and zebra tables
	$("#mailbox_checkall").click(function() {
		var bChecked = $(this).prop('checked');
		$(".mailbox_item").each(function() {
			$(this).prop('checked', bChecked);
		});
		MailboxUpdateActionButtons();
	});
	$('.mailbox_button').prop('disabled', false);
	$(".mailbox_item").bind('change', function() {
		MailboxUpdateActionButtons();
	});
	$('#mailbox_reset_status').click(function() {
		MailboxResetStatus();
	});
	$('#mailbox_delete_messages').click(function() {
		MailboxDeleteMessages();
	});
	MailboxUpdateActionButtons();
}

function MailboxResetStatus()
{
	var aUIDLs = [];
	$(".mailbox_item:checked").each(function() {
		aUIDLs.push(this.value);
	});
					
	$('#mailbox_content_output').html('<img src="../images/indicator.gif"/>');
	$('#mailbox_content_refresh').attr('disabled', 'disabled');
	var iStart = $('#mailbox_start_index').val();
	var iCount = $('#mailbox_count').val();
					
	$.post('$sAjaxUrl', {operation: 'mailbox_reset_status', id: $iId, start: iStart, count: iCount, aUIDLs: aUIDLs }, function(data) {
		 MailboxRefresh(data);
	});
	return false;
}

function MailboxDeleteMessages()
{
	var aUIDLs = [];
	$(".mailbox_item:checked").each(function() {
		aUIDLs.push(this.value);
	});
					
	$('#mailbox_content_output').html('<img src="../images/indicator.gif"/>');
	$('#mailbox_content_refresh').attr('disabled', 'disabled');
	var iStart = $('#mailbox_start_index').val();
	var iCount = $('#mailbox_count').val();
					
	$.post('$sAjaxUrl', {operation: 'mailbox_delete_messages', id: $iId, start: iStart, count: iCount, aUIDLs: aUIDLs }, function(data) {
		 MailboxRefresh(data);
	});
	return false;	
}
EOF
			);
			$oPage->add_ready_script(
<<<EOF
$('#mailbox_content_refresh').click(function() {
					
	$('#mailbox_content_output').html('<img src="../images/indicator.gif"/>');
	$('#mailbox_content_refresh').attr('disabled', 'disabled');
	var iStart = $('#mailbox_start_index').val();
	var iCount = $('#mailbox_count').val();
					
	$.post('$sAjaxUrl', {operation: 'mailbox_content', id: $iId, start: iStart, count: iCount }, function(data) {
		MailboxRefresh(data);
	});
					
	return false;
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
	 * @param EmailReplica $oEmailReplica The EmailReplica associated with the email. A new replica (i.e. not yet in DB) one for new emails
	 * @return int An action code from EmailProcessor
	 */
	public function DispatchEmail(EmailReplica $oEmailReplica)
	{
		$this->SetNextAction(EmailProcessor::NO_ACTION);
		if ($oEmailReplica->IsNew())
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
			$this->sLastError = 'No contact found for the email address ('.$oEmail->sCallerEmail.')';
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
	 * @param EmailMessage $oEmail can be null in case of decoding error (like message too big)
	 * @param string $sErrorCode
	 * @param RawEmailMessage $oRawEmail In case decoding failed or null
	 * @param string $sAdditionalErrorMessage More information about the error (optional)
	 * @return int Next action: action code of the next action to execute
	 */
	public function HandleError($oEmail, $sErrorCode, $oRawEmail = null, $sAdditionalErrorMessage = '')
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
	 * @return an array of cid => attachment_id
	 */
	protected function AddAttachments(Ticket $oTicket, EmailMessage $oEmail, CMDBChange $oMyChange, $bNoDuplicates = true, &$aIgnoredAttachments = array())
	{
		if (self::$iMinImageWidth === null)
		{
			$sMinImagesSize = MetaModel::GetModuleSetting('combodo-email-synchro', 'images_minimum_size','0x0');
			if (preg_match('/^([0-9]+)x([0-9]+)$/i', $sMinImagesSize, $aMatches))
			{
				self::$iMinImageWidth = (int)$aMatches[1];
				self::$iMinImageHeight = (int)$aMatches[2];
				MailInboxesEmailProcessor::Trace("Info: minimum dimensions for attachment images: ".self::$iMinImageWidth."x".self::$iMinImageHeight." px. Images smaller than these dimensions will be ignored.");
			}
			else
			{
				MailInboxesEmailProcessor::Trace("Warning: incorrect format for the configuration value: 'images_minimum_size'. Expecting a value dddxddd (where ddd are digits), like 100x100, but got: '$sMinImagesSize'. No minimum value will be set.");
			}
		}
		if (self::$iMaxImageWidth === null)
		{
			if (function_exists('imagecopyresampled'))
			{
				$sMaxImagesSize = MetaModel::GetModuleSetting('combodo-email-synchro', 'images_maximum_size', '');
				if ($sMaxImagesSize != '')
				{
					if (preg_match('/^([0-9]+)x([0-9]+)$/i', $sMaxImagesSize, $aMatches))
					{
						self::$iMaxImageWidth = (int)$aMatches[1];
						self::$iMaxImageHeight = (int)$aMatches[2];
						MailInboxesEmailProcessor::Trace("Info: maximum dimensions for attachment images: ".self::$iMaxImageWidth."x".self::$iMaxImageHeight." px. Images bigger than these dimensions will be resized.");
					}
					else
					{
						MailInboxesEmailProcessor::Trace("Warning: incorrect format for the configuration value: 'images_maximum_size'. Expecting a value dddxddd (where ddd are digits), like 1000x1000, but got: '$sMaxImagesSize'. No maximum value will be set.");
						self::$iMaxImageWidth = 0;
					}
				}
				else
				{
					MailInboxesEmailProcessor::Trace("Info: no maximum dimensions configured for attachment images.");
					self::$iMaxImageWidth = 0;
				}
			}
			else
			{
				MailInboxesEmailProcessor::Trace("Info: GD not installed, cannot resize big images.");
				self::$iMaxImageWidth = 0;
			}
			
		}
		$aAddedAttachments = array();
		// Process attachments (if any)
		$aPreviousAttachments = array();
		$aRejectedAttachments = array();
		if ($bNoDuplicates)
		{
			$sOQL = "SELECT Attachment WHERE item_class = :class AND item_id = :id";
			$oAttachments = new DBObjectSet(DBObjectSearch::FromOQL($sOQL), array(), array('class' => get_class($oTicket), 'id' => $oTicket->GetKey()));
			while($oPrevAttachment = $oAttachments->Fetch())
			{
				$oDoc = $oPrevAttachment->Get('contents');
				$data = $oDoc->GetData();
				$aPreviousAttachments[] = array(
					'class' => 'Attachment',
					'filename' => $oDoc->GetFileName(),
					'mimeType' => $oDoc->GetMimeType(),
					'size' => strlen($data),
					'md5' => md5($data),
					'object' => $oPrevAttachment,
				);
			}
			// same processing for InlineImages
			if (class_exists('InlineImage'))
			{
				$sOQL = "SELECT InlineImage WHERE item_class = :class AND item_id = :id";
				$oAttachments = new DBObjectSet(DBObjectSearch::FromOQL($sOQL), array(), array('class' => get_class($oTicket), 'id' => $oTicket->GetKey()));
				while($oPrevAttachment = $oAttachments->Fetch())
				{
					$oDoc = $oPrevAttachment->Get('contents');
					$data = $oDoc->GetData();
					$aPreviousAttachments[] = array(
						'class' => 'InlineImage',
						'filename' => $oDoc->GetFileName(),
						'mimeType' => $oDoc->GetMimeType(),
						'size' => strlen($data),
						'md5' => md5($data),
						'object' => $oPrevAttachment,
					);
				}
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
				if ($this->IsImage($aAttachment['mimeType']))
				{
					$aImgInfo = array();
					if (((self::$iMinImageWidth > 0) || (self::$iMaxImageWidth > 0)) && (($aImgInfo = $this->GetImageSize($aAttachment['content'], $aImgInfo)) !== false))
					{
						$iWidth = $aImgInfo[0];
						$iHeight = $aImgInfo[1];
						if (($iWidth < self::$iMinImageWidth) || ($iHeight < self::$iMinImageHeight))
						{
							$bIgnoreAttachment = true;
							$aIgnoredAttachments[$aAttachment['content-id']] = true;
							MailInboxesEmailProcessor::Trace("Info: attachment '{$aAttachment['filename']}': $iWidth x $iHeight px rejected because it is too small (probably a signature). The minimum size is configured to ".self::$iMinImageWidth." x ".self::$iMinImageHeight." px");
						}
						else if ((self::$iMaxImageWidth > 0) && (($iWidth > self::$iMaxImageWidth) || ($iHeight > self::$iMaxImageHeight)))
						{
							MailInboxesEmailProcessor::Trace("Info: attachment '{$aAttachment['filename']}': $iWidth x $iHeight px will be resized to fit into ".self::$iMaxImageWidth." x ".self::$iMaxImageHeight." px");
							$aAttachment = self::ResizeImageToFit($aAttachment, $iWidth, $iHeight, self::$iMaxImageWidth, self::$iMaxImageHeight);
						}
					}
				}
				if (!$bIgnoreAttachment && $bNoDuplicates)
				{
					// Check if an attachment with the same name/type/size/md5 already exists
					$iSize = strlen($aAttachment['content']);
					if ($iSize > $this->iMaxAttachmentSize)
					{
						// The attachment is too big, reject it, and replace it by a text message, explaining what happened
						$aAttachment = $this->RejectBigAttachment($aAttachment, $oTicket);
						$aRejectedAttachments[] = $aAttachment['content'];
						MailInboxesEmailProcessor::Trace("Info: attachment '{$aAttachment['filename']}' too big (size = $iSize > max size = {$this->iMaxAttachmentSize} bytes)");
					}
					else
					{
						$sMd5 = md5($aAttachment['content']);
						foreach($aPreviousAttachments as $aPrevious)
						{
							if (($aAttachment['filename'] == $aPrevious['filename']) &&
							    ($aAttachment['mimeType'] == $aPrevious['mimeType']) &&
							    ($iSize == $aPrevious['size']) &&
							    ($sMd5 == $aPrevious['md5']) )
							{
								// Skip this attachment
								MailInboxesEmailProcessor::Trace("Info: attachment {$aAttachment['filename']} skipped, already attached to the ticket.");
								$aAddedAttachments[$aAttachment['content-id']] = $aPrevious['object']; // Still remember it for processing inline images
								$bIgnoreAttachment = true;
								break;
							}
						}
					}
				}
				if (!$bIgnoreAttachment && $this->ContainsViruses($aAttachment))
				{
					// Skip this attachment
					MailInboxesEmailProcessor::Trace("Info: attachment {$aAttachment['filename']} is reported as containing a virus, skipped.");
					$aRejectedAttachments[] = "attachment {$aAttachment['filename']} was reported as containing a virus, it has been skipped.";
					$bIgnoreAttachment = true;
				}
				if (!$bIgnoreAttachment)
				{
					if (class_exists('InlineImage') && $aAttachment['inline'])
					{
						$oAttachment = new InlineImage();
						MailInboxesEmailProcessor::Trace("Info: email attachment {$aAttachment['filename']} will be stored as an InlineImage.");
						$oAttachment->Set('secret', sprintf ('%06x', mt_rand(0, 0xFFFFFF))); // something not easy to guess
					}
					else
					{
						MailInboxesEmailProcessor::Trace("Info: email attachment {$aAttachment['filename']} will be stored as an Attachment.");
						$oAttachment = new Attachment();
					}
					if ($oTicket->IsNew())
					{
						$oAttachment->Set('item_class', get_class($oTicket));
					}
					else
					{
						$oAttachment->SetItem($oTicket);
					}
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
					$aAddedAttachments[$aAttachment['content-id']] = $oAttachment;
				}
			}
			else
			{
				MailInboxesEmailProcessor::Trace("Info: The attachment {$aAttachment['filename']} was NOT added to the ticket because its type '{$aAttachment['mimeType']}' is excluded according to the configuration");
			}
		}
		if (count($aRejectedAttachments) > 0)
		{
			// Report the problem to the administrator...
			$this->HandleError($oEmail, 'rejected_attachments', null, implode("\n", $aRejectedAttachments));
		}
		
		return $aAddedAttachments;
	}
	
	/**
	 *
	 * Update the item_class, item_id and item_org_id of the Attachments to match the values of the suplied ticket
	 * To be called if the attachments were created BEFORE the actual creation of the ticket in the database
	 * @param array $aAttachments An array of Attachment objects
	 * @param Ticket $oTicket
	 * @return an array of cid => Attachment
	 */
	protected function UpdateAttachments($aAttachments, Ticket $oTicket)
	{
		foreach($aAttachments as $oAttachment)
		{
			$oAttachment->SetItem($oTicket);
			$oAttachment->DBUpdate();
		}
	}
	
	/**
	 * Check if an the given mimeType is an image that can be processed by the system
	 * @param string $sMimeType
	 * @return boolean
	 */
	protected function IsImage($sMimeType)
	{
		if (!function_exists('gd_info')) return false; // no image processing capability on this system
		
		$bRet = false;
		$aInfo = gd_info(); // What are the capabilities
		switch($sMimeType)
		{
			case 'image/gif':
			return $aInfo['GIF Read Support'];
			break;
			
			case 'image/jpeg':
			return $aInfo['JPEG Support'];
			break;
			
			case 'image/png':
			return $aInfo['PNG Support'];
			break;

		}
		return $bRet;
	}
	
	protected function GetImageSize($sImageData)
	{
		if (function_exists('getimagesizefromstring')) // PHP 5.4.0 or higher
		{
			$aRet = @getimagesizefromstring($sImageData);
		}
		else if(ini_get('allow_url_fopen'))
		{
			// work around to avoid creating a tmp file
			$sUri = 'data://application/octet-stream;base64,'.base64_encode($sImageData);
			$aRet = @getimagesize($sUri);
		}
		else
		{
			// Damned, need to create a tmp file
			$sTempFile = tempnam(SetupUtils::GetTmpDir(), 'img-');
			@file_put_contents($sTempFile, $sImageData);
			$aRet = @getimagesize($sTempFile);
			@unlink($sTempFile);
		}
		return $aRet;
	}
	
	/**
	 * Check if the supplied attachment contains a virus: implement you own methods based on your antivirus...
	 * The following (inactive) code is just provided as an example
	 * @param hash $aAttachment
	 * @return bool True if the attachment contains a virus (and should NOT be attached to the ticket), false otherwise
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
	 * Truncates the text, if needed, to fit into the given the maximum length and:
	 * 1) Takes care of replacing line endings by \r\n since the browser produces this kind of line endings inside a TEXTAREA
	 * 2) Trims the result to emulate the behavior of iTop's inputs
	 * @param string $sInputText
	 * @param int $iMaxLength
	 * @return string The fitted text
	 */
	protected function FitTextIn($sInputText, $iMaxLength)
	{
		$sInputText = trim($sInputText);
		$sInputText = str_replace("\r\n", "\r", $sInputText);
		$sInputText = str_replace("\n", "\r", $sInputText);
		$sInputText = str_replace("\r", "\r\n", $sInputText);
		if (strlen($sInputText) > $iMaxLength)
		{
			$sInputText = trim(substr($sInputText, 0, $iMaxLength-3)).'...';
		}
		return $sInputText;
	}
	
	protected function RejectBigAttachment($aAttachment, $oObj)
	{
		$sMessage = "The attachment {$aAttachment['filename']} (".strlen($aAttachment['content'])." bytes) is bigger than the maximum possible size ({$this->iMaxAttachmentSize}).";
		if ($this->sBigFilesDir == '')
		{
			$sMessage .= "The attachment was deleted. In order to keep such attachments in the future, contact your administrator to:\n";
			$sMessage .= "- either increase the 'max_allowed_packet' size in the configuration of the MySQL server to be able to store them in iTop\n";
			$sMessage .= "- or configure the parameter 'big_files_dir' in the iTop configuration file, so that such attachments can be kept on the web server.\n";
		}
		else if (!is_writable($this->sBigFilesDir))
		{
			$sMessage .= "The attachment was deleted, since the directory where to save such files on the web server ({$this->sBigFilesDir}) is NOT writable to iTop.\n";
		}
		else
		{
			$sExtension = '.'.pathinfo($aAttachment['filename'], PATHINFO_EXTENSION);
			$idx = 1;
			$sFileName = 'attachment_'.(get_class($oObj)).'_'.($oObj->GetKey()).'_';
			$hFile = false;
			while(($hFile = fopen($this->sBigFilesDir.'/'.$sFileName.$idx.$sExtension, 'x')) === false)
			{
				$idx++;
			}
			fwrite($hFile, $aAttachment['content']);
			fclose($hFile);
			$sMessage .= "The attachment was saved as '{$sFileName}{$idx}{$sExtension}' on the web server in the directory '{$this->sBigFilesDir}'.\n";
			$sMessage .= "In order to get such attachments into iTop, increase the 'max_allowed_packet' size in the configuration of the MySQL server.\n";
		}
		$aReplacement = array('content' => $sMessage, 'mimeType' => 'text/plain', 'filename' => 'warning.txt');
		return $aReplacement;
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
				 		else if ($oAttDef instanceof AttributeEnum)
				 		{
				 			// For enums the allowed values are value => label
				 			foreach($aValues as $allowedValue => $sLocalizedLabel)
				 			{
				 				if (($allowedValue == $value) || ($sLocalizedLabel == $value))
				 				{
				 					$bFound = true;
				 					$oObj->Set($sAttCode, $allowedValue);
				 					break;
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
			$aImapOptions = MetaModel::GetModuleSetting('combodo-email-synchro', 'imap_options', array('imap'));
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
	
	/**
	 * Resize an image attachment so that it fits in the given dimensions
	 * @param array $aAttachment The original image stored as an attached array (content / mimetype / filename)
	 * @param int $iWidth Image's original width
	 * @param int $iHeight Image's original height
	 * @param int $iMaxImageWidth Maximum width for the resized image
	 * @param int $iMaxImageHeight Maximum height for the resized image
	 * @return array The modified attachment array with the resample image in the 'content'
	 */
	protected static function ResizeImageToFit($aAttachment, $iWidth, $iHeight, $iMaxImageWidth, $iMaxImageHeight)
	{
		$img = false;
		switch($aAttachment['mimeType'])
		{
			case 'image/gif':
			case 'image/jpeg':
			case 'image/png':
			$img = @imagecreatefromstring($aAttachment['content']);
			break;
			
			default:
			// Unsupported image type, return the image as-is
			self::Trace("Warning: unsupported image type: '{$aAttachment['mimeType']}'. Cannot resize the image, original image will be used.");
			return $aAttachment;
		}
		if ($img === false)
		{
			self::Trace("Warning: corrupted image: '{$aAttachment['filename']} / {$aAttachment['mimeType']}'. Cannot resize the image, original image will be used.");
			return $aAttachment;
		}
		else
		{
			// Let's scale the image, preserving the transparency for GIFs and PNGs
			
			$fScale = min($iMaxImageWidth / $iWidth, $iMaxImageHeight / $iHeight);

			$iNewWidth = $iWidth * $fScale;
			$iNewHeight = $iHeight * $fScale;
			
			self::Trace("Info: resizing image from ($iWidth x $iHeight) to ($iNewWidth x $iNewHeight) px");
			$new = imagecreatetruecolor($iNewWidth, $iNewHeight);
			
			// Preserve transparency
			if(($aAttachment['mimeType'] == "image/gif") || ($aAttachment['mimeType'] == "image/png"))
			{
				imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
				imagealphablending($new, false);
				imagesavealpha($new, true);
			}
			
			imagecopyresampled($new, $img, 0, 0, 0, 0, $iNewWidth, $iNewHeight, $iWidth, $iHeight);
			
			ob_start();
			switch ($aAttachment['mimeType'])
			{
				case 'image/gif':
				imagegif($new); // send image to output buffer
				break;
				
				case 'image/jpeg':
				imagejpeg($new, null, 80); // null = send image to output buffer, 80 = good quality
				break;
				 
				case 'image/png':
				imagepng($new, null, 5); // null = send image to output buffer, 5 = medium compression
				break;
			}
			$aAttachment['content'] = ob_get_contents();
			@ob_end_clean();
			
			imagedestroy($img);
			imagedestroy($new);
			
			self::Trace("Info: resized image is ".strlen($aAttachment['content'])." bytes long.");
				
			return $aAttachment;
		}
				
	}
}
