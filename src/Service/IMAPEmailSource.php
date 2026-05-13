<?php

namespace Combodo\iTop\Extension\EmailSynchro\Service;

use Combodo\iTop\Extension\EmailSynchro\Helper\EmailHelper;
use Combodo\iTop\Extension\EmailSynchro\Helper\ImapOptionsHelper;
use Dict;
use DirectoryTree\ImapEngine\Enums\ImapFetchIdentifier;
use DirectoryTree\ImapEngine\FolderInterface;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\MailboxInterface;
use EmailSource;
use Exception;
use IssueLog;
use MailInboxBase;
use MessageFromMailbox;

class IMAPEmailSource extends EmailSource
{
	public const LOG_CHANNEL = IMAPEmailLogger::LOG_CHANNEL;
	public const LOG_DEBUG_CLASS = 'IMAPEmailSource';
	public const CONFIG_AUTHENTICATION = 'plain';

	/** LOGIN username @var string */
	protected $sLogin;
	protected $sServer;
	protected $sTargetFolder;
	protected $sMailbox;

	// Access token to use instead of password, if set
	protected ?string $sAccessToken = null;

	private MailboxInterface $oMailbox;
	/**
	 * @var FolderInterface|null
	 */
	private $oFolder;
	private $bMessagesDeleted = false;
	private array $aIndexedUids = [];

	public function __construct(MailInboxBase $oMailbox)
	{
		$sServer = $oMailbox->Get('server');
		$this->sServer = $sServer;
		$sLogin = $oMailbox->Get('login');
		$this->sLogin = $sLogin;
		$sMailbox = $oMailbox->Get('mailbox');
		$this->sMailbox = $sMailbox;
		$iPort = $oMailbox->Get('port');
		$this->sTargetFolder = $oMailbox->Get('target_folder');

		$sPassword = $this->sAccessToken === null ? $oMailbox->Get('password') : $this->sAccessToken;

		IssueLog::Debug("IMAPEmailSource Start for $this->sServer", static::LOG_CHANNEL);
		$oImapOptions = new ImapOptionsHelper();

		$sSSL = match (true) {
			$oImapOptions->HasOption('ssl') => 'ssl',
			$oImapOptions->HasOption('tls') => 'starttls',
			default => null,
		};

		$aOptions = [
			'port' => $iPort,
			'username' => $sLogin,
			'password' => $sPassword,
			'encryption' => $sSSL,
			'authentication' => static::CONFIG_AUTHENTICATION,
			'host' => $sServer,
			'debug' => IMAPEmailLogger::class,
		];

		if ($oImapOptions->HasOption('validate_cert')) {
			IssueLog::Debug("IMAPEmailSource - SSL certificate validation enabled", static::LOG_CHANNEL);
			$aOptions['validate_cert'] = true;
		}

		$this->oMailbox = new Mailbox($aOptions);
		$this->oMailbox->connect();

		// Calls parent with original arguments
		parent::__construct();
	}

	public function GetMessagesCount()
	{
		IssueLog::Debug(static::LOG_DEBUG_CLASS." Start GetMessagesCount for $this->sServer", static::LOG_CHANNEL);
		$iCount = $this->GetFolder()->status()['MESSAGES'] ?? 0;
		IssueLog::Debug(static::LOG_DEBUG_CLASS." $iCount message(s) found for $this->sServer", static::LOG_CHANNEL);

		return $iCount;
	}

	public function GetMessage($index)
	{
		$this->sLastErrorSubject = null;
		$this->sLastErrorMessage = null;
		[$iOffsetIndex, $identifier] = $this->ResolveMessageIdentifier($index);

		IssueLog::Debug(__METHOD__." Start: uid=$iOffsetIndex index=$index for $this->sServer", static::LOG_CHANNEL);
		try {
			$oMessage = $this->GetFolder()
				->messages()
				->withHeaders()
				->withBody()
				->findOrFail($iOffsetIndex, $identifier);

			if (!$oMessage) {
				throw new Exception("Email with UID {$oMessage->uid()} is not found", 0);
			}

			$iMessageSize = strlen($oMessage);
			if ($this->GetMaxMessageSize() > 0 && $iMessageSize > $this->GetMaxMessageSize()) {
				IssueLog::Error("Message #$index is ".$iMessageSize." bytes, whereas the configured limit is ".$this->GetMaxMessageSize()." bytes", static::LOG_CHANNEL, [
					'exception.message' => "Message #$index is ".$iMessageSize." bytes, whereas the configured limit is ".$this->GetMaxMessageSize()." bytes",
					'exception.stack'   => '',
				]);
				$this->sLastErrorSubject = $oMessage->subject() ?? '';
				$sMessageSizeForHumans = EmailHelper::HumanReadableSize($iMessageSize);
				$sMaxSizeForHumans = EmailHelper::HumanReadableSize($this->GetMaxMessageSize());
				$this->sLastErrorMessage = Dict::Format('MailInboxProcessor:MessageTooBig_Size_MaxSize', $sMessageSizeForHumans, $sMaxSizeForHumans);
				return null;
			}
			$sUIDL = static::UseMessageIdAsUid() ? $oMessage->messageId() : $oMessage->uid();
		} catch (Exception $e) {
			IssueLog::Error(__METHOD__." uid=$iOffsetIndex for $this->sServer throws an exception", static::LOG_CHANNEL, [
				'exception.message' => $e->getMessage(),
				'exception.stack'   => $e->getTraceAsString(),
			]);
			$sLastErrorSubject = '';
			$sLastErrorMessage = " uid=$iOffsetIndex for $this->sServer throws an exception";

			return null;
		}
		$oNewMail = new MessageFromMailbox($sUIDL, $oMessage->head(), $oMessage->body());
		IssueLog::Debug(__METHOD__." End: uid=$iOffsetIndex for $this->sServer", static::LOG_CHANNEL);

		return $oNewMail;
	}

	/**
	 * @param $index
	 * @return true|null
	 */
	public function DeleteMessage($index)
	{
		[$iOffsetIndex, $identifier] = $this->ResolveMessageIdentifier($index);

		IssueLog::Debug(__METHOD__." Start: $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);
		try {
			$oMessage = $this->GetFolder()
				->messages()
				->find($iOffsetIndex, $identifier);

			if (!$oMessage) {
				return null;
			}

			$oMessage->delete();
			$this->bMessagesDeleted = true;

		} catch (Exception $e) {
			IssueLog::Error(__METHOD__." $iOffsetIndex for $this->sServer throws an exception", static::LOG_CHANNEL, [
				'exception.message' => $e->getMessage(),
				'exception.stack'   => $e->getTraceAsString(),
			]);

			return null;
		}
		IssueLog::Debug(__METHOD__." End: $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);

		return true;
	}

	public function GetName()
	{
		return $this->sLogin;
	}

	public function GetSourceId()
	{
		return $this->sServer.'/'.$this->sLogin;
	}

	public function GetListing()
	{
		// Indexes the UIDs of the messages to communicate with the IMAP server with UID only (if we can)
		// Internally iTop uses sequence number, we'll use this cache to map both
		$this->aIndexedUids = [];
		$aReturn = [];
		$oMessages = $this->GetFolder()
			->messages()
			->withHeaders()
			->get();

		$iIndex = 1; // We start at 1 to be consistent with IMAP sequence index
		foreach ($oMessages as $oMessage) {
			$iUid = $oMessage->uid();
			$this->aIndexedUids[] = $iUid;
			$aReturn[] = [
				'msg_id' => $iIndex, // msg_id is historically not a messageId, but the sequence index
				'uidl'   => static::UseMessageIdAsUid() ? $oMessage->messageId() : $iUid,
			];
			$iIndex++;
		}
		return $aReturn;
	}

	public function GetFolder()
	{
		// Set the folder if not already done
		if ($this->oFolder === null && $this->sMailbox === '') {
			$this->oFolder = $this->oMailbox->inbox();
		} elseif ($this->oFolder === null) {
			$this->oFolder = $this->oMailbox->folders()->find($this->sMailbox);
		}

		// If we haven't found a real folder yet, throw an error
		if ($this->oFolder === null) {
			throw new Exception("IMAP folder '{$this->sMailbox}' not found on server {$this->sServer}. Please check the 'Mailbox (for IMAP)' field and make sure it follows RFC 3501 (https://www.rfc-editor.org/rfc/rfc3501.html#section-5.1)");
		}

		return $this->oFolder;
	}

	/**
	 * Move the message of the given index [0..Count] from the mailbox to another folder
	 *
	 * @param $index integer The index between zero and count
	 *
	 * @throws \DirectoryTree\ImapEngine\Exceptions\ImapCapabilityException
	 */
	public function MoveMessage($index)
	{
		[$iOffsetIndex, $identifier] = $this->ResolveMessageIdentifier($index);
		IssueLog::Debug(__METHOD__." Start: $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);
		try {
			$oMessage = $this->GetFolder()
				->messages()
				->find($iOffsetIndex, $identifier);

			if (!$oMessage) {
				return false;
			}

			// Use copy+delete instead of move as Gmail won't expunge automatically and break our way of iterating over messages indexes
			$oMessage->copy($this->sTargetFolder);
			$oMessage->delete();
			$this->bMessagesDeleted = true;
		} catch (Exception $e) {
			IssueLog::Error(__METHOD__." $iOffsetIndex for $this->sServer throws an exception", static::LOG_CHANNEL, [
				'exception.message' => $e->getMessage(),
				'exception.stack'   => $e->getTraceAsString(),
			]);

			return false;
		}

		IssueLog::Debug(__METHOD__." End: $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);
		return true;
	}

	public function Disconnect()
	{
		// Expunge deleted messages before disconnecting
		if ($this->bMessagesDeleted) {
			IssueLog::Debug(__METHOD__." Expunging deleted messages for $this->sServer", static::LOG_CHANNEL);
			$this->GetFolder()->expunge();
		}

		$this->oMailbox->disconnect();
	}

	public function GetMailbox()
	{
		return $this->sMailbox;
	}

	/**
	 * Resolve a sequence index as an UID. If none is found in our index, we'll try to use it as a sequence index
	 *
	 * @param int $iSequenceIndex
	 * @return array{0:int,1:ImapFetchIdentifier}
	 */
	private function ResolveMessageIdentifier(int $iSequenceIndex): array
	{
		$iCachedUid = $this->aIndexedUids[$iSequenceIndex] ?? null;
		if ($iCachedUid !== null) {
			return [$iCachedUid, ImapFetchIdentifier::Uid];
		}

		// Our internal API use 0 based sequence number, we make it 1 based to match IMAP RFC 9051 2.3.1.2.https://www.ietf.org/rfc/rfc9051.html#section-2.3.1.2
		return [1 + $iSequenceIndex, ImapFetchIdentifier::MessageNumber];
	}
}
