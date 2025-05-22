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
Dict::Add('DE DE', 'German', 'Deutsch', [
	'Class:MailInboxBase' => 'Posteingang',
	'Class:MailInboxBase+' => 'Quelle der eingehenden EMails',
	'Class:MailInboxBase/Attribute:active' => 'Aktiv',
	'Class:MailInboxBase/Attribute:active+' => 'Wenn diese Option auf "Ja" gesetzt ist, wird der Posteingang abgerufen. Sonst nicht.',
	'Class:MailInboxBase/Attribute:active/Value:no' => 'Nein',
	'Class:MailInboxBase/Attribute:active/Value:yes' => 'Ja',
	'Class:MailInboxBase/Attribute:login' => 'Login',
	'Class:MailInboxBase/Attribute:login+' => 'Der Name des E-Mail-Kontos, das für die Verbindung mit dem Postfach verwendet wird',
	'Class:MailInboxBase/Attribute:mailbox' => 'Mailbox (für IMAP)',
	'Class:MailInboxBase/Attribute:mailbox+' => 'Das IMAP-Postfach (Ordner), das nach eingehenden Nachrichten durchsucht werden soll. Wenn nicht angegeben, wird das Standardpostfach (Stammverzeichnis) durchsucht.',
	'Class:MailInboxBase/Attribute:password' => 'Passwort',
	'Class:MailInboxBase/Attribute:port' => 'Port',
	'Class:MailInboxBase/Attribute:port+' => '143 (verschlüsselt: 993) für IMAP und 110 (verschlüsselt: 995) für POP3',
	'Class:MailInboxBase/Attribute:protocol' => 'Protokol',
	'Class:MailInboxBase/Attribute:protocol+' => 'Achtung: Die FUnktion von POP3 wird ab iTop 3.1 nicht mehr garantiert.',
	'Class:MailInboxBase/Attribute:protocol/Value:imap' => 'IMAP',
	'Class:MailInboxBase/Attribute:protocol/Value:pop3' => 'POP3',
	'Class:MailInboxBase/Attribute:server' => 'Mail Server',
	'Class:MailInboxBase/Attribute:server+' => 'Die IP-Adresse oder der FQDN des Mail-Servers',
	'Class:TriggerOnMailUpdate' => 'Trigger (beim Mail-Update)',
	'Class:TriggerOnMailUpdate+' => 'Trigger bei Aktualisierung eines Tickets per E-Mail',
	'MailInbox:Date' => 'Datum',
	'MailInbox:DeleteMessage' => 'Löschen',
	'MailInbox:Display_X_eMailsStartingFrom_Y' => 'Anzeige von %1$s EMail(s), beginnend von %2$s.',
	'MailInbox:DownloadEml' => 'EML-Datei herunterladen',
	'MailInbox:EmptyMailbox' => 'Keine Nachrichten zur Anzeige vorhanden',
	'MailInbox:ErrorMessage' => 'Fehlermeldung',
	'MailInbox:From' => 'Von',
	'MailInbox:IgnoreMessage' => 'EMail ignorieren',
	'MailInbox:Login/Server/MailboxMustBeUnique' => 'Diese Kombination aus Login (%1$s), Server (%2$s) und Mailbox (%3$s) ist bereits für eine anderen Posteingang konfiguriert.',
	'MailInbox:Login/ServerMustBeUnique' => 'Diese Kombination aus Login (%1$s) und Server (%2$s) ist bereits für einen anderen Posteingang konfiguriert.',
	'MailInbox:MailboxContent' => 'Mailbox Inhalt',
	'MailInbox:MailboxContent:ConfirmMessage' => 'Sind Sie sicher?',
	'MailInbox:MaxAllowedPacketTooSmall' => 'Der MySQL-Parameter max_allowed_packet in "my.ini" ist zu klein: %1$s. Der empfohlene Wert ist mindestens: %2$s',
	'MailInbox:MessageDetails' => 'Nachrichtendetails',
	'MailInbox:RelatedTicket' => 'dazugehöriges Ticket',
	'MailInbox:ResetStatus' => 'Status zurücksetzen',
	'MailInbox:Status' => 'Status',
	'MailInbox:Status/Error' => 'Fehler',
	'MailInbox:Status/Ignored' => 'Ignoriert',
	'MailInbox:Status/New' => 'Neu',
	'MailInbox:Status/Processed' => 'bereits abgearbeitet',
	'MailInbox:Status/Undesired' => 'Unerwünscht',
	'MailInbox:Subject' => 'Betreff',
	'MailInbox:WithSelectedDo' => 'Für die ausgewählten EMails: ',
	'MailInbox:Z_DisplayedThereAre_X_Msg_Y_NewInTheMailbox' => '%1$d E-Mail(s) werden angezeigt. Es befinden sich %2$d E-Mail(s) im Postfach: %3$d neue (davon %4$d unlesbar), %5$d bearbeitete.',
	'MailInboxProcessor:MessageTooBig_Size_MaxSize' => 'Die Nachrichti ist zu groß: %1$s (maximale zulässige Größe %2$s)',
]);
