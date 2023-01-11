<?php
/*
 * @copyright   Copyright (C) 2010-2021 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */


namespace Combodo\iTop\Test\UnitTest\CombodoEmailSynchro;

use Combodo\iTop\Test\UnitTest\ItopTestCase;
use RawEmailMessage;

/**
 * Class TestRegexAttachmentNames
 *
 * @package Combodo\iTop\Test\UnitTest\CombodoEmailSynchro
 */
class TestRegexAttachmentNames extends ItopTestCase
{
	public function setUp(): void
	{
		parent::setUp();
		require_once(APPROOT.'env-production/combodo-email-synchro/classes/rawemailmessage.class.inc.php');
	}

	/**
	 * @dataProvider AttachmentFilenameProvider
	 * @covers       RawEmailMessage::GetAttachments
	 *
	 * @param string $sInput
	 * @param string $sExceptedAttachmentName
	 */
	public function testNormalizeAttachmentName(string $sInput, string $sExpectedAttachmentName)
	{
		$aMatches = array();
		$sNormalizedAttachmentName = null;
		if (preg_match(RawEmailMessage::$sFileNameRegex, $sInput, $aMatches))
		{
			$sNormalizedAttachmentName = end($aMatches);
			$this->assertEquals($sExpectedAttachmentName, $sNormalizedAttachmentName, "Attachmentname for '".bin2hex($sInput)."' doesn't match. Got'".bin2hex($sNormalizedAttachmentName)."', expected '$sExpectedAttachmentName'.");
		}
		else
		{
			$this->AssertNull($sNormalizedAttachmentName);
		}
	}

	public function AttachmentFilenameProvider()
	{
		return [
			'All allowed Chars' => [
				"!#$%&'*+-.0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ^_`abcdefghijklmnopqrstuvwxyz{|}~",
				"!#$%&'*+-.0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ^_`abcdefghijklmnopqrstuvwxyz{|}~",
			],
			// Something is odd here: x2C (comma) is not in the list of allowed
			// chars in RawEmailMessage::$sFileNameRegex, but nevertheless is
			// not filtered by the Regex, resulting in this test case is failing. 
			// I have no clue, as to why this is happening.
			'All not allowed Chars (from the ASCII Table)' => [ 
				"\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x17\x18\x19\x1A\x1B\x1C\x1E\x1F\x20\x22\x28\x29\x2C\x2F\x3A\x3B\x3C\x3D\x3E\x3F\x40\x5B\x5C\x5D\x7F",
				"",
			],
			'Single Quotes delimit filename' => [
				"'End 'before'",
				"End ",
			],
			'Double Quotes delimit filename' => [
				'"End "before"',
				'End ',
			],
			'Empty String' => [
				'',
				'',
			],
			'Name with a Space' => [
				'OQL Queries',
				'OQL',
			],
			// Same problem as in test for not allowed chars: The comma somehow slips through.
			'Name with a Comma' => [
				'OQL,Queries',
				'OQL',
			],
		];
	}
}
