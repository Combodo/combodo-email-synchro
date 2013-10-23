<?php
// Copyright (C) 2013 Combodo SARL
//
//   This file is part of iTop.
//
//   iTop is free software; you can redistribute it and/or modify	
//   it under the terms of the GNU Affero General Public License as published by
//   the Free Software Foundation, either version 3 of the License, or
//   (at your option) any later version.
//
//   iTop is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU Affero General Public License for more details.
//
//   You should have received a copy of the GNU Affero General Public License
//   along with iTop. If not, see <http://www.gnu.org/licenses/>
/**
 * Processing of AJAX calls for the CalendarView
 *
 * @copyright   Copyright (C) 2013 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

require_once('../../approot.inc.php');
require_once(APPROOT.'/application/application.inc.php');
require_once(APPROOT.'/application/webpage.class.inc.php');
require_once(APPROOT.'/application/ajaxwebpage.class.inc.php');

try
{
	require_once(APPROOT.'/application/cmdbabstract.class.inc.php');
	require_once(APPROOT.'/application/startup.inc.php');
	
	require_once(APPROOT.'/application/loginwebpage.class.inc.php');
	LoginWebPage::DoLogin(false /* bMustBeAdmin */, false /* IsAllowedToPortalUsers */); // Check user rights and prompt if needed
	
	$oPage = new ajax_page("");
	$oPage->no_cache();

	$sOperation = utils::ReadParam('operation', '');
	$iMailInboxId = utils::ReadParam('id', 0, false, 'raw_data');
	
	switch($sOperation)
	{
		case 'mailbox_content':
		$oInbox = MetaModel::GetObject('MailInboxBase', $iMailInboxId, false);
		$iMailInboxId = utils::ReadParam('id', 0, false, 'raw_data');
		if(is_object($oInbox))
		{
			$iStartIndex = utils::ReadParam('start', 0);
			$iMaxCount = utils::ReadParam('count', 10);
			$iMsgCount = 0;
			try
			{
				$oSource = $oInbox->GetEmailSource();
				$iTotalMsgCount = $oSource->GetMessagesCount();
				$iStart = min($iStartIndex, $iTotalMsgCount);
				$iEnd = min($iStart + $iMaxCount, $iTotalMsgCount);
				$iMsgCount = $iEnd - $iStart;
				$aMessages = $oSource->GetListing();
			}
			catch(Exception $e)
			{
				$oPage->p("Failed to initialize the mailbox: ".$oInbox->GetName().". Reason: ".$e->getMessage());
			}
						
			$iProcessedCount = 0;
			if ($iMsgCount > 0)
			{
				// Get the corresponding EmailReplica object for each message
				$aUIDLs = array();
				for($iMessage = 0; $iMessage < $iTotalMsgCount; $iMessage++)
				{
					// Assume that EmailBackgroundProcess::IsMultiSourceMode() is always set to true
					$aUIDLs[] = $oSource->GetName().'_'.$aMessages[$iMessage]['uidl'];
				}
				$sOQL = 'SELECT EmailReplica WHERE uidl IN ('.implode(',', CMDBSource::Quote($aUIDLs)).')';
				$oReplicaSet = new DBObjectSet(DBObjectSearch::FromOQL($sOQL));
				$oReplicaSet->OptimizeColumnLoad(array('EmailReplica' => array('uidl', 'ticket_id')));
				$iProcessedCount = $oReplicaSet->Count();
				$aProcessed = array();
				while($oReplica = $oReplicaSet->Fetch())
				{
					$aProcessed[$oReplica->Get('uidl')] = $oReplica->Get('ticket_id');
				}
				
				$aTableConfig = array(
					'status' => array('label' => Dict::S('MailInbox:Status'), 'description' => ''),
					'from' => array('label' => Dict::S('MailInbox:From'), 'description' => ''),
					'subject' => array('label' => Dict::S('MailInbox:Subject'), 'description' => ''),
					'ticket' => array('label' =>  Dict::S('MailInbox:RelatedTicket'), 'description' => ''),
				);

				$aData = array();
				for($iMessage = $iStart; $iMessage < $iStart+$iMsgCount; $iMessage++)
				{
					$oRawEmail = $oSource->GetMessage($iMessage);
					$oEmail = $oRawEmail->Decode($oSource->GetPartsOrder());

					// Assume that EmailBackgroundProcess::IsMultiSourceMode() is always set to true
					$sUIDLs = $oSource->GetName().'_'.$aMessages[$iMessage]['uidl'];
					$sNew = array_key_exists($sUIDLs, $aProcessed) ? Dict::S('MailInbox:Status/Processed') : Dict::S('MailInbox:Status/New') ;
					if (array_key_exists($sUIDLs, $aProcessed))
					{
						$sTicketUrl = ApplicationContext::MakeObjectUrl($oInbox->Get('target_class'), $aProcessed[$sUIDLs]);
						$sLink = '<a href="'.$sTicketUrl.'">'.$oInbox->Get('target_class').'::'.$aProcessed[$sUIDLs].'</a>';
					}
					else
					{
						$sLink = '';
					}
					$aData[] = array('status' => $sNew, 'from' => $oEmail->sCallerEmail, 'subject' => $oEmail->sSubject, 'ticket' => $sLink);
				}
				$oPage->p(Dict::Format('MailInbox:Z_DisplayedThereAre_X_Msg_Y_NewInTheMailbox', $iMsgCount, $iTotalMsgCount, ($iTotalMsgCount - $iProcessedCount)));					
				$oPage->table($aTableConfig, $aData);
			}
			else
			{
				$oPage->p(Dict::Format('MailInbox:EmptyMailbox'));					
			}
		}
		else
		{
			$oPage->P(Dict::S('UI:ObjectDoesNotExist'));
		}
		break;
	}
	$oPage->output();
}
catch(Exception $e)
{	
	$oPage->SetContentType('text/html');
	$oPage->add($e->getMessage());
	$oPage->output();
}