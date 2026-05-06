<?php

/*
 * @copyright   Copyright (C) 2010-2026 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\CombodoEmailSynchro\Test\UnitTest\Unitary;

use DirectoryTree\ImapEngine\Testing\FakeMessage;

class TestImapMessage extends FakeMessage
{
	private string $messageId;
	private string $head;
	private string $body;
	private array $copyTargets = [];
	private bool $deleted = false;

	public function __construct(int $uid, string $messageId, string $head, string $body)
	{
		parent::__construct($uid, [], $head."\r\n".$body);
		$this->messageId = $messageId;
		$this->head = $head;
		$this->body = $body;
	}

	public function messageId(): ?string
	{
		return $this->messageId;
	}

	public function head(): string
	{
		return $this->head;
	}

	public function body(): string
	{
		return $this->body;
	}

	public function copy(string $folder): void
	{
		$this->copyTargets[] = $folder;
	}

	public function delete(bool $expunge = false): void
	{
		$this->deleted = true;
	}

	public function wasDeleted(): bool
	{
		return $this->deleted;
	}

	public function copiedTo(): ?string
	{
		return $this->copyTargets[0] ?? null;
	}
}
