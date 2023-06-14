<?php
/**
 * Localized data
 *
 * @copyright Copyright (C) 2010-2018 Combodo SARL
 * @license	http://opensource.org/licenses/AGPL-3.0
 *
 * This file is part of iTop.
 *
 * iTop is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * iTop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with iTop. If not, see <http://www.gnu.org/licenses/>
 */
Dict::Add('PL PL', 'Polish', 'Polski', array(
	// Dictionary entries go here
	'Class:MailInboxBase' => 'Mail Inbox~~',
	'Class:MailInboxBase+' => 'Source of incoming eMails~~',
	'Class:MailInboxBase/Attribute:server' => 'Mail Server~~',
	'Class:MailInboxBase/Attribute:server+' => 'The IP address or fully qualified hostname of the mail server~~',
	'Class:MailInboxBase/Attribute:mailbox' => 'Mailbox (for IMAP)~~',
	'Class:MailInboxBase/Attribute:mailbox+' => 'The IMAP mailbox (folder) to scan for incoming messages. If omitted the default (root) mailbox will be scanned~~',
	'Class:MailInboxBase/Attribute:login' => 'Login~~',
	'Class:MailInboxBase/Attribute:login+' => 'The name of the mail account used for connecting to the mailbox~~',
	'Class:MailInboxBase/Attribute:password' => 'Password~~',
	'Class:MailInboxBase/Attribute:protocol' => 'Protocol~~',
	'Class:MailInboxBase/Attribute:protocol+' => 'Warning, from iTop 3.1, POP3 is no more guaranteed~~',
	'Class:MailInboxBase/Attribute:protocol/Value:pop3' => 'POP3~~',
	'Class:MailInboxBase/Attribute:protocol/Value:imap' => 'IMAP~~',
	'Class:MailInboxBase/Attribute:port' => 'Port~~',
	'Class:MailInboxBase/Attribute:port+' => '143 (secured: 993) for IMAP and 110 (secured: 995) for POP3~~',
	'Class:MailInboxBase/Attribute:active' => 'Active~~',
	'Class:MailInboxBase/Attribute:active+' => 'If set to “Yes”, the inbox will be polled. Otherwise no~~',
	'Class:MailInboxBase/Attribute:active/Value:yes' => 'Yes~~',
	'Class:MailInboxBase/Attribute:active/Value:no' => 'No~~',
	'MailInbox:MailboxContent' => 'Mailbox Content~~',
	'MailInbox:MailboxContent:ConfirmMessage' => 'Are you sure ?~~',
	'MailInbox:EmptyMailbox' => 'No message to display~~',
	'MailInbox:Z_DisplayedThereAre_X_Msg_Y_NewInTheMailbox' => '%1$d eMails displayed. There are %2$d email(s) in the mailbox : %3$d new (including %4$d unreadable), %5$d processed.~~',
	'MailInbox:MaxAllowedPacketTooSmall' => 'MySQL parameter max_allowed_packet in "my.ini" is too small: %1$s. The recommended value is at least: %2$s~~',
	'MailInbox:Status' => 'Status~~',
	'MailInbox:Subject' => 'Subject~~',
	'MailInbox:From' => 'From~~',
	'MailInbox:Date' => 'Date~~',
	'MailInbox:RelatedTicket' => 'Related Ticket~~',
	'MailInbox:ErrorMessage' => 'Error Message~~',
	'MailInbox:Status/Processed' => 'Already Processed~~',
	'MailInbox:Status/New' => 'New~~',
	'MailInbox:Status/Error' => 'Error~~',
	'MailInbox:Status/Undesired' => 'Undesired~~',
	'MailInbox:Status/Ignored' => 'Ignored~~',
	'MailInbox:Login/ServerMustBeUnique' => 'The combination Login (%1$s) and Server (%2$s) is already configured for another Mail Inbox.~~',
	'MailInbox:Login/Server/MailboxMustBeUnique' => 'The combination Login (%1$s), Server (%2$s) and Mailbox (%3$s) is already configured for another Mail Inbox~~',
	'MailInbox:Display_X_eMailsStartingFrom_Y' => 'Display %1$s eMail(s), starting from %2$s.~~',
	'MailInbox:WithSelectedDo' => 'With the selected emails: ~~',
	'MailInbox:ResetStatus' => 'Reset status~~',
	'MailInbox:DeleteMessage' => 'Delete email~~',
	'MailInbox:IgnoreMessage' => 'Ignore email~~',
	'MailInbox:MessageDetails' => 'Message details~~',
	'MailInbox:DownloadEml' => 'Download eml file~~',
	'Class:TriggerOnMailUpdate' => 'Trigger (when updated by mail)~~',
	'Class:TriggerOnMailUpdate+' => 'Trigger activated when a ticket is updated by processing an incoming email~~',
));
