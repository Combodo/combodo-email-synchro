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
	'Class:MailInboxBase/Attribute:mailbox' => 'Папка (для IMAP)',
	'Class:MailInboxBase/Attribute:login' => 'Логин',
	'Class:MailInboxBase/Attribute:password' => 'Пароль',
	'Class:MailInboxBase/Attribute:protocol' => 'Протокол',
	'Class:MailInboxBase/Attribute:protocol/Value:pop3' => 'POP3',
	'Class:MailInboxBase/Attribute:protocol/Value:imap' => 'IMAP',
	'Class:MailInboxBase/Attribute:port' => 'Порт',
	'Class:MailInboxBase/Attribute:active' => 'Включён',
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
