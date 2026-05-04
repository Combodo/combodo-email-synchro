<?php

/*
 * @copyright   Copyright (C) 2010-2025 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\CombodoEmailSynchro\Test\UnitTest\Unitary;

use Combodo\iTop\Extension\EmailSynchro\Service\IMAPEmailSource;
use Combodo\iTop\Test\UnitTest\ItopTestCase;
use DirectoryTree\ImapEngine\FolderInterface;
use DirectoryTree\ImapEngine\FolderRepositoryInterface;
use DirectoryTree\ImapEngine\MailboxInterface;
use Exception;
use ReflectionClass;

/**
 * @covers \Combodo\iTop\Extension\EmailSynchro\Service\IMAPEmailSource
 */
class IMAPEmailSourceTest extends ItopTestCase
{
	private const SERVER_HOST = 'imap.example.com';

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
}
