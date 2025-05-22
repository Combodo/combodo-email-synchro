<?php
/**
 * Localized data
 *
 * @copyright Copyright (C) 2010-2024 Combodo SAS
 * @license    https://opensource.org/licenses/AGPL-3.0
 * 
 */
/**
 *
 */
Dict::Add('NL NL', 'Dutch', 'Nederlands', [
	'Class:MailInboxBase' => 'Mail Inbox~~',
	'Class:MailInboxBase+' => 'Source of incoming eMails~~',
	'Class:MailInboxBase/Attribute:active' => 'Active~~',
	'Class:MailInboxBase/Attribute:active+' => 'If set to “Yes”, the inbox will be polled. Otherwise no~~',
	'Class:MailInboxBase/Attribute:active/Value:no' => 'No~~',
	'Class:MailInboxBase/Attribute:active/Value:yes' => 'Yes~~',
	'Class:MailInboxBase/Attribute:login' => 'Login~~',
	'Class:MailInboxBase/Attribute:login+' => 'The name of the mail account used for connecting to the mailbox~~',
	'Class:MailInboxBase/Attribute:mailbox' => 'Mailbox (for IMAP)~~',
	'Class:MailInboxBase/Attribute:mailbox+' => 'The IMAP mailbox (folder) to scan for incoming messages. If omitted the default (root) mailbox will be scanned~~',
	'Class:MailInboxBase/Attribute:password' => 'Password~~',
	'Class:MailInboxBase/Attribute:port' => 'Port~~',
	'Class:MailInboxBase/Attribute:port+' => '143 (secured: 993) for IMAP and 110 (secured: 995) for POP3~~',
	'Class:MailInboxBase/Attribute:protocol' => 'Protocol~~',
	'Class:MailInboxBase/Attribute:protocol+' => 'Warning, from iTop 3.1, POP3 is no more guaranteed~~',
	'Class:MailInboxBase/Attribute:protocol/Value:imap' => 'IMAP~~',
	'Class:MailInboxBase/Attribute:protocol/Value:pop3' => 'POP3~~',
	'Class:MailInboxBase/Attribute:server' => 'Mail Server~~',
	'Class:MailInboxBase/Attribute:server+' => 'The IP address or fully qualified hostname of the mail server~~',
	'Class:TriggerOnMailUpdate' => 'Trigger (when updated by mail)~~',
	'Class:TriggerOnMailUpdate+' => 'Trigger activated when a ticket is updated by processing an incoming email~~',
	'MailInbox:Date' => 'Date~~',
	'MailInbox:DeleteMessage' => 'Delete email~~',
	'MailInbox:Display_X_eMailsStartingFrom_Y' => 'Display %1$s eMail(s), starting from %2$s.~~',
	'MailInbox:DownloadEml' => 'Download eml file~~',
	'MailInbox:EmptyMailbox' => 'No message to display~~',
	'MailInbox:ErrorMessage' => 'Error Message~~',
	'MailInbox:From' => 'From~~',
	'MailInbox:IgnoreMessage' => 'Ignore email~~',
	'MailInbox:Login/Server/MailboxMustBeUnique' => 'The combination Login (%1$s), Server (%2$s) and Mailbox (%3$s) is already configured for another Mail Inbox~~',
	'MailInbox:Login/ServerMustBeUnique' => 'The combination Login (%1$s) and Server (%2$s) is already configured for another Mail Inbox.~~',
	'MailInbox:MailboxContent' => 'Mailbox Content~~',
	'MailInbox:MailboxContent:ConfirmMessage' => 'Are you sure ?~~',
	'MailInbox:MaxAllowedPacketTooSmall' => 'MySQL parameter max_allowed_packet in "my.ini" is too small: %1$s. The recommended value is at least: %2$s~~',
	'MailInbox:MessageDetails' => 'Message details~~',
	'MailInbox:RelatedTicket' => 'Related Ticket~~',
	'MailInbox:ResetStatus' => 'Reset status~~',
	'MailInbox:Status' => 'Status~~',
	'MailInbox:Status/Error' => 'Error~~',
	'MailInbox:Status/Ignored' => 'Ignored~~',
	'MailInbox:Status/New' => 'New~~',
	'MailInbox:Status/Processed' => 'Already Processed~~',
	'MailInbox:Status/Undesired' => 'Undesired~~',
	'MailInbox:Subject' => 'Subject~~',
	'MailInbox:WithSelectedDo' => 'With the selected emails: ~~',
	'MailInbox:Z_DisplayedThereAre_X_Msg_Y_NewInTheMailbox' => '%1$d eMails displayed. There are %2$d email(s) in the mailbox : %3$d new (including %4$d unreadable), %5$d processed.~~',
	'MailInboxProcessor:MessageTooBig_Size_MaxSize' => 'Message too big: %1$s (maximum allowed size %2$s)~~',
]);
