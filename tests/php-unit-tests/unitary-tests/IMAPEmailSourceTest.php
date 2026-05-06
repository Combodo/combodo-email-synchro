<?php

/*
 * @copyright   Copyright (C) 2010-2026 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\CombodoEmailSynchro\Test\UnitTest\Unitary;

use Combodo\iTop\Extension\EmailSynchro\Service\IMAPEmailSource;
use Combodo\iTop\Test\UnitTest\ItopTestCase;
use DirectoryTree\ImapEngine\FolderInterface;
use DirectoryTree\ImapEngine\FolderRepositoryInterface;
use DirectoryTree\ImapEngine\MailboxInterface;
use DirectoryTree\ImapEngine\Testing\FakeFolder;
use DirectoryTree\ImapEngine\Testing\FakeMailbox;
use Exception;
use MessageFromMailbox;
use ReflectionClass;
use ReflectionProperty;
use utils;

class IMAPEmailSourceTest extends ItopTestCase
{
	private $oConfig;
	private $bOriginalUseMessageIdAsUid;
	private const SERVER_HOST = 'imap.example.com';

	protected function setUp(): void
	{
		parent::setUp();
		$this->oConfig = utils::GetConfig();
		$this->bOriginalUseMessageIdAsUid = $this->oConfig->GetModuleSetting('combodo-email-synchro', 'use_message_id_as_uid', false);

		$this->RequireOnceItopFile('env-'.utils::GetCurrentEnvironment().'/combodo-email-synchro/classes/autoload.php');
		$this->RequireOnceItopFile('env-'.utils::GetCurrentEnvironment().'/combodo-email-synchro/vendor/autoload.php');
		$this->RequireOnceItopFile('env-'.utils::GetCurrentEnvironment().'/combodo-email-synchro/tests/php-unit-tests/unitary-tests/classes/TestImapMessage.php');

	}

	protected function tearDown(): void
	{
		parent::tearDown();
		$this->setUseMessageIdAsUid($this->bOriginalUseMessageIdAsUid);
	}
	protected function LoadRequiredItopFiles(): void
	{
		parent::LoadRequiredItopFiles();
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

	/**
	 * When the mailbox field is empty, GetFolder() must return the server's INBOX and must never call folders()->find().
	 *
	 * @covers \Combodo\iTop\Extension\EmailSynchro\Service\IMAPEmailSource::GetFolder
	 * @since N°9563
	 */
	public function testGetFolderReturnsDefaultInboxWhenMailboxFieldIsEmpty(): void
	{
		$oMockFolder = $this->createMock(FolderInterface::class);

		$oMockMailbox = $this->createMock(MailboxInterface::class);
		$oMockMailbox->expects($this->once())->method('inbox')->willReturn($oMockFolder);
		$oMockMailbox->expects($this->never())->method('folders');

		$oSource = $this->MakeSource('', $oMockMailbox);

		$this->assertSame($oMockFolder, $oSource->GetFolder());
	}

	/**
	 * When the mailbox field is set and the folder exists on the server, GetFolder() must return it and must never call inbox().
	 *
	 * @covers \Combodo\iTop\Extension\EmailSynchro\Service\IMAPEmailSource::GetFolder
	 * @since N°9563
	 */
	public function testGetFolderReturnsFolderWhenMailboxFieldIsValid(): void
	{
		$sValidFolderPath = 'INBOX/valid-folder';

		$oMockFolder = $this->createMock(FolderInterface::class);

		$oMockFolders = $this->createMock(FolderRepositoryInterface::class);
		$oMockFolders->expects($this->once())
			->method('find')
			->with($sValidFolderPath)
			->willReturn($oMockFolder);

		$oMockMailbox = $this->createMock(MailboxInterface::class);
		$oMockMailbox->expects($this->never())->method('inbox');
		$oMockMailbox->expects($this->once())->method('folders')->willReturn($oMockFolders);

		$oSource = $this->MakeSource($sValidFolderPath, $oMockMailbox);

		$this->assertSame($oMockFolder, $oSource->GetFolder());
	}

	/**
	 * When the mailbox field is set but the folder does not exist on the server, GetFolder() must throw an Exception with the folder name and server in its message,
	 * so the caller (and ultimately the user) gets an actionable error.
	 *
	 * This is the non regression test for the fatal "Call to a member function status() on null" that occurred when an invalid mailbox folder was configured.
	 *
	 * @covers \Combodo\iTop\Extension\EmailSynchro\Service\IMAPEmailSource::GetFolder
	 * @since N°9563
	 */
	public function testGetFolderThrowsWhenMailboxFolderDoesNotExist(): void
	{
		$sInvalidFolderPath = 'invalid-folder';

		$oMockFolders = $this->createMock(FolderRepositoryInterface::class);
		$oMockFolders->method('find')->with($sInvalidFolderPath)->willReturn(null);

		$oMockMailbox = $this->createMock(MailboxInterface::class);
		$oMockMailbox->method('folders')->willReturn($oMockFolders);

		$oSource = $this->MakeSource($sInvalidFolderPath, $oMockMailbox);

		$this->expectException(Exception::class);
		$this->expectExceptionMessageMatches('/'.$sInvalidFolderPath.'/');
		$this->expectExceptionMessageMatches('/'.preg_quote(self::SERVER_HOST, '/').'/');

		$oSource->GetFolder();
	}

	/**
	 * GetFolder() must cache the FolderInterface after the first call. Subsequent calls must return the same instance without hitting the IMAP server again.
	 *
	 * @covers \Combodo\iTop\Extension\EmailSynchro\Service\IMAPEmailSource::GetFolder
	 * @since N°9563
	 */
	public function testGetFolderCachesFolderAfterFirstCall(): void
	{
		$oMockFolder = $this->createMock(FolderInterface::class);

		$oMockMailbox = $this->createMock(MailboxInterface::class);
		// inbox() must be called exactly once regardless of how many times GetFolder() is called
		$oMockMailbox->expects($this->once())->method('inbox')->willReturn($oMockFolder);

		$oSource = $this->MakeSource('', $oMockMailbox);

		$oFirstResult  = $oSource->GetFolder();
		$oSecondResult = $oSource->GetFolder();
		$oThirdResult  = $oSource->GetFolder();

		$this->assertSame($oFirstResult, $oSecondResult);
		$this->assertSame($oFirstResult, $oThirdResult);
	}

	/**
	 * Creates an IMAPEmailSource instance without going through the constructor (which requires a real IMAP connection), and injects the given dependencies.
	 */
	private function MakeSource(string $sMailbox, MailboxInterface $oMockMailbox): IMAPEmailSource
	{
		$oSource = (new ReflectionClass(IMAPEmailSource::class))->newInstanceWithoutConstructor();

		$this->SetNonPublicProperty($oSource, 'sServer', self::SERVER_HOST);
		$this->SetNonPublicProperty($oSource, 'sMailbox', $sMailbox);
		$this->SetNonPublicProperty($oSource, 'oFolder', null);
		$this->SetNonPublicProperty($oSource, 'bMessagesDeleted', false);
		$this->SetNonPublicProperty($oSource, 'oMailbox', $oMockMailbox);

		return $oSource;
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
