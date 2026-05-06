<?php

/*
 * @copyright   Copyright (C) 2010-2026 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\CombodoEmailSynchro\Test\UnitTest\Unitary;

use Combodo\iTop\Extension\EmailSynchro\Service\IMAPEmailSource;
use Combodo\iTop\Test\UnitTest\ItopTestCase;
use DirectoryTree\ImapEngine\Testing\FakeFolder;
use DirectoryTree\ImapEngine\Testing\FakeMailbox;
use MessageFromMailbox;
use MetaModel;
use ReflectionClass;
use ReflectionProperty;
use utils;

class IMAPEmailSourceTest extends ItopTestCase
{
	private $oConfig;
	private $bOriginalUseMessageIdAsUid;

	protected function setUp(): void
	{
		parent::setUp();
		$this->oConfig = utils::GetConfig();
		$this->bOriginalUseMessageIdAsUid = MetaModel::GetModuleSetting('combodo-email-synchro', 'use_message_id_as_uid', false);
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		$this->setUseMessageIdAsUid($this->bOriginalUseMessageIdAsUid);
	}
	protected function LoadRequiredItopFiles(): void
	{
		parent::LoadRequiredItopFiles();
		$this->RequireOnceItopFile('env-production/combodo-email-synchro/classes/autoload.php');
		$this->RequireOnceItopFile('env-production/combodo-email-synchro/tests/php-unit-tests/unitary-tests/classes/TestImapMessage.php');
	}

	public function testIndexedUidFromListing(): void
	{
		$aMessages = [
			$this->makeMessage(100, '<id-100@example.com>', "Message-ID: <id-100@example.com>\r\n", "Body 100"),
			$this->makeMessage(200, '<id-200@example.com>', "Message-ID: <id-200@example.com>\r\n", "Body 200"),
			$this->makeMessage(300, '<id-300@example.com>', "Message-ID: <id-300@example.com>\r\n", "Body 300"),
		];

		$oSource = $this->makeSourceWithMessages($aMessages, 'Processed');

		$aListing = $oSource->GetListing();
		$this->assertCount(3, $aListing);

		$aIndexedUids = $this->getProtectedProperty($oSource, 'aIndexedUids');
		$iUidl = $aIndexedUids[0];
		$sExpected = 100;
		$this->assertEquals($sExpected, $iUidl);
	}

	/**
	 * @dataProvider GetMessageUsesIndexedUidFromListingProvider
	 */
	public function testGetMessageUsesIndexedUidFromListing($bUseMessageIdAsUid, $sExpectedUid): void
	{
		$this->setUseMessageIdAsUid($bUseMessageIdAsUid);

		$aMessages = [
			$this->makeMessage(100, '<id-100@example.com>', "Message-ID: <id-100@example.com>\r\n", "Body 100"),
			$this->makeMessage(200, '<id-200@example.com>', "Message-ID: <id-200@example.com>\r\n", "Body 200"),
		];

		$oSource = $this->makeSourceWithMessages($aMessages, 'Processed');

		$aListing = $oSource->GetListing();
		$this->assertCount(2, $aListing);

		$oMessage = $oSource->GetMessage(0);
		$this->assertInstanceOf(MessageFromMailbox::class, $oMessage);

		$iUidl = $this->getProtectedProperty($oMessage, 'sUIDL');
		$this->assertEquals($sExpectedUid, $iUidl);
	}

	public function GetMessageUsesIndexedUidFromListingProvider()
	{
		return [
			'UseMessageId' => [
				true,
				'<id-100@example.com>',
			],
			'DoesntUseMessageId' => [
				false,
				100,
			],
		];
	}

	public function testDeleteMessageUsesIndexedUidFromListing(): void
	{
		$aMessages = [
			$this->makeMessage(100, '<id-100@example.com>', "Message-ID: <id-100@example.com>\r\n", "Body 100"),
			$this->makeMessage(200, '<id-200@example.com>', "Message-ID: <id-200@example.com>\r\n", "Body 200"),
		];

		$Source = $this->makeSourceWithMessages($aMessages, 'Processed');

		$Source->GetListing();
		$Result = $Source->DeleteMessage(1);

		$this->assertTrue($Result);
		$this->assertTrue($aMessages[1]->wasDeleted());
		$this->assertFalse($aMessages[0]->wasDeleted());
	}

	public function testMoveMessageUsesIndexedUidFromListing(): void
	{
		$sMessages = [
			$this->makeMessage(100, '<id-100@example.com>', "Message-ID: <id-100@example.com>\r\n", "Body 100"),
			$this->makeMessage(200, '<id-200@example.com>', "Message-ID: <id-200@example.com>\r\n", "Body 200"),
		];

		$oSource = $this->makeSourceWithMessages($sMessages, 'Processed');

		$oSource->GetListing();
		$bResult = $oSource->MoveMessage(0);

		$this->assertTrue($bResult);
		$this->assertEquals('Processed', $sMessages[0]->copiedTo());
		$this->assertTrue($sMessages[0]->wasDeleted());
	}

	private function makeSourceWithMessages(array $aMessages, string $sTargetFolder): IMAPEmailSource
	{
		$oFolder = new FakeFolder('inbox', messages: $aMessages);
		$oMailbox = new FakeMailbox(folders: [$oFolder]);

		$oRef = new ReflectionClass(IMAPEmailSource::class);
		$oSource = $oRef->newInstanceWithoutConstructor();

		$this->setProperty($oSource, 'sServer', 'test-server');
		$this->setProperty($oSource, 'sLogin', 'test-user');
		$this->setProperty($oSource, 'sMailbox', '');
		$this->setProperty($oSource, 'sTargetFolder', $sTargetFolder);
		$this->setProperty($oSource, 'oMailbox', $oMailbox);
		$this->setProperty($oSource, 'oFolder', null);
		$this->setProperty($oSource, 'bMessagesDeleted', false);
		$this->setProperty($oSource, 'aIndexedUids', []);

		return $oSource;
	}

	private function makeMessage(int $iUid, string $sMessageId, string $sHead, string $sBody): TestImapMessage
	{
		return new TestImapMessage($iUid, $sMessageId, $sHead, $sBody);
	}

	private function setProperty(object $oObj, string $sName, mixed $value): void
	{
		$prop = new ReflectionProperty($oObj, $sName);
		$prop->setAccessible(true);
		$prop->setValue($oObj, $value);
	}

	private function getProtectedProperty(object $oObj, string $sName): mixed
	{
		$prop = new ReflectionProperty($oObj, $sName);
		$prop->setAccessible(true);
		return $prop->getValue($oObj);
	}

	private function setUseMessageIdAsUid(bool $bUseMessageIdAsUid): void
	{
		$this->oConfig->SetModuleSetting('combodo-email-synchro', 'use_message_id_as_uid', $bUseMessageIdAsUid);
	}
}
