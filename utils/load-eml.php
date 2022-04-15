<?php
/**
 * Loads an EML file into the DB
 * Launch without any parameter to see how to use it
 */

function Usage()
{
	echo "Load an EML file to the DB\n";
	echo "Usage: php load-email.php <MailboxId> <EMLFile>\n\n";
}


//--- Parameters handling
if ($argc !== 3) {
	Usage();
	exit - 1;
}

$iMailboxId = $argv[1];
if (false === is_numeric($iMailboxId)) {
	Usage();
	echo "MailboxId isn't a number :(\n";
	exit - 2;
}

$sEmlFilePath = $argv[2];
if (false === file_exists($sEmlFilePath)) {
	Usage();
	echo "EMLFile doesn't exist\n";
	exit - 3;
}


//--- Starting datamodel
require_once('../../../approot.inc.php');
require_once('../classes/autoload.php');
require_once(APPROOT.'/application/application.inc.php');
require_once(APPROOT.'/application/startup.inc.php');

CMDBObject::SetCurrentChange(null);
CMDBObject::SetTrackInfo('Mail to ticket automation (EML import tool)');
// Important: Don't use the \Combodo\iTop\Core\CMDBChange\CMDBChangeOrigin::EMAIL_PROCESSING yet, as it is only available in iTop 3.0+
CMDBObject::SetTrackOrigin('email-processing');


//--- Init mail objects
try {
	/** @var \MailInboxBase $oMailbox */
	$oMailbox = MetaModel::GetObject('MailInboxBase', $iMailboxId, true, true);
}
catch (CoreException $e) {
	echo "MailboxId '$iMailboxId' doesn't exists :(\n";
	exit - 2;
}
$oSource = $oMailbox->GetEmailSource();
$oSource->SetToken($oMailbox->GetKey());
$oProcessor = new MailInboxesEmailProcessor();
$oProcessor->ListEmailSources();


//--- Processing message
$oEmlRawMessage = MessageFromMailbox::FromFile($sEmlFilePath);
$sMessageId = $oEmlRawMessage->GetMessageId();
$sEmlUIDL = basename($sEmlFilePath);

/** @var \EmailReplica $oEmailReplica */
$oEmailReplica = MetaModel::NewObject(EmailReplica::class);
$oEmailReplica->Set('uidl', $sEmlUIDL);
$oEmailReplica->Set('mailbox_path', $oSource->GetMailbox());
$oEmailReplica->Set('message_id', $sMessageId);
$oEmailReplica->Set('last_seen', date('Y-m-d H:i:s'));

$iActionCode = $oProcessor->DispatchMessage($oSource, 0, $sEmlUIDL, $oEmailReplica);
if ($iActionCode !== EmailProcessor::PROCESS_MESSAGE) {
	echo "Message action code isn't 'process' : $iActionCode\n";
	exit - 4;
}
$oEmail = $oEmlRawMessage->Decode($oSource->GetPartsOrder());
if (false === $oEmail->IsValid()) {
	echo "decoded email isn't valid !\n";
	exit - 5;
}
$iNextActionCode = $oProcessor->ProcessMessage($oSource, 0, $oEmail, $oEmailReplica, $aErrors);

echo "Done !\n";
