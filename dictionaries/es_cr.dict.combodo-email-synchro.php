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
Dict::Add('ES CR', 'Spanish', 'Español, Castellano', [
	'Class:MailInboxBase' => 'Buzón de Correo',
	'Class:MailInboxBase+' => 'Fuente de mensajes entrantes',
	'Class:MailInboxBase/Attribute:active' => 'Activo',
	'Class:MailInboxBase/Attribute:active+' => 'If set to “Yes”, the inbox will be polled. Otherwise no~~',
	'Class:MailInboxBase/Attribute:active/Value:no' => 'No',
	'Class:MailInboxBase/Attribute:active/Value:yes' => 'Si',
	'Class:MailInboxBase/Attribute:login' => 'Usuario',
	'Class:MailInboxBase/Attribute:login+' => 'The name of the mail account used for connecting to the mailbox~~',
	'Class:MailInboxBase/Attribute:mailbox' => 'Buzón (para IMAP)',
	'Class:MailInboxBase/Attribute:mailbox+' => 'The IMAP mailbox (folder) to scan for incoming messages. If omitted the default (root) mailbox will be scanned~~',
	'Class:MailInboxBase/Attribute:password' => 'Contraseña',
	'Class:MailInboxBase/Attribute:port' => 'Puerto',
	'Class:MailInboxBase/Attribute:port+' => '143 (secured: 993) for IMAP and 110 (secured: 995) for POP3~~',
	'Class:MailInboxBase/Attribute:protocol' => 'Protocolo',
	'Class:MailInboxBase/Attribute:protocol+' => 'Warning, from iTop 3.1, POP3 is no more guaranteed~~',
	'Class:MailInboxBase/Attribute:protocol/Value:imap' => 'IMAP',
	'Class:MailInboxBase/Attribute:protocol/Value:pop3' => 'POP3',
	'Class:MailInboxBase/Attribute:server' => 'Servidor de Correo',
	'Class:MailInboxBase/Attribute:server+' => 'The IP address or fully qualified hostname of the mail server~~',
	'Class:TriggerOnMailUpdate' => 'Disparador (cuando sea actualizado por mensaje)',
	'Class:TriggerOnMailUpdate+' => 'Disparador activado cuanto un ticket es actualiado por el procesameinto de mensaje entrante',
	'MailInbox:Date' => 'Fecha',
	'MailInbox:DeleteMessage' => 'Borrar mensaje',
	'MailInbox:Display_X_eMailsStartingFrom_Y' => 'Mostrar %1$s mensaje(s), iniciando desde %2$s.',
	'MailInbox:DownloadEml' => 'Download eml file~~',
	'MailInbox:EmptyMailbox' => 'No message to display~~',
	'MailInbox:ErrorMessage' => 'Mensaje de Error',
	'MailInbox:From' => 'De',
	'MailInbox:IgnoreMessage' => 'Ignore email~~',
	'MailInbox:Login/Server/MailboxMustBeUnique' => 'La combinación de usuario (%1$s), Servidor (%2$s) y buzón (%3$s) ya está configurado para otra cuenta',
	'MailInbox:Login/ServerMustBeUnique' => 'La combinación de usuario (%1$s) y Servidor (%2$s) ya está configurado para otro buzón.',
	'MailInbox:MailboxContent' => 'Contenido de Buzón',
	'MailInbox:MailboxContent:ConfirmMessage' => 'Are you sure ?~~',
	'MailInbox:MaxAllowedPacketTooSmall' => 'MySQL parameter max_allowed_packet in "my.ini" is too small: %1$s. The recommended value is at least: %2$s~~',
	'MailInbox:MessageDetails' => 'Message details~~',
	'MailInbox:RelatedTicket' => 'Ticket Relacionado',
	'MailInbox:ResetStatus' => 'Resetear estatus',
	'MailInbox:Status' => 'Estatus',
	'MailInbox:Status/Error' => 'Error',
	'MailInbox:Status/Ignored' => 'Ignored~~',
	'MailInbox:Status/New' => 'Nuevo',
	'MailInbox:Status/Processed' => 'Ya Procesado',
	'MailInbox:Status/Undesired' => 'Undesired~~',
	'MailInbox:Subject' => 'Asunto',
	'MailInbox:WithSelectedDo' => 'Con mensajes seleccionados: ',
	'MailInbox:Z_DisplayedThereAre_X_Msg_Y_NewInTheMailbox' => '%1$d eMails displayed. There are %2$d email(s) in the mailbox : %3$d new (including %4$d unreadable), %5$d processed.~~',
	'MailInboxProcessor:MessageTooBig_Size_MaxSize' => 'Message too big: %1$s (maximum allowed size %2$s)~~',
]);
