<?php
// Copyright (C) 2011 Combodo SARL
//
//   This program is free software; you can redistribute it and/or modify
//   it under the terms of the GNU General Public License as published by
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
require_once('../../approot.inc.php');
require_once(APPROOT.'/application/application.inc.php');
require_once(APPROOT.'/application/startup.inc.php');
require_once(APPROOT.'/application/itopwebpage.class.inc.php');
require_once(APPROOT.'/application/clipage.class.inc.php');
require_once(APPROOT.'/modules/combodo-email-synchro/email-source.php');

/**
 * Read page's mandatory param: work in HTTP mode and CLI as well
 */
function ReadMandatoryParam($oP, $sParam)
{
	global $aPageParams;
	assert(isset($aPageParams[$sParam]));
	assert($aPageParams[$sParam]['mandatory']);

	$sValue = utils::ReadParam($sParam, null, true /* Allow CLI */);
	if (is_null($sValue))
	{
		$oP->p("ERROR: Missing argument '$sParam'\n");
		UsageAndExit($oP);
	}
	return trim($sValue);
}

/****************************************************************************
 * 
 * Main program
 * 
 ****************************************************************************/
 
if (utils::IsModeCLI())
{
	$oP = new CLIPage("iTop - Create/Synchronize Tickets from eMails");

	// Perform authentication
	$sAuthUser = ReadMandatoryParam($oP, 'auth_user');
	$sAuthPwd = ReadMandatoryParam($oP, 'auth_pwd');
	if (UserRights::CheckCredentials($sAuthUser, $sAuthPwd))
	{
		UserRights::Login($sAuthUser); // Login & set the user's language
	}
	else
	{
		$oP->p("Access restricted or wrong credentials ('$sAuthUser')");
		$oP->output();
		exit;
	}
}
else
{
	require_once(APPROOT.'/application/loginwebpage.class.inc.php');
	LoginWebPage::DoLogin(); // Check user rights and prompt if needed

	$oP = new iTopWebPage("iTop - Create/Synchronize Tickets from eMails");
	$oP->set_base(utils::GetAbsoluteUrlAppRoot().'pages');
}

// Connect to the POP3 server & open the mailbox

/*
$sPop3Server = MetaModel::GetModuleSetting('combodo-email-synchro', 'pop3_server', '');
$iPort = MetaModel::GetModuleSetting('combodo-email-synchro', 'pop3_port', 110);
$sLogin = MetaModel::GetModuleSetting('combodo-email-synchro', 'mailbox_name', '');
$sPwd = MetaModel::GetModuleSetting('combodo-email-synchro', 'mailbox_pwd', '');
*/

$oProcess = new EmailBackgroundProcess();

$oProcess->Process(PHP_INT_MAX);

$oP->output();
?>
