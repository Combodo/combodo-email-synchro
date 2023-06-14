<?php
/**
 * Localized data
 *
 * @author      Vladimir Kunin <v.b.kunin@gmail.com>
 * @link        http://community.itop-itsm.ru  iTop Russian Community
 * @link        https://github.com/itop-itsm-ru/itop-rus
 * @license     http://www.opensource.org/licenses/gpl-3.0.html LGPL
 */
Dict::Add('RU RU', 'Russian', 'Русский', array(
	// Dictionary entries go here
	'Class:MailInboxBase' => 'Почтовый ящик',
	'Class:MailInboxBase+' => 'Источник входящих сообщений электронной почты',
	'Class:MailInboxBase/Attribute:server' => 'Сервер',
	'Class:MailInboxBase/Attribute:server+' => 'The IP address or fully qualified hostname of the mail server~~',
	'Class:MailInboxBase/Attribute:mailbox' => 'Папка (для IMAP)',
	'Class:MailInboxBase/Attribute:mailbox+' => 'The IMAP mailbox (folder) to scan for incoming messages. If omitted the default (root) mailbox will be scanned~~',
	'Class:MailInboxBase/Attribute:login' => 'Логин',
	'Class:MailInboxBase/Attribute:login+' => 'The name of the mail account used for connecting to the mailbox~~',
	'Class:MailInboxBase/Attribute:password' => 'Пароль',
	'Class:MailInboxBase/Attribute:protocol' => 'Протокол',
	'Class:MailInboxBase/Attribute:protocol+' => 'Warning, from iTop 3.1, POP3 is no more guaranteed~~',
	'Class:MailInboxBase/Attribute:protocol/Value:pop3' => 'POP3',
	'Class:MailInboxBase/Attribute:protocol/Value:imap' => 'IMAP',
	'Class:MailInboxBase/Attribute:port' => 'Порт',
	'Class:MailInboxBase/Attribute:port+' => '143 (secured: 993) for IMAP and 110 (secured: 995) for POP3~~',
	'Class:MailInboxBase/Attribute:active' => 'Включён',
	'Class:MailInboxBase/Attribute:active+' => 'If set to “Yes”, the inbox will be polled. Otherwise no~~',
	'Class:MailInboxBase/Attribute:active/Value:yes' => 'Да',
	'Class:MailInboxBase/Attribute:active/Value:no' => 'Нет',
	'MailInbox:MailboxContent' => 'Содержимое ящика',
	'MailInbox:MailboxContent:ConfirmMessage' => 'Are you sure ?~~',
	'MailInbox:EmptyMailbox' => 'No message to display~~',
	'MailInbox:Z_DisplayedThereAre_X_Msg_Y_NewInTheMailbox' => '%1$d eMails displayed. There are %2$d email(s) in the mailbox : %3$d new (including %4$d unreadable), %5$d processed.~~',
	'MailInbox:MaxAllowedPacketTooSmall' => 'MySQL parameter max_allowed_packet in "my.ini" is too small: %1$s. The recommended value is at least: %2$s~~',
	'MailInbox:Status' => 'Статус',
	'MailInbox:Subject' => 'Тема',
	'MailInbox:From' => 'От',
	'MailInbox:Date' => 'Дата',
	'MailInbox:RelatedTicket' => 'Связанный тикет',
	'MailInbox:ErrorMessage' => 'Ошибка',
	'MailInbox:Status/Processed' => 'Обработано',
	'MailInbox:Status/New' => 'Новое',
	'MailInbox:Status/Error' => 'Ошибка',
	'MailInbox:Status/Undesired' => 'Undesired~~',
	'MailInbox:Status/Ignored' => 'Ignored~~',
	'MailInbox:Login/ServerMustBeUnique' => 'Эта комбинация Логина (%1$s) и Сервера (%2$s) уже используется для другого Почтового ящика.',
	'MailInbox:Login/Server/MailboxMustBeUnique' => 'Эта комбинация Логина (%1$s), Сервера (%2$s) и Папки (%3$s) уже используется для другого Почтового ящика',
	'MailInbox:Display_X_eMailsStartingFrom_Y' => 'Показать %1$s сообщений, начиная с %2$s.',
	'MailInbox:WithSelectedDo' => 'С выбранными сообщениями: ',
	'MailInbox:ResetStatus' => 'Сбросить статус',
	'MailInbox:DeleteMessage' => 'Удалить',
	'MailInbox:IgnoreMessage' => 'Ignore email~~',
	'MailInbox:MessageDetails' => 'Message details~~',
	'MailInbox:DownloadEml' => 'Download eml file~~',
	'Class:TriggerOnMailUpdate' => 'Trigger (when updated by mail)~~',
	'Class:TriggerOnMailUpdate+' => 'Trigger activated when a ticket is updated by processing an incoming email~~',
));
