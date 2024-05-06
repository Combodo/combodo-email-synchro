<?php
// Copyright (c) 2010-2024 Combodo SAS
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


namespace Combodo\iTop\CombodoEmailSynchro\Test\UnitTest\Integration;

use Combodo\iTop\Test\UnitTest\ItopTestCase;
use RawEmailMessage;
use function file_exists;


/**
 * Class HTMLDOMSanitizerTest
 *
 * Test integration of the module with iTop HTMLDOMSanitizer
 *
 * @package Combodo\iTop\Test\UnitTest\CombodoEmailSynchro\Integration
 * @author  Guillaume Lajarige <guillaume.lajarige@combodo.com>
 */
class HTMLDOMSanitizerTest extends ItopTestCase
{
	/**
	 * @inheritDoc
	 */
	protected function LoadRequiredItopFiles(): void
	{
		parent::LoadRequiredItopFiles();

		$this->RequireOnceItopFile('env-production/combodo-email-synchro/classes/rawemailmessage.class.inc.php');
	}


	/**
	 * Test the fix for ticket N°2556
	 *
	 * @dataProvider RemoveBlackListedTagContentProvider
	 */
    public function testDoSanitizeRemoveBlackListedTagContent(string $sFileName, $expected)
    {
        $this->markTestSkipped('Test needs to be fixed, see N°6536');

        $oEmail = RawEmailMessage::FromFile($sFileName);

        $sBody = $oEmail->GetHTMLBody();
        if (empty($sBody)) {
            $sBody = $oEmail->GetTextBody();
        }

        $oSanitizer = new \HTMLDOMSanitizer();
        $sSanitizedBody = $oSanitizer->DoSanitize($sBody);

		$sSanitizedBodyOutputFolderPath = APPROOT . 'data/testDoSanitizePreserveBlackListedTagContent/';

        if (null == $expected) {
            @mkdir();
            file_put_contents($sSanitizedBodyOutputFolderPath . basename($sFileName, '.eml') . '.html', $sSanitizedBody);
            file_put_contents($sSanitizedBodyOutputFolderPath . basename($sFileName, '.eml') . '.raw.html', $sSanitizedBody);
            $this->assertEquals($sBody, $sSanitizedBody, 'No expectation found, comparing the raw with the filtered, if acceptable, please paste the file generated into data/testDoSanitizePreserveBlackListedTagContent');
        } else {
            $sRawFilesPath = $sSanitizedBodyOutputFolderPath;
            if (false === file_exists($sRawFilesPath)) {
                mkdir($sRawFilesPath);
            }
            file_put_contents($sRawFilesPath . basename($sFileName, '.eml') . '.raw.html', $sSanitizedBody);
            $this->assertEquals($expected, $sSanitizedBody, 'The Sanitized body must equals the expected');
        }
    }

    public function RemoveBlackListedTagContentProvider()
    {
        clearstatcache();

        $sEmailsSamplePath = __DIR__ . '/../resources/email-samples/';
        $aFiles = glob($sEmailsSamplePath . '*.eml');

        $aReturn = array();
        foreach ($aFiles as $sFile) {
	        $sTestName = basename($sFile);
	        $sExpectedFileName = __DIR__ . '/../resources/email-expected-sanitized-content/' . basename($sFile, '.eml') . '.html';
            if (!is_file($sExpectedFileName)) {
                // Tips: if you want to pre-create the files, you can add a touch($sExpectedFileName);  but beware, they will be located in env-production ;)
                $sExpected = null;
            } else {
                $sExpected = file_get_contents($sExpectedFileName);
            }

            $aReturn[$sTestName] = array(
                'sFileName' => $sFile,
                'expected' => $sExpected,
            );
        }

        if (count($aReturn) === 0) {
            $this->markTestSkipped('No files to test! Check that the module is correctly deployed in env-production!');
        }

        return $aReturn;
    }

}

