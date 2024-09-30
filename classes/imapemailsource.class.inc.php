<?php
// Copyright (C) 2012-2016 Combodo SARL
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
 * @copyright   Copyright (C) 2012-2024 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

/**
 * Read messages from an IMAP mailbox using PHP's IMAP extension
 * Note: in theory PHP IMAP methods can also be used to connect to
 *       a POP3 mailbox, but in practice the missing emulation of
 *       actual unique identifiers (UIDLs) for the messages makes
 *       this unusable for our particular purpose
 */
class IMAPEmailSource extends EmailSource
{
	protected $rImapConn = null;
	protected $sServer = '';
	protected $sLogin = '';
	protected $sMailbox = '';
	protected $sTargetFolder = '';

	// Keep parameters on separate lines for PHP7.4
	public function __construct(
		$sServer,
		$iPort,
		$sLogin,
		#[\SensitiveParameter]
		$sPwd,
		$sMailbox,
		$aOptions,
		$sTargetFolder = '')
	{
		parent::__construct();
		$this->sLastErrorSubject = '';
		$this->sLastErrorMessage = '';
		$this->sServer = $sServer;
		$this->sLogin = $sLogin;
		$this->sMailbox = $sMailbox;
		$this->sTargetFolder = $sTargetFolder;

		$sOptions = '';
		if (count($aOptions) > 0)
		{
			$sOptions = '/'.implode('/',$aOptions);
		}
		
		if (!function_exists('imap_open')) throw new Exception('The imap_open function is missing. Did you forget to install the PHP module "IMAP" on the server?');

		$aImapOpenOptions = MetaModel::GetModuleSetting('combodo-email-synchro', 'imap_open_options', []);
		$sIMAPConnStr = "{{$sServer}:{$iPort}$sOptions}$sMailbox";
		$this->rImapConn = imap_open($sIMAPConnStr, $sLogin, $sPwd, 0, 0, $aImapOpenOptions );
		if ($this->rImapConn === false)
		{
			if (class_exists('EventHealthIssue'))
			{
				EventHealthIssue::LogHealthIssue('combodo-email-synchro', "Cannot connect to IMAP server: '$sIMAPConnStr', with credentials: '$sLogin'/***");
			}
			$sMessage = "Cannot connect to IMAP server: '$sIMAPConnStr', with credentials: '$sLogin'/***'";
			IssueLog::Error($sMessage.' '.var_export(imap_errors(), true));
			throw new Exception($sMessage);
		}
	}	

	/**
	 * Get the number of messages to process
	 * @return integer The number of available messages
	 */
	public function GetMessagesCount()
	{
		$oInfo = imap_check($this->rImapConn);
		if ($oInfo !== false) return $oInfo->Nmsgs;
		
		return 0;	
	}
	
	/**
	 * Retrieves the message of the given index [0..Count]
	 * @param $index integer The index between zero and count
	 * @return \MessageFromMailbox
	 */
	public function GetMessage($index)
	{
		$aOverviews = imap_fetch_overview($this->rImapConn, 1+$index);
		$oOverview = array_pop($aOverviews);
		if (($this->GetMaxMessageSize() > 0) && ($oOverview->size > $this->GetMaxMessageSize()))
		{
			$sMessage = "Message #$index is ".$oOverview->size." bytes, whereas the configured limit is ".$this->GetMaxMessageSize()." bytes";
			throw new EmailBiggerThanMaxMessageSizeException($sMessage, $oOverview->size);
		}
		$sRawHeaders = imap_fetchheader($this->rImapConn, 1+$index);
		$sBody = imap_body($this->rImapConn, 1+$index, FT_PEEK);
		$bUseMessageId = static::UseMessageIdAsUid();
		if ($bUseMessageId)
		{
			$oOverview->uid = $oOverview->message_id;
		}

		return new MessageFromMailbox($oOverview->uid, $sRawHeaders, $sBody);
	}

	/**
	 * Deletes the message of the given index [0..Count] from the mailbox
	 * @param $index integer The index between zero and count
	 */
	public function DeleteMessage($index)
	{
		$ret = imap_delete($this->rImapConn, (1+$index).':'.(1+$index));
		return $ret;
	}

	/**
	 * Move the message of the given index [0..Count] from the mailbox to another folder
	 * @param $index integer The index between zero and count
	 */
	public function MoveMessage($index)
	{
		$ret = imap_mail_move($this->rImapConn, (1+$index).':'.(1+$index), $this->sTargetFolder);
		if (!$ret){
			print_r(imap_errors());
			throw new Exception("Error : Cannot move message to folder ".$this->sTargetFolder);
		}

		return $ret;
	}

	/**
	 * Name of the eMail source
	 */
	public function GetName()
	{
		return $this->sLogin;
	}

	public function GetSourceId()
	{
		return $this->sServer.'/'.$this->sLogin;
	}

	/**
	 * Mailbox path of the eMail source
	 */
	public function GetMailbox()
	{
		return $this->sMailbox;
	}

	 public function GetListing()
	 {
		 $ret = null;

		 $oInfo = imap_check($this->rImapConn);
		 if (($oInfo !== false) && ($oInfo->Nmsgs > 0)) {
			 $sRange = "1:".$oInfo->Nmsgs;
			 $bUseMessageId = static::UseMessageIdAsUid();

			 $ret = array();
			 $aResponse = imap_fetch_overview($this->rImapConn, $sRange);

			 foreach ($aResponse as $aMessage) {
				 if ($bUseMessageId) {
					 $ret[] = array('msg_id' => $aMessage->msgno, 'uidl' => $aMessage->message_id);
				 } else {
					 $ret[] = array('msg_id' => $aMessage->msgno, 'uidl' => $aMessage->uid);
				 }
			 }
		 }
        
		return $ret;
	 }
	 
	 public function Disconnect()
	 {
	 	imap_close($this->rImapConn, CL_EXPUNGE);
	 	$this->rImapConn = null; // Just to be sure
	 }
}