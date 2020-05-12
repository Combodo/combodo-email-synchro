<?php
require_once('../classes/rawemailmessage.class.inc.php');

function print_addr($aAddresses)
{
	$aRes = array();
	
	foreach($aAddresses as $aAddr)
	{
		if (empty($aAddr['name']))
		{
			$aRes[] = $aAddr['email'];
		}
		else
		{
			$aRes[] = '"'.$aAddr['name'].'" <'.$aAddr['email'].'>';
		}
	}
	
	return implode(', ', $aRes);
}

function CheckAddresses($aAddresses, $sFile, &$aErrors,&$aWarnings)
{
	foreach($aAddresses as $aAddr)
	{
		if (empty($aAddr['email']))
		{
			$aWarnings[$sFile] = 'Empty email address for decoded address.';			
		}
		else if (!is_valid_email_address($aAddr['email']))
		{
			$aErrors[$sFile] = "Invalid email address '{$aAddr['email']}' for decoded address.";			
		}
		
	}
	
}

// A sophisticated (if not 100% exact?) way to check the syntax of an email address
// Copied from: http://www.iamcal.com/publish/articles/php/parsing_email/
//
function is_valid_email_address($email)
{
	$qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
	$dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
	$atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c'.
		'\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
	$quoted_pair = '\\x5c[\\x00-\\x7f]';
	$domain_literal = "\\x5b($dtext|$quoted_pair)*\\x5d";
	$quoted_string = "\\x22($qtext|$quoted_pair)*\\x22";
	$domain_ref = $atom;
	$sub_domain = "($domain_ref|$domain_literal)";
	$word = "($atom|$quoted_string)";
	$domain = "$sub_domain(\\x2e$sub_domain)*.?";
	$local_part = "$word(\\x2e$word)*";
	$addr_spec = "$local_part\\x40$domain";

	return preg_match("!^$addr_spec$!", $email) ? 1 : 0;
}

echo "\n\n\n*************************************************************************************\n";
echo "> > > > > > > > > > > > > > >  S T A R T I N G  ".date('H:i:s')." < < < < < < < < < < < < < < <\n";
echo "*************************************************************************************\n\n";

$sBaseDirPath = '../log';
$rDir = opendir($sBaseDirPath);
$index = 0;
$aErrors = array();
$aWarnings = array();

//foreach(array('email_111.eml') as $sFile)
//foreach(array('email_029.eml') as $sFile)
//foreach(array('email_119.eml') as $sFile)
//foreach(array('email_125.eml') as $sFile)
//foreach(array('email_017.eml', 'email_125.eml', 'email_109.eml', 'email_127.eml') as $sFile)
while($sFile = readdir($rDir))
{
	$sFileName = $sBaseDirPath.'/'.$sFile;
	if (!is_dir($sFileName) && ($sFile != 'readme.txt'))
	{
		$oEmail = RawEmailMessage::FromFile($sFileName);

		echo "======================================\n";
		echo $sFileName."\n";
		echo "======================================\n";
		$sSubject = $oEmail->GetSubject();
		echo "Subject: ".$sSubject."\n";
		$aSender = $oEmail->GetSender();
		CheckAddresses($aSender, $sFile, $aErrors, $aWarnings);
		echo "Sender: ".print_addr($aSender)."\n";
		$aTo = $oEmail->GetTo();
		CheckAddresses($aTo, $sFile, $aErrors, $aWarnings);
		echo "To: ".print_addr($aTo)."\n";
		$aCc = $oEmail->GetCc();
		CheckAddresses($aCc, $sFile, $aErrors, $aWarnings);
		echo "Cc: ".print_addr($aCc)."\n";
		$sTextBody = $oEmail->GetTextBody();
		if ($sTextBody != null)
		{
			echo "Body (text): ".strlen($sTextBody)." characters.\n";
		}
		else
		{
			echo "Body (text): NOT FOUND.\n";
		}
		$sHTMLBody = $oEmail->GetHTMLBody();
		if ($sHTMLBody != null)
		{
			echo "Body (HTML): ".strlen($sHTMLBody)." characters.\n";
		}
		else
		{
			echo "Body (HTML): NOT FOUND.\n";
		}
		if (($sTextBody == null) && ($sHTMLBody == null))
		{
			$aErrors[$sFile] = "No body found. (neither text not HTML).";
		}
		//echo "+++++++++++ Structure  +++++++++++\n";
		//$aStructure = $oEmail->GetStructure();
		//print_r($aStructure);
		//echo "======================================\n";
		$aAttachments = $oEmail->GetAttachments();
		echo count($aAttachments)." attachment(s) to this message.\n";
		if (count($aAttachments) > 0)
		{
			$idx = 1;
			foreach($aAttachments as $aAttachment)
			{
				echo "\tAttachment #$idx\n";
				echo "\t\tName: {$aAttachment['filename']}\n";
				echo "\t\tType: {$aAttachment['mimeType']}\n";
				echo "\t\tSize: ".strlen($aAttachment['content'])." bytes\n";
				$idx++;
			}
		}
		echo "======================================\n";
		$index++;
	}
}

echo "\n*************************************************************************************\n";
echo "Done. Finished at ".date('H:i:s').", processed $index messages.\n";
if (count($aErrors) > 0)
{
	echo count($aErrors)." ERRORS encountered:\n";
	print_r($aErrors);
}
else
{
	echo "Ok, no error encountered.\n";
}
if (count($aWarnings) > 0)
{
	echo count($aWarnings)." warnings encountered:\n";
	print_r($aWarnings);
}
else
{
	echo "Ok, no warning encountered.\n";
}
echo "*************************************************************************************\n";

