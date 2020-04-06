<?php
// Copyright (C) 2016 Combodo SARL
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
/**
 * @copyright   Copyright (C) 2016 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

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
	private $aMessageTrace = array();
	private $iCurrentMessage;
	/**
	 * @var EmailSource
	 */
	private $oCurrentSource;
	
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
		$this->aMessageTrace[] = $sText;
		if ($this->bDebug)
		{
			echo $sText."\n";
		}
	}

	/**
	 * Tries to set the error message from the $oProcessor. Sets a default error message in case of failure.
	 *
	 * @param EmailReplica $oEmailReplica
	 * @param EmailProcessor $oProcessor
	 * @param string $sErrorCode
	 * @param null|RawEmailMessage $oRawEmail
	 *
	 * @throws \CoreCannotSaveObjectException
	 * @throws \CoreException
	 * @throws \CoreUnexpectedValue
	 */
	protected function UpdateEmailReplica(&$oEmailReplica, $oProcessor, $sErrorCode = 'error', $oRawEmail = null)
	{
		try
		{
			if (is_null($oRawEmail))
			{
				$oCurrentSource = $this->oCurrentSource;
				$iCurrentMessage = $this->iCurrentMessage;
				if (isset($oCurrentSource))
				{
					$oRawEmail = $oCurrentSource->GetMessage($iCurrentMessage);
				}
			}
			if (!in_array($sErrorCode, MetaModel::GetAllowedValues_att('EmailReplica', 'status')))
			{
				$sErrorCode = 'error';
			}
			$oEmailReplica->Set('status', $sErrorCode);
			if (isset($oRawEmail))
			{
				$this->SaveEml($oEmailReplica, $oRawEmail);
			}

			$iMaxSize = MetaModel::GetAttributeDef('EmailReplica', 'error_message')->GetMaxSize();
			$sErrorMessage = $oProcessor->GetLastErrorSubject()." - ".$oProcessor->GetLastErrorMessage();
			$sErrorMessage = substr($sErrorMessage, 0, $iMaxSize);
			$oEmailReplica->Set('error_message', $sErrorMessage);

            $sDate = $oEmailReplica->Get('message_date');
            if (empty($sDate))
            {
                $oEmailReplica->SetCurrentDate('message_date');
            }
			$oEmailReplica->Set('error_trace', $this->GetMessageTrace());

			$oEmailReplica->DBWrite();
		}
		catch (Exception $e)
		{
			$this->Trace('Error: ' . $oProcessor->GetLastErrorSubject() . " - " . $oProcessor->GetLastErrorMessage());
			IssueLog::Error('Email not processed for email replica of uidl "' . $oEmailReplica->Get('uidl') . '" and message_id "' . $oEmailReplica->Get('message_id') . '" : ' . $oProcessor->GetLastErrorSubject() . " - " . $oProcessor->GetLastErrorMessage());
			$sMessage = $e->getMessage();
			if (strlen($sMessage) > 10*1024)
			{
				$sMessage = "Truncated message: \n".substr($sMessage, 0, 8*1024)."\n[...]\n".substr($sMessage, -2*1024);
			}
			IssueLog::Error($sMessage);

			if (strpos($e->getMessage(), 'MySQL server has gone away') === false)
			{
				$oEmailReplica->Set('status', 'error');
				$oEmailReplica->Set('error_message', 'An error occurred during the processing of this email that could not be displayed here. Consult application error log for details.');
				$oEmailReplica->Set('error_trace', '');
				$oEmailReplica->DBWrite();
			}
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
        $iTotalUndesired = 0;
		foreach(self::$aEmailProcessors as $sProcessorClass)
		{
			/** @var \EmailProcessor $oProcessor */
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
					$sOQL = 'SELECT EmailReplica WHERE uidl IN (' . implode(',', CMDBSource::Quote($aUIDLs)) . ') AND mailbox_path = ' . CMDBSource::Quote($oSource->GetMailbox());
					$this->Trace("Searching EmailReplicas: '$sOQL'");
					$oReplicaSet = new DBObjectSet(DBObjectSearch::FromOQL($sOQL));
					$aReplicas = array();
					while($oReplica = $oReplicaSet->Fetch())
					{
						$aReplicas[$oReplica->Get('uidl')] = $oReplica;
					}				 
					for ($iMessage = 0; $iMessage < $iMsgCount; $iMessage++)
					{
						try
						{
							$this->InitMessageTrace($oSource, $iMessage);
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
								$oEmailReplica->Set('mailbox_path', $oSource->GetMailbox());
								$oEmailReplica->Set('message_id', $iMessage);
							}
							else
							{
								if ($oEmailReplica->Get('status') == 'error')
								{
									$this->Trace("\nSkipping old (already processed) message: uidl=$sUIDL index=$iMessage marked as 'error'");
									$iTotalSkipped++;
									continue;
								}
								elseif ($oEmailReplica->Get('status') == 'ignored')
								{
									$this->Trace("\nSkipping old (already processed) message: uidl=$sUIDL index=$iMessage marked as 'ignored'");
									$iTotalSkipped++;
									continue;
								}
								else
								{
									if ($oEmailReplica->Get('status') == 'undesired')
									{
										$this->Trace("\nUndesired message: uidl=$sUIDL index=$iMessage");
										$iDelay = MetaModel::GetModuleSetting('combodo-email-synchro', 'undesired-purge-delay', 7) * 86400;
										if ($iDelay > 0)
										{
											$sDate = $oEmailReplica->Get('message_date');
											$oDate = DateTime::createFromFormat('Y-m-d H:i:s', $sDate);
											if ($oDate !== false)
											{
												$iDate = $oDate->getTimestamp();
												$iDelay -= time() - $iDate;
											}
										}
										if ($iDelay <= 0)
										{
											$iDelay = MetaModel::GetModuleSetting('combodo-email-synchro', 'undesired-purge-delay', 7);
											$this->Trace("\nDeleting undesired message (AND replica) due to purge delay threshold ({$iDelay}): uidl=$sUIDL index=$iMessage");
											$iTotalDeleted++;
											$ret = $oSource->DeleteMessage($iMessage);
											$this->Trace("DeleteMessage($iMessage) returned $ret");
											if (!$oEmailReplica->IsNew())
											{
												$this->Trace("Deleting replica #".$oEmailReplica->GetKey());
												$oEmailReplica->DBDelete();
												$oEmailReplica = null;
											}
											continue;
										}
										$iTotalSkipped++;
										continue;
									}
									else
									{
										$this->Trace("\nDispatching old (already read) message: uidl=$sUIDL index=$iMessage");
									}
								}
							}

							$iActionCode = $oProcessor->DispatchMessage($oSource, $iMessage, $sUIDL, $oEmailReplica);

							switch ($iActionCode)
							{
								case EmailProcessor::MARK_MESSAGE_AS_ERROR:
									$iTotalMarkedAsError++;
									$this->Trace("Marking the message (and replica): uidl=$sUIDL index=$iMessage as in error.");
									$this->UpdateEmailReplica($oEmailReplica, $oProcessor);
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
									if ((self::$iMaxEmailSize > 0) && ($oRawEmail->GetSize() > self::$iMaxEmailSize))
									{
										$aErrors = array();
										$iNextActionCode = $oProcessor->OnDecodeError($oSource, $sUIDL, null, $oRawEmail, $aErrors);
										$sMessage = implode("\n", $aErrors);
										$this->Trace($sMessage);
										switch ($iNextActionCode)
										{
											case EmailProcessor::MARK_MESSAGE_AS_ERROR:
												$iTotalMarkedAsError++;
												$this->Trace("Email too big, marking the message (and replica): uidl=$sUIDL index=$iMessage as in error.");
												$this->UpdateEmailReplica($oEmailReplica, $oProcessor);
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
												break;
										}
									}
									else
									{
										$oEmail = $oRawEmail->Decode($oSource->GetPartsOrder());
										if (!$oEmail->IsValid())
										{
											$aErrors = array();
											$iNextActionCode = $oProcessor->OnDecodeError($oSource, $sUIDL, $oEmail, $oRawEmail, $aErrors);
											$sMessage = implode("\n", $aErrors);
											$this->Trace($sMessage);
											switch ($iNextActionCode)
											{
												case EmailProcessor::MARK_MESSAGE_AS_ERROR:
													$iTotalMarkedAsError++;
													$this->Trace("Failed to decode the message, marking the message (and replica): uidl=$sUIDL index=$iMessage as in error.");
													$this->UpdateEmailReplica($oEmailReplica, $oProcessor);
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
													break;
											}
										}
										else
										{
											$aErrors = array();
											$iNextActionCode = $oProcessor->ProcessMessage($oSource, $iMessage, $oEmail, $oEmailReplica, $aErrors);
											$sMessage = implode("\n", $aErrors);
											$this->Trace($sMessage);
											switch ($iNextActionCode)
											{
												case EmailProcessor::MARK_MESSAGE_AS_ERROR:
													$iTotalMarkedAsError++;
													$this->Trace("Marking the message (and replica): uidl=$sUIDL index=$iMessage as in error.");
													$this->UpdateEmailReplica($oEmailReplica, $oProcessor);
													$aReplicas[$sUIDL] = $oEmailReplica; // Remember this new replica, don't delete it later as "unused"
													$this->Trace("EmailReplica ID: ".$oEmailReplica->GetKey());
													break;

												case EmailProcessor::MARK_MESSAGE_AS_UNDESIRED:
													$iTotalUndesired++;
													$this->Trace("Marking the message (and replica): uidl=$sUIDL index=$iMessage as undesired.");
													$this->UpdateEmailReplica($oEmailReplica, $oProcessor, 'undesired');
													$aReplicas[$sUIDL] = $oEmailReplica; // Remember this new replica, don't delete it later as "unused"
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
													$this->ReportError($sSubject, $sMessage, $oRawEmail);
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
												case EmailProcessor::NO_ACTION:
													$this->Trace("No more action for EmailReplica ID: ".$oEmailReplica->GetKey());
													$this->UpdateEmailReplica($oEmailReplica, $oProcessor, 'ok', $oRawEmail);
													$aReplicas[$sUIDL] = $oEmailReplica; // Remember this new replica, don't delete it later as "unused"
													break;
											}
										}
									}
									break;

								case EmailProcessor::NO_ACTION:
								default:
									$this->Trace("No action for message (and replica): $sUIDL");
									$aReplicas[$sUIDL] = $oEmailReplica; // Remember this new replica, don't delete it later as "unused"
									break;
							}
							if (time() > $iTimeLimit)
							{
								break;
							} // We'll do the rest later
						}
						catch (Exception $e)
						{
							if (!empty($oEmailReplica))
							{
								$this->Trace($e->getMessage());
								$this->UpdateEmailReplica($oEmailReplica, $oProcessor);
							}
							return $e->getMessage();
						}
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
						$sOQL = "SELECT EmailReplica WHERE uidl LIKE " . CMDBSource::Quote($oSource->GetName() . '_%') . " AND mailbox_path = " . CMDBSource::Quote($oSource->GetMailbox()) . " AND id NOT IN (" . implode(',', CMDBSource::Quote($aIDs)) . ')';
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
		return "Message(s) read: $iTotalMessages, message(s) skipped: $iTotalSkipped, message(s) processed: $iTotalProcessed, message(s) deleted: $iTotalDeleted, message(s) marked as error: $iTotalMarkedAsError, undesired message(s): $iTotalUndesired";
	}

	private function InitMessageTrace($oSource, $iMessage)
	{
		$this->oCurrentSource = $oSource;
		$this->iCurrentMessage = $iMessage;
		$this->aMessageTrace = array();
	}

	private function GetMessageTrace()
	{
		return "<pre>".htmlentities(implode("\n", $this->aMessageTrace), ENT_QUOTES, 'UTF-8')."</pre>";
	}

	/**
	 * @param \EmailReplica $oEmailReplica
	 * @param $oRawEmail
	 *
	 * @throws \CoreException
	 * @throws \CoreUnexpectedValue
	 */
	protected function SaveEml(&$oEmailReplica, $oRawEmail)
	{
		$iContentSize = strlen($oRawEmail->GetRawContent());
		$iMaxServerSize = CMDBSource::GetServerVariable('max_allowed_packet') - 128*1024;
		if ($iContentSize < $iMaxServerSize)
		{
			$oEmailReplica->Set('contents', new ormDocument($oRawEmail->GetRawContent(), 'message/rfc822', 'email.eml'));
		}
		else
		{
			$this->Trace("EML too big ($iContentSize bytes) max is ($iMaxServerSize bytes), not saved in database.");
			$oEmailReplica->Set('error_trace', $this->GetMessageTrace());
		}
	}
}

//EmailBackgroundProcess::RegisterEmailProcessor('TestEmailProcessor');