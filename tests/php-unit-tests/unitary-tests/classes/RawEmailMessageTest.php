<?php
/*
 * @copyright   Copyright (C) 2010-2024 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */


namespace Combodo\iTop\CombodoEmailSynchro\Test\UnitTest\Unitary;

use Combodo\iTop\Test\UnitTest\ItopTestCase;
use RawEmailMessage;

class RawEmailMessageTest extends ItopTestCase
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
	 * @dataProvider ExtractAddressPiecesProvider
	 * @covers       \RawEmailMessage::ExtractAddressPieces()
	 *
	 * @param string $sSourceAddressString
	 * @param string $sExpectedName
	 * @param string $sExpectedEmail
	 */
	public function testExtractAddressPieces(string $sSourceAddressString, string $sExpectedName, string $sExpectedEmail): void
	{
		$aAddressParts = $this->InvokeNonPublicStaticMethod(RawEmailMessage::class, 'ExtractAddressPieces', [$sSourceAddressString]);

		$this->assertCount(2, $aAddressParts, 'method should return 2 results');
		$this->assertArrayHasKey('name', $aAddressParts, 'Result must contain the "name" key');
		$this->assertArrayHasKey('email', $aAddressParts, 'Result must contain the "email" key');
		$this->assertEquals($sExpectedName, $aAddressParts['name'], 'Name different than expected');
		$this->assertEquals($sExpectedEmail, $aAddressParts['email'], 'Email different than expected');
	}

	public function ExtractAddressPiecesProvider(): array
	{
		return [
			'simple email' => [
				'$sSourceAddressString' => 'name@domain.com',
				'$sExpectedName' => '',
				'$sExpectedEmail' => 'name@domain.com',
			],
			'simple email but invalid' => [
				'$sSourceAddressString' => 'name @domain.com',
				'$sExpectedName' => '',
				'$sExpectedEmail' => 'name @domain.com',
			],
			'name + email' => [
				'$sSourceAddressString' => 'Firstname Lastname <name@domain.com>',
				'$sExpectedName' => 'Firstname Lastname',
				'$sExpectedEmail' => 'name@domain.com',
			],
			'name + email, email invalid' => [
				'$sSourceAddressString' => 'Firstname Lastname <name @domain.com>',
				'$sExpectedName' => 'Firstname Lastname',
				'$sExpectedEmail' => 'name @domain.com',
			],
			'name + email : space before closing angled bracket' => [
				'$sSourceAddressString' => 'Firstname Lastname <name@domain.com >',
				'$sExpectedName' => 'Firstname Lastname',
				'$sExpectedEmail' => 'name@domain.com',
			],
			'name + email : double quotes' => [
				'$sSourceAddressString' => '"Firstname Lastname" <name@domain.com>',
				'$sExpectedName' => 'Firstname Lastname',
				'$sExpectedEmail' => 'name@domain.com',
			],
			'name + email : double quotes + space before closing angled bracket' => [
				'$sSourceAddressString' => '"Firstname Lastname" <name@domain.com >',
				'$sExpectedName' => 'Firstname Lastname',
				'$sExpectedEmail' => 'name@domain.com',
			],
		];
	}

	public function MessageToTruncateProvider()
	{
		return [
			'simple case'  => [
				'eml_path'          => __DIR__.'/../../resources/email-samples/email_000.eml',
				'expected_email_id' => '<20110112151854.456323DF24@60gp.ovh.net>',
			],
			'too long'     => [
				'eml_path'          => __DIR__.'/../../resources/email-samples/email_134_messageid_too_long.eml',
				'expected_email_id' => '<74ce6d9f-106e-3125-ce04-15b7e68055efilmefautunmessagebeaucouptroplongpouretreinetegreetquivafaireplanceritopilmefautunmessagebeaucouptroplongpouretreinetegreetquivafaireplanceritopilmefautunmessagebeaucouptroplongpouretreinetegreetquivafaireplanceritopil',
			],
			'not existing' => [
				'eml_path'          => __DIR__.'/../../resources/email-samples/email_135_messageid_empty.eml',
				'expected_email_id' => '',
			],
			'empty string' => [
				'eml_path'          => __DIR__.'/../../resources/email-samples/email_136_messageid_not_existing.eml',
				'expected_email_id' => '',
			],
		];
	}

	/**
	 * @dataProvider MessageToTruncateProvider
	 */
	public function testTruncateMessageId(string $sEMLFilePath, string $sExpectedMessageId)
	{
		$sRrawContent = @file_get_contents($sEMLFilePath);
		$oRawEmailMessage = new RawEmailMessage($sRrawContent);

		$this->assertEquals($sExpectedMessageId, $oRawEmailMessage->GetMessageId());
	}
}