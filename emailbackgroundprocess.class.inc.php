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
						}
						else
						{
							$this->Trace("\nDispatching old (already read) message: uidl=$sUIDL index=$iMessage");
							
						}
						
						$iActionCode = $oProcessor->DispatchMessage($oSource, $iMessage, $sUIDL, $oEmailReplica);
				
						switch($iActionCode)
						{
							case EmailProcessor::DELETE_MESSAGE:
							$iTotalDeleted++;
							$this->Trace("Deleting message (and replica): uidl=$sUIDL index=$iMessage");
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
							$oEmail = $oRawEmail->Decode($oSource->GetPartsOrder());
							if (!$oEmail->IsValid())
							{
								$oProcessor->OnDecodeError($oSource, $sUIDL, $oEmail, $oRawEmail);
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
									$sSubject = $oProcessor->GetLastErrorSubject();
									$sMessage = $oProcessor->GetLastErrorMessage();
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
