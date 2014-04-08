<?php

/**
 * The interface between iBackgroundProcess (generic background tasks for iTop)
 * and the emails processing mechanism based on EmailProcessor
 */
class EmailBackgroundProcess implements iBackgroundProcess
{
	protected static $aEmailProcessors = array();
	protected static $sSaveErrorsTo = '';
	protected static $sNotifyErrorsTo = '';
	protected static $sNotifyErrorsFrom = '';
	protected static $bMultiSourceMode = false;
	public static $iMaxEmailSize = 0;
	protected $bDebug;
	
	/**
	 * Activates the given EmailProcessor specified by its class name
	 * @param string $sClassName
	 */
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
		$sMaxEmailSize = MetaModel::GetModuleSetting('combodo-email-synchro', 'maximum_email_size', '0');
		self::$iMaxEmailSize = utils::ConvertToBytes($sMaxEmailSize);
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
		return (int)MetaModel::GetModuleSetting('combodo-email-synchro', 'periodicity', 30); // seconds
	}

	public function ReportError($sSubject, $sMessage, $oRawEmail)
	{
		if ( (self::$sNotifyErrorsTo != '') && (self::$sNotifyErrorsFrom != ''))
		{
			$oRawEmail->SendAsAttachment(self::$sNotifyErrorsTo, self::$sNotifyErrorsFrom, $sSubject, $sMessage);
			//@mail(self::$sNotifyErrorsTo, $sSubject, $sMessage, 'From: '.self::$sNotifyErrorsFrom);
		}
	}
	
	/**
	 * Call this function to set this mode to true if you want to
	 * process several incoming mailboxes and if the mail server
	 * does not assign unique UIDLs accross all mailboxes
	 * For example with MS Exchange the UIDL is just a sequential
	 * number 1,2,3... inside each mailbox.
	 */
	public static function SetMultiSourceMode($bMode = true)
	{
		self::$bMultiSourceMode = $bMode;
	}
	
	public static function IsMultiSourceMode()
	{
		return self::$bMultiSourceMode;
	}
	
	public function Process($iTimeLimit)
	{
		$iTotalMessages = 0;
		$iTotalProcessed = 0;
		$iTotalMarkedAsError = 0;
		$iTotalSkipped = 0;
		$iTotalDeleted = 0;
		foreach(self::$aEmailProcessors as $sProcessorClass)
		{
			$oProcessor = new $sProcessorClass();
			$aSources = $oProcessor->ListEmailSources();
			foreach($aSources as $oSource)
			{
				$iMsgCount = $oSource->GetMessagesCount();
				$this->Trace("-----------------------------------------------------------------------------------------");			
				$this->Trace("Processing Message Source: ".$oSource->GetName()." GetMessagesCount returned: $iMsgCount");			

				if ($iMsgCount != 0)
				{
					$aMessages = $oSource->GetListing();
					$iMsgCount = count($aMessages);

					// Get the corresponding EmailReplica object for each message
					$aUIDLs = array();
					for($iMessage = 0; $iMessage < $iMsgCount; $iMessage++)
					{
						if (self::IsMultiSourceMode())
						{
							$aUIDLs[] = $oSource->GetName().'_'.$aMessages[$iMessage]['uidl'];
						}
						else
						{
							$aUIDLs[] = $aMessages[$iMessage]['uidl'];
						}
					}
					$sOQL = 'SELECT EmailReplica WHERE uidl IN ('.implode(',', CMDBSource::Quote($aUIDLs)).')';
					$this->Trace("Searching EmailReplicas: '$sOQL'");
					$oReplicaSet = new DBObjectSet(DBObjectSearch::FromOQL($sOQL));
					$aReplicas = array();
					while($oReplica = $oReplicaSet->Fetch())
					{
						$aReplicas[$oReplica->Get('uidl')] = $oReplica;
					}				 
					for($iMessage = 0; $iMessage < $iMsgCount; $iMessage++)
					{
						$iTotalMessages++;
						if (self::IsMultiSourceMode())
						{
							$sUIDL = $oSource->GetName().'_'.$aMessages[$iMessage]['uidl'];
						}
						else
						{
							$sUIDL = $aMessages[$iMessage]['uidl'];
						}
						
						$oEmailReplica = array_key_exists($sUIDL, $aReplicas) ? $aReplicas[$sUIDL] : null;
	
						if ($oEmailReplica == null)
						{
							$this->Trace("\nDispatching new message: uidl=$sUIDL index=$iMessage");
							// Create a replica to keep track that we've processed this email
							$oEmailReplica = new EmailReplica();
							$oEmailReplica->Set('uidl', $sUIDL);	
							$oEmailReplica->Set('message_id', $iMessage);
						}
						else if($oEmailReplica->Get('status') == 'error')
						{
							$this->Trace("\nSkipping old (already processed) message: uidl=$sUIDL index=$iMessage marked as 'error'");
							$iTotalSkipped++;
							continue;
						}
						else
						{
							$this->Trace("\nDispatching old (already read) message: uidl=$sUIDL index=$iMessage");
							
						}
						
						$iActionCode = $oProcessor->DispatchMessage($oSource, $iMessage, $sUIDL, $oEmailReplica);
				
						switch($iActionCode)
						{
							case EmailProcessor::MARK_MESSAGE_AS_ERROR:
							$iTotalMarkedAsError++;
							$this->Trace("Marking the message (and replica): uidl=$sUIDL index=$iMessage as in error.");
							$oEmailReplica->Set('status', 'error');
							$oEmailReplica->Set('error_message', $oProcessor->GetLastErrorSubject()." - ".$oProcessor->GetLastErrorMessage());
							$oEmailReplica->DBWrite();
							break;
							
							case EmailProcessor::DELETE_MESSAGE:
							$iTotalDeleted++;
							$this->Trace("Deleting message (AND replica): uidl=$sUIDL index=$iMessage");
							$ret = $oSource->DeleteMessage($iMessage);
							$this->Trace("DeleteMessage($iMessage) returned $ret");
							if (!$oEmailReplica->IsNew())
							{
								$this->Trace("Deleting replica #".$oEmailReplica->GetKey());
								$oEmailReplica->DBDelete();
								$oEmailReplica = null;
							}
							break;
							
							case EmailProcessor::PROCESS_MESSAGE:
							$iTotalProcessed++;
							if ($oEmailReplica->IsNew())
							{
								$this->Trace("Processing new message: $sUIDL");
							}
							else
							{
								$this->Trace("Processing old (already read) message: $sUIDL");
							}
	
	
							$oRawEmail = $oSource->GetMessage($iMessage);
							//$oRawEmail->SaveToFile(dirname(__FILE__)."/log/$sUIDL.eml"); // Uncomment the line to keep a local copy if needed
							if ((self::$iMaxEmailSize > 0) && ($oRawEmail->GetSize() > self::$iMaxEmailSize))
							{
								$iNextActionCode = $oProcessor->OnDecodeError($oSource, $sUIDL, null, $oRawEmail);
								switch($iNextActionCode)
								{
									case EmailProcessor::MARK_MESSAGE_AS_ERROR:
									$iTotalMarkedAsError++;
									$this->Trace("Email too big, marking the message (and replica): uidl=$sUIDL index=$iMessage as in error.");
									$oEmailReplica->Set('status', 'error');
									$oEmailReplica->Set('error_message', $oProcessor->GetLastErrorSubject()." - ".$oProcessor->GetLastErrorMessage());
									$oEmailReplica->DBWrite();
									$aReplicas[$sUIDL] = $oEmailReplica; // Remember this new replica, don't delete it later as "unused"
									break;
						
									case EmailProcessor::DELETE_MESSAGE:
									$iTotalDeleted++;
									$this->Trace("Email too big, deleting message (and replica): $sUIDL");
									$oSource->DeleteMessage($iMessage);
									if (!$oEmailReplica->IsNew())
									{
										$oEmailReplica->DBDelete();
										$oEmailReplica = null;
									}
								}								
							}
							else
							{
								$oEmail = $oRawEmail->Decode($oSource->GetPartsOrder());
								if (!$oEmail->IsValid())
								{
									$iNextActionCode = $oProcessor->OnDecodeError($oSource, $sUIDL, null, $oRawEmail);
									switch($iNextActionCode)
									{
										case EmailProcessor::MARK_MESSAGE_AS_ERROR:
										$iTotalMarkedAsError++;
										$this->Trace("Failed to decode the message, marking the message (and replica): uidl=$sUIDL index=$iMessage as in error.");
										$oEmailReplica->Set('status', 'error');
										$oEmailReplica->Set('error_message', $oProcessor->GetLastErrorSubject()." - ".$oProcessor->GetLastErrorMessage());
										$oEmailReplica->DBWrite();
										$aReplicas[$sUIDL] = $oEmailReplica; // Remember this new replica, don't delete it later as "unused"
										break;
							
										case EmailProcessor::DELETE_MESSAGE:
										$iTotalDeleted++;
										$this->Trace("Failed to decode the message, deleting it (and its replica): $sUIDL");
										$oSource->DeleteMessage($iMessage);
										if (!$oEmailReplica->IsNew())
										{
											$oEmailReplica->DBDelete();
											$oEmailReplica = null;
										}
									}
								}
								else
								{
									$iNextActionCode = $oProcessor->ProcessMessage($oSource, $iMessage, $oEmail, $oEmailReplica);
									switch($iNextActionCode)
									{
										case EmailProcessor::MARK_MESSAGE_AS_ERROR:
										$iTotalMarkedAsError++;
										$this->Trace("Marking the message (and replica): uidl=$sUIDL index=$iMessage as in error.");
										$oEmailReplica->Set('status', 'error');
										$oEmailReplica->Set('error_message', $oProcessor->GetLastErrorSubject()." - ".$oProcessor->GetLastErrorMessage());
										$oEmailReplica->DBWrite();
										$aReplicas[$sUIDL] = $oEmailReplica; // Remember this new replica, don't delete it later as "unused"
										$this->Trace("EmailReplica ID: ".$oEmailReplica->GetKey());
										break;
							
										case EmailProcessor::DELETE_MESSAGE:
										$iTotalDeleted++;
										$this->Trace("Deleting message (and replica): $sUIDL");
										$oSource->DeleteMessage($iMessage);
										if (!$oEmailReplica->IsNew())
										{
											$oEmailReplica->DBDelete();
											$oEmailReplica = null;
										}
										break;
										
										case EmailProcessor::PROCESS_ERROR:
										$sSubject = $oProcessor->GetLastErrorSubject();
										$sMessage = $oProcessor->GetLastErrorMessage();
										EmailBackgroundProcess::ReportError($sSubject, $sMessage, $oRawEmail);
										$iTotalDeleted++;
										$this->Trace("Deleting message (and replica): $sUIDL");
										$oSource->DeleteMessage($iMessage);
										if (!$oEmailReplica->IsNew())
										{
											$oEmailReplica->DBDelete();
											$oEmailReplica = null;
										}
										break;
	
										default:
										$this->Trace("EmailReplica ID: ".$oEmailReplica->GetKey());
										$aReplicas[$sUIDL] = $oEmailReplica; // Remember this new replica, don't delete it later as "unused"
									}
								}
							}
							break;
				
							case EmailProcessor::NO_ACTION:
							default:
							$aReplicas[$sUIDL] = $oEmailReplica; // Remember this new replica, don't delete it later as "unused"
							break;
						}
						if (time() > $iTimeLimit) break; // We'll do the rest later
					}
					if (time() > $iTimeLimit) break; // We'll do the rest later
					
					if (self::IsMultiSourceMode())
					{
						$aIDs = array(-1); // Make sure that the array is never empty...
						foreach($aReplicas as $oUsedReplica)
						{
							if (is_object($oUsedReplica) && ($oUsedReplica->GetKey() != null))
							{
								$aIDs[] = $oUsedReplica->GetKey();
							}
						}
						
						// Cleanup the unused replicas based on the pattern of their UIDL, unfortunately this is not possible in NON multi-source mode
						$sOQL = "SELECT EmailReplica WHERE uidl LIKE ".CMDBSource::Quote($oSource->GetName().'_%')." AND id NOT IN (".implode(',', CMDBSource::Quote($aIDs)).')';
						$this->Trace("Searching for unused EmailReplicas: '$sOQL'");
						$oUnusedReplicaSet = new DBObjectSet(DBObjectSearch::FromOQL($sOQL));
						$oUnusedReplicaSet->OptimizeColumnLoad(array('EmailReplica' => array('uidl')));
						while($oReplica = $oUnusedReplicaSet->Fetch())
						{
							$this->Trace("Deleting unused EmailReplica (#".$oReplica->GetKey()."), UIDL: ".$oReplica->Get('uidl'));
							$oReplica->DBDelete();
							if (time() > $iTimeLimit) break; // We'll do the rest later
						}
					}
				}
				$oSource->Disconnect();
			}
			if (time() > $iTimeLimit) break; // We'll do the rest later
		}
		return "Message(s) read: $iTotalMessages, message(s) skipped: $iTotalSkipped, message(s) processed: $iTotalProcessed, message(s) deleted: $iTotalDeleted, message(s) marked as error: $iTotalMarkedAsError";
	}
}

//EmailBackgroundProcess::RegisterEmailProcessor('TestEmailProcessor');