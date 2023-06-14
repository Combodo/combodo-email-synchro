<?php
// Copyright (C) 2010-2013 Combodo SARL
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
Dict::Add('DE DE', 'German', 'Deutsch', array(
	// Dictionary entries go here
	'Class:MailInboxBase' => 'Posteingang',
	'Class:MailInboxBase+' => 'Quelle der eingehenden EMails',
	'Class:MailInboxBase/Attribute:server' => 'Mail Server',
	'Class:MailInboxBase/Attribute:server+' => 'The IP address or fully qualified hostname of the mail server~~',
	'Class:MailInboxBase/Attribute:mailbox' => 'Mailbox (für IMAP)',
	'Class:MailInboxBase/Attribute:mailbox+' => 'The IMAP mailbox (folder) to scan for incoming messages. If omitted the default (root) mailbox will be scanned~~',
	'Class:MailInboxBase/Attribute:login' => 'Login',
	'Class:MailInboxBase/Attribute:login+' => 'The name of the mail account used for connecting to the mailbox~~',
	'Class:MailInboxBase/Attribute:password' => 'Passwort',
	'Class:MailInboxBase/Attribute:protocol' => 'Protokol',
	'Class:MailInboxBase/Attribute:protocol+' => 'Warning, from iTop 3.1, POP3 is no more guaranteed~~',
	'Class:MailInboxBase/Attribute:protocol/Value:pop3' => 'POP3',
	'Class:MailInboxBase/Attribute:protocol/Value:imap' => 'IMAP',
	'Class:MailInboxBase/Attribute:port' => 'Port',
	'Class:MailInboxBase/Attribute:port+' => '143 (secured: 993) for IMAP and 110 (secured: 995) for POP3~~',
	'Class:MailInboxBase/Attribute:active' => 'Aktiv',
	'Class:MailInboxBase/Attribute:active+' => 'If set to “Yes”, the inbox will be polled. Otherwise no~~',
	'Class:MailInboxBase/Attribute:active/Value:yes' => 'Ja',
	'Class:MailInboxBase/Attribute:active/Value:no' => 'Nein',
	'MailInbox:MailboxContent' => 'Mailbox Inhalt',
	'MailInbox:MailboxContent:ConfirmMessage' => 'Sind Sie sicher?',
	'MailInbox:EmptyMailbox' => 'No message to display~~',
	'MailInbox:Z_DisplayedThereAre_X_Msg_Y_NewInTheMailbox' => '%1$d eMails displayed. There are %2$d email(s) in the mailbox : %3$d new (including %4$d unreadable), %5$d processed.~~',
	'MailInbox:MaxAllowedPacketTooSmall' => 'Der MySQL-Parameter max_allowed_packet in "my.ini" ist zu klein: %1$s. Der empfohlene Wert ist mindestens: %2$s',
	'MailInbox:Status' => 'Status',
	'MailInbox:Subject' => 'Betreff',
	'MailInbox:From' => 'Von',
	'MailInbox:Date' => 'Datum',
	'MailInbox:RelatedTicket' => 'dazugehöriges Ticket',
	'MailInbox:ErrorMessage' => 'Fehlermeldung',
	'MailInbox:Status/Processed' => 'bereits abgearbeitet',
	'MailInbox:Status/New' => 'Neu',
	'MailInbox:Status/Error' => 'Fehler',
	'MailInbox:Status/Undesired' => 'Unerwünscht',
	'MailInbox:Status/Ignored' => 'Ignoriert',
	'MailInbox:Login/ServerMustBeUnique' => 'Diese Kombination aus Login (%1$s) und Server (%2$s) ist bereits für einen anderen Posteingang konfiguriert.',
	'MailInbox:Login/Server/MailboxMustBeUnique' => 'Diese Kombination aus Login (%1$s), Server (%2$s) und Mailbox (%3$s) ist bereits für eine anderen Posteingang konfiguriert.',
	'MailInbox:Display_X_eMailsStartingFrom_Y' => 'Anzeige von %1$s EMail(s), beginnend von %2$s.',
	'MailInbox:WithSelectedDo' => 'Für die ausgewählten EMails: ',
	'MailInbox:ResetStatus' => 'Status zurücksetzen',
	'MailInbox:DeleteMessage' => 'Löschen',
	'MailInbox:IgnoreMessage' => 'EMail ignorieren',
	'MailInbox:MessageDetails' => 'Nachrichtendetails',
	'MailInbox:DownloadEml' => 'EML-Datei herunterladen',
	'Class:TriggerOnMailUpdate' => 'Trigger (beim Mail-Update)',
	'Class:TriggerOnMailUpdate+' => '',
));
