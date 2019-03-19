<?php
// Copyright (C) 2010-2018 Combodo SARL
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
Dict::Add('ES CR', 'Spanish', 'Español, Castellaño', array(
	// Dictionary entries go here
	'Class:MailInboxBase' => 'Buzón de Correo',
	'Class:MailInboxBase+' => 'Fuente de mensajes entrantes',

	'Class:MailInboxBase/Attribute:server' => 'Servidor de Correo',
	'Class:MailInboxBase/Attribute:mailbox' => 'Buzón (para IMAP)',
	'Class:MailInboxBase/Attribute:login' => 'Usuario',
	'Class:MailInboxBase/Attribute:password' => 'Contraseña',
	'Class:MailInboxBase/Attribute:protocol' => 'Protocolo',
	'Class:MailInboxBase/Attribute:protocol/Value:pop3' => 'POP3',
	'Class:MailInboxBase/Attribute:protocol/Value:imap' => 'IMAP',
	'Class:MailInboxBase/Attribute:port' => 'Puerto',
	'Class:MailInboxBase/Attribute:active' => 'Activo',
	'Class:MailInboxBase/Attribute:active/Value:yes' => 'Si',
	'Class:MailInboxBase/Attribute:active/Value:no' => 'No',

	'MailInbox:MailboxContent' => 'Contenido de Buzón',
	'MailInbox:EmptyMailbox' => 'El buzón está vacío',
	'MailInbox:Z_DisplayedThereAre_X_Msg_Y_NewInTheMailbox' => '%1$d mensajes mostrados. Existen %2$d mensaje(s) en el buzón (%3$d new).',
	'MailInbox:Status' => 'Estatus',
	'MailInbox:Subject' => 'Asunto',
	'MailInbox:From' => 'De',
	'MailInbox:Date' => 'Fecha',
	'MailInbox:RelatedTicket' => 'Ticket Relacionado',
	'MailInbox:ErrorMessage' => 'Mensaje de Error',
	'MailInbox:Status/Processed' => 'Ya Procesado',
	'MailInbox:Status/New' => 'Nuevo',
    'MailInbox:Status/Error' => 'Error',
    'MailInbox:Status/Undesired' => 'Undesired~~',

	'MailInbox:Login/ServerMustBeUnique' => 'La combinación de usuario (%1$s) y Servidor (%2$s) ya está configurado para otro buzón.',
	'MailInbox:Login/Server/MailboxMustBeUnique' => 'La combinación de usuario (%1$s), Servidor (%2$s) y buzón (%3$s) ya está configurado para otra cuenta',
	'MailInbox:Display_X_eMailsStartingFrom_Y' => 'Mostrar %1$s mensaje(s), iniciando desde %2$s.',
	'MailInbox:WithSelectedDo' => 'Con mensajes seleccionados: ',
	'MailInbox:ResetStatus' => 'Resetear estatus',
	'MailInbox:DeleteMessage' => 'Borrar mensaje',

	'MailInbox:MessageDetails' => 'Message details~~',
	'MailInbox:DownloadEml' => 'Download eml file~~',


	'Class:TriggerOnMailUpdate' => 'Disparador (cuando sea actualizado por mensaje)',
	'Class:TriggerOnMailUpdate+' => 'Disparador activado cuanto un ticket es actualiado por el procesameinto de mensaje entrante',
));
