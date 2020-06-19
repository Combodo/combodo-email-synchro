<?php
// Copyright (c) 2010-2020 Combodo SARL
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
//


namespace Combodo\iTop\Test\UnitTest\CombodoEmailSynchro;

use Combodo\iTop\Test\UnitTest\ItopTestCase;
use RawEmailMessage;




/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @backupGlobals disabled
 */
class HTMLDOMSanitizerTest extends ItopTestCase
{
	public function setUp()
	{
		parent::setUp();

		require_once(APPROOT . 'env-production/combodo-email-synchro/classes/rawemailmessage.class.inc.php');
	}


	/**
	 * Test the fix for ticket N°2556
	 *
	 * @dataProvider PreserveBlackListedTagContentProvider
	 *
	 */
	public function testDoSanitizePreserveBlackListedTagContent($sFileName, $expected)
	{
//		if (basename($sFileName) == 'TEST failure notice.eml') {$this->markTestSkipped('TEST failure notice.eml takes too much time!'); return;}

		$oEmail = RawEmailMessage::FromFile($sFileName);

		$sBody = $oEmail->GetHTMLBody();
		if (empty($sBody))
		{
			$sBody = $oEmail->GetTextBody();
		}

		$oSanitizer = new \HTMLDOMSanitizer();
		$sSanitizedBody = $oSanitizer->DoSanitize($sBody);

		if (null == $expected)
		{
			@mkdir(APPROOT.'data/testDoSanitizePreserveBlackListedTagContent/');
			file_put_contents(APPROOT.'data/testDoSanitizePreserveBlackListedTagContent/'.basename($sFileName, '.eml').'.html', $sSanitizedBody);
			file_put_contents(APPROOT.'data/testDoSanitizePreserveBlackListedTagContent/'.basename($sFileName, '.eml').'.raw.html', $sSanitizedBody);
			$this->assertEquals($sBody, $sSanitizedBody, 'No expectation found, comparing the raw with the filtered, if acceptable, please paste the file generated into data/testDoSanitizePreserveBlackListedTagContent');
		}
		else
		{
			file_put_contents(APPROOT.'data/testDoSanitizePreserveBlackListedTagContent/'.basename($sFileName, '.eml').'.raw.html', $sSanitizedBody);
			$this->assertEquals($expected, $sSanitizedBody, 'The Sanitized body must equals the expected');
		}
	}


	public function PreserveBlackListedTagContentProvider()
	{
		parent::setUp();

		clearstatcache();
		$aFiles = glob(APPROOT . 'env-production/combodo-email-synchro/test/emailsSample/*.eml');

		$aReturn = array();
		foreach ($aFiles as $sFile)
		{
			$sTestName = basename($sFile);
			$sExpectedFileName = sprintf('%senv-production/combodo-email-synchro/test/DoSanitizeExpected/%s.html', APPROOT, basename($sFile, '.eml'));
			if (! is_file($sExpectedFileName))
			{
				// Tips: if you want to pre-create the files, you can add a touch($sExpectedFileName);  but beware, they will be located in env-production ;)
				$sExpected = null;
			}
			else
			{
				$sExpected = file_get_contents($sExpectedFileName);
			}

			$aReturn[$sTestName] = array(
				'sFileName' => $sFile,
				'expected' => $sExpected,
			);
		}

		return $aReturn;
	}

}

