<?php
// Copyright (C) 2010-2013 Combodo SARL
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

// Stand-alone command-line tool to test the decoding of a message (as a .eml file)
//
// Usage: php decoding-test.php <eml.file>
//

require_once('rawemailmessage.class.inc.php');
require_once('main.email-synchro.php');

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
$sPartsOrder = 'text/html,text/plain';

echo "Decoding test for file '$sFilePath'\n";
if (!file_exists($sFilePath))
{
	echo "ERROR: File '$sFilePath' not found.\n";
}


$oMessage = MessageFromMailbox::FromFile($sFilePath);
$startTime = microtime(true);
$oEmail = $oMessage->Decode($sPartsOrder);
$endTime = microtime(true);

echo "====== Decoded eMail: =========\n";
echo "Subject: {$oEmail->sSubject}\n";
echo "MessageID: {$oEmail->sMessageId}\n";
echo "Date: {$oEmail->sDate}\n";
echo "Recipient: {$oEmail->sRecipient}\n";
echo "Caller (eMail): {$oEmail->sCallerEmail}\n";
echo "Caller (name): {$oEmail->sCallerName}\n";
echo "Attachments:".count($oEmail->aAttachments)."\n";
$idx = 1;
foreach($oEmail->aAttachments as $aAttachment)
{
	echo "\t$idx. {$aAttachment['filename']} - {$aAttachment['mimeType']}, ".strlen($aAttachment['content'])." bytes\n";
	$idx++;
}
echo "Body Format: {$oEmail->sBodyFormat}\n";
echo "Body Text:\n{$oEmail->sBodyText}\n";
if ($oEmail->sBodyFormat == 'text/html')
{
	$sBodyText = $oEmail->StripTags();
	echo "Plain Text Version:\n$sBodyText\n";
}
echo "===============================\n";


echo "Decoding duration: ".sprintf('%.1f', 1000*($endTime - $startTime))." ms\n";