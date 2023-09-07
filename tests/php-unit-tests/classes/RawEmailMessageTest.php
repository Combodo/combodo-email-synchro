<?php
/*
 * @copyright   Copyright (C) 2010-2021 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Test\UnitTest\CombodoEmailSynchro;

use Combodo\iTop\Test\UnitTest\ItopTestCase;
use RawEmailMessage;

class RawEmailMessageTest extends ItopTestCase
{
	public function setUp(): void
	{
		parent::setUp();

		$this->RequireOnceCurrentModuleFile('/classes/rawemailmessage.class.inc.php');
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
}