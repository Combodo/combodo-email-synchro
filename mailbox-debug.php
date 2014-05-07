<?php
/**
 * Command line tool to debug (i.e. dump) the content of mail inboxes
 * and check their content compared to the EmailReplicas in iTop.
 *
 * Usage: php mailbox-debug.php [--debug] [--noprogress]
 *
 */
require_once('../approot.inc.php');
require_once(APPROOT.'/application/application.inc.php');
require_once(APPROOT.'/application/startup.inc.php');

if (!utils::IsModeCLI())
{
	echo "This page can be run ONLY in command line mode";
	exit;
}

$_bDebug = false;
$_bProgress = true;

foreach($argv as $sArg)
{
	if ($sArg == '--debug')
	{
		$_bDebug = true;
	}
	if ($sArg == '--noprogress')
	{
		$_bProgress = false;
	}
}

function CLIProgressBar($iDone, $iTotal, $sMessage, $bInteractive, $iSize = 25)
{
	global $_bProgress;
	
	if (!$_bProgress) return; // No progress bar
	
    // Out of bounds, exit
    if (($iDone > $iTotal) || ($iTotal <= 0) || ($iDone < 0) )return;
    
    $fPercentage = (float)($iDone / $iTotal);
    
    if ($bInteractive)
    {
	    $iBar = floor($fPercentage * $iSize);
	
	    $sStatusBar = "\r[";
	    $sStatusBar .= str_repeat("=", $iBar);
	    if($iBar < $iSize)
	    {
	        $sStatusBar .= ">";
	        $sStatusBar .= str_repeat(" ", $iSize - $iBar);
	    }
	    else
	    {
	        $sStatusBar .= "=";
	    }
	
	    $sDisp = number_format($fPercentage*100, 0);
	
	    $sStatusBar .= "] $sDisp%  $iDone/$iTotal ".sprintf('%-40s', substr($sMessage, 0, 40));
	
	    echo $sStatusBar;
	
	    flush();
    }
    else
    {
    	echo sprintf("%' 3.0f %% - %s", 100*$fPercentage, $sMessage);
    }
    
    // When done, print a newline
    if($iDone == $iTotal)
    {
       echo "\n";
    }
}

class MailboxDebug
{
	protected static $aEmailProcessors;
	protected static $iMaxEmailSize;
	public $bDebug;
	
	public function __construct()
	{
		self::$aEmailProcessors = array('MailInboxesEmailProcessor');
		$this->bDebug = false;
		$sMaxEmailSize = MetaModel::GetModuleSetting('combodo-email-synchro', 'maximum_email_size', '0');
		self::$iMaxEmailSize = utils::ConvertToBytes($sMaxEmailSize);
	}
	
	public function Trace($sText)
	{
		if ($this->bDebug)
		{
			echo $sText."\n";
		}
	}
	
	protected static function IsMultiSourceMode()
	{
		return true;
	}
	
	public function Run()
	{
		$iTotalMessages = 0;
		foreach(self::$aEmailProcessors as $sProcessorClass)
		{
			$oProcessor = new $sProcessorClass();
			$aSources = $oProcessor->ListEmailSources();
			if (count($aSources) == 0)
			{
				$this->Trace("No MailInbox configured in iTop.");
			}
			
			foreach($aSources as $oSource)
			{
				$aSummary = array();
				$iMsgCount = $oSource->GetMessagesCount();
				$this->Trace("-----------------------------------------------------------------------------------------");			
				$this->Trace("Processing Message Source: ".$oSource->GetName()." GetMessagesCount returned: $iMsgCount");			
				echo "=========================================================================\n";
				echo "Report for the mailbox '".$oSource->GetName()."'\n";
				echo "=========================================================================\n";
				flush();
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
						if(!array_key_exists($oReplica->Get('uidl'), $aReplicas))
						{
							$aReplicas[$oReplica->Get('uidl')] = array();
						}
						$aReplicas[$oReplica->Get('uidl')][] = $oReplica;
					}				 
					for($iMessage = 0; $iMessage < $iMsgCount; $iMessage++)
					{
					
						CLIProgressBar(1+$iMessage, $iMsgCount, 'messages processed', true, 40);
						
						$iTotalMessages++;
						$aRow = array( 'index' => $iMessage );
						if (self::IsMultiSourceMode())
						{
							$sUIDL = $oSource->GetName().'_'.$aMessages[$iMessage]['uidl'];
						}
						else
						{
							$sUIDL = $aMessages[$iMessage]['uidl'];
						}
						$aRow['uid'] = $aMessages[$iMessage]['uidl'];
						
						$oRawEmail = $oSource->GetMessage($iMessage);
						
						if ((self::$iMaxEmailSize > 0) && ($oRawEmail->GetSize() > self::$iMaxEmailSize))
						{
							$this->Trace("Email too big, skipping decode");
							$aRow['message_date'] = '**skipped**';
							$aRow['message_subject'] = '**skipped**';
							$aRow['message_size'] = $oRawEmail->GetSize();
						}
						else
						{
							$oEmail = $oRawEmail->Decode($oSource->GetPartsOrder());
							if (!$oEmail->IsValid())
							{
								$this->Trace("Decoding failed!!!");
								$aRow['message_date'] = '**failed**';
								$aRow['message_subject'] = '**failed**';
								$aRow['message_size'] = $oRawEmail->GetSize();								
							}
							else
							{
								if (isset($oEmail->aHeaders['date']))
								{
									$iTime = (int)strtotime($oEmail->aHeaders['date']); // Parse the RFC822 date format
	 								$sDate = date('Y-m-d H:i:s', $iTime);
									$aRow['message_date'] = $sDate;
								}
								else
								{
									$this->Trace("No 'date' header found.");
									$aRow['message_date'] = '';
								}
								$aRow['message_subject'] = $oEmail->sSubject;
								$aRow['message_size'] = $oRawEmail->GetSize();								
							}
						}
						
						$oEmailReplica = array_key_exists($sUIDL, $aReplicas) ? $aReplicas[$sUIDL][0] : null;
	
						if ($oEmailReplica == null)
						{
							$aRow['status'] = 'new';
							$aRow['ticket'] = '';
							$aRow['replica_id'] = '';
						}
						else
						{
							$aRow['status'] = 'processed';
							if (count($aReplicas[$sUIDL]) > 1)
							{
								$aRow['processed'] = 'duplicate';
								$aRow['ticket'] = '';
								$aRow['replica_id'] = 'several';
							}
							else
							{
								$aRow['processed'] = 'processed';
								$aRow['ticket'] = sprintf("  %06s", (string)$oEmailReplica->Get('ticket_id'));
								$aRow['replica_id'] = (string)$oEmailReplica->GetKey();
							}
							
						}
						$aSummary[] = $aRow;
					}
					$this->PrintSummary($aSummary, $aReplicas);
					echo "=========================================================================\n\n";
					
				}
				else
				{
					$this->Trace("No message to process in this mailbox");
					echo "This mailbox contains no message.\n";
				}
			}
		}
		$this->Trace("$iTotalMessages messages processed.");
	}
	
	protected function PrintSummary($aSummary, $aReplicas)
	{
		$iUIDSize = 1;
		foreach($aSummary as $idx => $aRow)
		{
			$iUIDSize = max($iUIDSize, strlen($aRow['uid']));
		}
		$sFormat = "| %3d |%{$iUIDSize}s| %9s | %8s | %7s | %7.2f | %19s | %30.30s |\n";
		
		$iPart1 = floor(($iUIDSize - 3) / 2);
		$iPart2 = $iUIDSize - 3 - $iPart1; 
		$sDelimiter = '+'.str_repeat('-', $iUIDSize + 104)."+\n";
		echo $sDelimiter;
		echo "| Idx |".str_repeat(' ', $iPart1)."UID".str_repeat(' ', $iPart2)."|   Status  |  Ticket  | Replica | Size KB |         Date        |             Subject           |\n";
		echo $sDelimiter;
		foreach($aSummary as $idx => $aRow)
		{
			echo sprintf($sFormat, $aRow['index'], $aRow['uid'], $aRow['status'], $aRow['ticket'], $aRow['replica_id'],  $aRow['message_size'] / 1024,  $aRow['message_date'],  $aRow['message_subject'] );
		}
		echo $sDelimiter;
		
		$iPotentialProblems = 0;
		foreach($aReplicas as $sUID => $aReps)
		{
			if (count($aReps) > 1)
			{
				$iPotentialProblems++;
				echo "\nPotential problem found:\n";
				echo "The email ($sUID) was processed ".count($aReps)." times, linked with the following tickets:\n";
				foreach($aReps as $oEmailReplica)
				{
					$iTicketId = $oEmailReplica->Get('ticket_id');
					if ($iTicketId == 0)
					{
						echo "No ticket for replica ID = ".$oEmailReplica->GetKey()."? Maybe an error occurred while creating the ticket ??";
					}
					else
					{
						$oTicket = MetaModel::GetObject('Ticket', $iTicketId, false, true);
						if ($oTicket == null)
						{
							echo "Ticket ".$iTicketId." for replica ID = ".$oEmailReplica->GetKey().". This ticket has been deleted\n";
						}
						else
						{
							echo "Ticket ".$oTicket->GetKey()." (status = ".$oTicket->Get('status').") for replica ID = ".$oEmailReplica->GetKey().". Created on ".$oTicket->Get('start_date')."\n";
						}
					}
				}
			}
		}
		echo "=========================================================================\n";
		switch($iPotentialProblems)
		{
			case 0:
			echo "Ok. All replicas are consistent.\n";
			break;

			case 1:
			echo "One potential problem found for this mailbox.\n";
			break;
			
			default:
			echo "A total of $iPotentialProblems potential problems were found for this mailbox.\n";
		}
	}
}

$oTest = new MailboxDebug();
$oTest->bDebug = $_bDebug;
$oTest->Run();