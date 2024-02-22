<?php
/*
 * @copyright   Copyright (C) 2010-2024 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

/**
 * Stand-alone command-line tool to test the decoding of a message (as a .eml file)
 *
 * Usage: php decoding-test.php <eml.file>
 * Usage: php decoding-test.php <eml.file>
 *
 * @since 3.7.7 
 */

use Laminas\Mail\Message;

require_once('../classes/autoload.php');
require_once('../../../approot.inc.php');
require_once(APPROOT.'/lib/autoload.php'); // needs Laminas lib !

date_default_timezone_set('Europe/Paris');

function Usage()
{
	echo "Test the decoding of an email message (stored as a .eml file)\n";
	echo "Usage: php decoding-test.php <eml.file>\n\n";
}

if ($argc != 2)
{
	Usage();
	exit -1;
}

///////////////////////////////////////////////////////////////

$sFilePath = $argv[1];
$sPartsOrder = 'text/html,text/plain,application/pkcs7-mime';

echo "# Decoding test for file '$sFilePath'\n";
if (!file_exists($sFilePath))
{
	echo "ERROR: File '$sFilePath' not found.\n";
}
echo "\n\n\n\n\n\n";



///////////////////////////////////////////////////////////////
echo "## Decoding using Laminas !\n\n";
$sEmlFileContent = file_get_contents($sFilePath);
try {
	$oLaminasMessage = Message::fromString($sEmlFileContent);
	echo "------ HEADER\n";
	var_export($oLaminasMessage->getHeaders()->toArray(), false);
	echo "------ BODY\n";
	var_export($oLaminasMessage->getBodyText(), false);
} catch (Exception $e) {
	echo "💥 An exception was returned :(\n";
	var_export([
		'exception_class' => get_class($e),
		'exception_message' => $e->getMessage(),
		'exception_trace' => $e->getTraceAsString(),
	], false);
}
echo "\n\n\n\n\n\n";



///////////////////////////////////////////////////////////////
echo "## Decoding using MessageFromMailbox::Decode !\n\n";
$oMessage = MessageFromMailbox::FromFile($sFilePath);
$startTime = microtime(true);
$oEmail = $oMessage->Decode($sPartsOrder);
$endTime = microtime(true);

echo "====== Decoded eMail: =========\n";

echo "\n\n\n\n\n\n";
echo "========================================================================================================================\n";
echo "------ HEADER\n";
echo "Subject: {$oEmail->sSubject}\n";
echo "MessageID: {$oEmail->sMessageId}\n";
echo "Date: {$oEmail->sDate}\n";
echo "Recipient: {$oEmail->sRecipient}\n";
echo "Caller (eMail): {$oEmail->sCallerEmail}\n";
echo "Caller (name): {$oEmail->sCallerName}\n";
echo 'To: '.print_r($oEmail->aTos, true)."\n";
echo 'CC: '.print_r($oEmail->aCCs, true)."\n";
echo "Attachments:".count($oEmail->aAttachments)."\n";
$idx = 1;
$aCIDToImage = array();
foreach ($oEmail->aAttachments as $aAttachment) {
	$sInline = $aAttachment['inline'] ? 'yes' : 'no';
	echo "\t$idx {$aAttachment['filename']} - {$aAttachment['mimeType']}, ".strlen($aAttachment['content'])." bytes, CID: ".$aAttachment['content-id'].", Inline ?: $sInline\n";
	// Uncomment the line below to dump the attachments as separate files
	//file_put_contents('/tmp/'.$aAttachment['filename'], $aAttachment['content']);
	if ($aAttachment['content-id'] != '') {
		$aCIDToImage[$aAttachment['content-id']] = $aAttachment;
	}
	$idx++;
}

echo "\n\n\n\n\n\n";
echo "========================================================================================================================\n";
echo "------ BODY\n";
echo "Body Format: {$oEmail->sBodyFormat}\n\n";
echo "Body Text:\n{$oEmail->sBodyText}\n\n";
if ($oEmail->sBodyFormat == 'text/html') {
	if (preg_match_all('/<img[^>]+src=(?:"cid:([^"]+)"|cid:([^ >]+))[^>]*>/i', $oEmail->sBodyText, $aMatches, PREG_OFFSET_CAPTURE)) {
		//print_r($aMatches);
		$aInlineImages = array();
		foreach ($aMatches[0] as $idx => $aInfo) {
			$aInlineImages[$idx] = array('position' => $aInfo[1]);
		}
		foreach ($aMatches[1] as $idx => $aInfo) {
			$sCID = $aInfo[0];
			if (!array_key_exists($sCID, $aCIDToImage)) {
				echo "ERROR: inline image: $sCID NOT FOUND !!!\n";
			} else {
				$aInlineImages[$idx]['cid'] = $sCID;
				echo "Ok, inline image {$aCIDToImage[$sCID]['filename']} as cid:$sCID\n";
			}
		}
		$sWholeText = $oEmail->sBodyText;
		$idx = count($aInlineImages);
		while ($idx > 0) {
			$idx--;
			$sBefore = substr($sWholeText, 0, $aInlineImages[$idx]['position']);
			$sAfter = substr($sWholeText, $aInlineImages[$idx]['position']);
			$sWholeText = $sBefore." [itop attachment: {$aInlineImages[$idx]['cid']}] ".$sAfter;
		}
		echo "=================\n";
		echo "$sWholeText\n";
		echo "=================\n";

		$sBodyText = $oEmail->StripTags($sWholeText);
	} else {
		echo "Inline Images: no inline-image found\n";
		$sBodyText = $oEmail->StripTags();
	}
	echo "------ BODY Text version\n";
	echo "Plain Text Version:\n$sBodyText\n";
}

echo "\n\n\n\n\n\n";
echo "========================================================================================================================\n";
echo "GetNewPartHTML() returned:\n";
echo "===============================\n";
echo $oEmail->GetNewPartHTML($oEmail->sBodyText);
echo "===============================\n";


echo "\n\n\n\n\n\n";
echo "========================================================================================================================\n";
echo "Decoding duration: ".sprintf('%.1f', 1000 * ($endTime - $startTime))." ms\n";
