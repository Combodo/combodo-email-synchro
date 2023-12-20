<?php
// Copyright (C) 2016-2023 Combodo SARL
//
//   This program is free software; you can redistribute it and/or modify
//   it under the terms of the GNU Lesser General Public License as published by
//   the Free Software Foundation; version 3 of the License.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of the GNU General Public License
//   along with this program; if not, write to the Free Software
//   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
/**
 * @copyright   Copyright (C) 2016 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

/**
 * Exception triggered when encoutering messages too big to be read
 */
class EmailBiggerThanMaxMessageSizeException extends Exception
{
	/**
	 * @var int
	 */
	protected $iMessageSize;

	/**
	 *
	 * @inheritDoc
	 */
	public function __construct($message = null, $iMessageSize = 0, $code = null, $previous = null)
	{
		parent::__construct($message, $code, $previous);
		$this->iMessageSize = $iMessageSize;
	}
	
	public function GetMessageSize()
	{
		return $this->iMessageSize;
	}
}

/**
 * A source of messages either POP3, IMAP or File...
 */
abstract class EmailSource
{
	protected $sLastErrorSubject;
	protected $sLastErrorMessage;
	protected $sPartsOrder;
	protected $token;
	/**
	 * Maximum size for reading messages
	 * Messages larger than this value will not be read (will cause an EmailBiggerThanMaxMessageSizeException)
	 * @var int|float
	 */
	protected $maxMessageSize;
	
	public function __construct()
	{
		$this->sPartsOrder = 'text/plain,text/html'; // Default value can be changed via SetPartsOrder
		$this->token  =null;
		$this->maxMessageSize = 0;
	}
	
	/**
	 * Get the number of messages to process
	 * @return integer The number of available messages
	 */
	abstract public function GetMessagesCount();
	
	/**
	 * Retrieves the message of the given index [0..Count]
	 * @param $index integer The index between zero and count
	 * @return MessageFromMailbox
	 */
	abstract public function GetMessage($index);

	/**
	 * Deletes the message of the given index [0..Count] from the mailbox
	 *
	 * @param $index integer The index between zero and count
	 */
	abstract public function DeleteMessage($index);

	/**
	 * Move the message of the given index [0..Count] from the mailbox to another folder
	 *
	 * @param $index integer The index between zero and count
	 */
	public function MoveMessage($index)
	{
		// Do nothing !
		return false;
	}

	/**
	 * Name of the eMail source
	 */
	abstract public function GetName();

	/**
	 * This impl is bad, but it will lower the risk for children classes in extensions !
	 *
	 * @return string something to identify the source in a log
	 *                this is useful as for example EmailBackgroundProcess is working on this class and not persisted mailboxes ({@link \MailInboxBase})
	 * @since 3.6.1 N°5633 method creation
	 */
	public function GetSourceId()
	{
		return $this->token;
	}

	/**
	 * Mailbox path of the eMail source
	 */
	public function GetMailbox()
	{
		return '';
	}

	/**
	 * Get the list (with their IDs) of all the messages
	 *
	 * @return array{msg_id: int, uidl: ?string} 'msg_id' => index, 'uidl' => message identifier (null if message cannot be decoded)
	 */
	abstract public function GetListing();

	/**
	 * Disconnect from the server
	 */
	abstract public function Disconnect();

	/**
	 * Workaround for some email servers (like gMail!) where the UID may change between two sessions, so let's use the MessageID
	 * as a replacement for the UID !
	 *
	 * Note that it is possible to receive twice a message with the same MessageID, but since the content of the message
	 * will be the same, it's a safe to process such messages only once...
	 *
	 * BEWARE: Make sure that you empty the mailbox before toggling this setting in the config file, since all the messages
	 *    present in the mailbox at the time of the toggle will be considered as "new" and thus processed again.
	 *
	 * @return boolean
	 * @uses `use_message_id_as_uid` config parameter
	 */
	public static function UseMessageIdAsUid()
	{
		return (bool)MetaModel::GetModuleSetting('combodo-email-synchro', 'use_message_id_as_uid', false);
	}

	public function GetLastErrorSubject()
	{
		return $this->sLastErrorSubject;
	}

	public function GetLastErrorMessage()
	{
		return $this->sLastErrorMessage;
	}
	
	/**
	 * Preferred order for retrieving the mail "body" when scanning a multiparts emails
	 * @param $sPartsOrder string A comma separated list of MIME types e.g. text/plain,text/html
	 */
	public function SetPartsOrder($sPartsOrder)
	{
		$this->sPartsOrder = $sPartsOrder;
	}
	/**
	 * Preferred order for retrieving the mail "body" when scanning a multiparts emails
	 * @return string A comma separated list of MIME types e.g. text/plain,text/html
	 */
	public function GetPartsOrder()
	{
		return $this->sPartsOrder;
	}
	/**
	 * Set an opaque reference token for use by the caller...
	 * @param mixed $token
	 */
 	public function SetToken($token)
 	{
 		$this->token = $token;
 	}
 	/**
 	 * Get the reference token set earlier....
 	 * @return mixed The token set by SetToken()
 	 */
 	public function GetToken()
 	{
 		return $this->token;
 	}
 	
 	/**
 	 * Set the maximum size for a message (in byte)
 	 * Messages larger than this value will not be read (will cause an EmailBiggerThanMaxMessageSizeException)
 	 * @param int|float $maxMessageSize Could be an int if we are not on 32-bit system since 2Gb may be too small as a limit one day
 	 */
 	public function SetMaxMessageSize($maxMessageSize)
 	{
 		$this->maxMessageSize = $maxMessageSize;
 	}

 	/**
 	 * Get the maximum size set for reading messages
 	 * @return int|float
 	 */
 	public function GetMaxMessageSize()
 	{
 		return $this->maxMessageSize;
 	}
}
