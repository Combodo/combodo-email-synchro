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
Dict::Add('FR FR', 'French', 'Français', [
	'Class:MailInboxBase' => 'Boîte Mail',
	'Class:MailInboxBase+' => 'Source d\eMails',
	'Class:MailInboxBase/Attribute:active' => 'Boîte Activée',
	'Class:MailInboxBase/Attribute:active+' => 'Si renseigné à “Oui”, la boite mail est interrogée, sinon elle ne l\'est pas',
	'Class:MailInboxBase/Attribute:active/Value:no' => 'Non',
	'Class:MailInboxBase/Attribute:active/Value:yes' => 'Oui',
	'Class:MailInboxBase/Attribute:login' => 'Identifiant',
	'Class:MailInboxBase/Attribute:login+' => 'L\'identifiant du compte applicatif pour se connecter à la boite mail',
	'Class:MailInboxBase/Attribute:mailbox' => 'Boîte Mail (pour IMAP)',
	'Class:MailInboxBase/Attribute:mailbox+' => 'Le sous-répertoire de la boite mail à interroger. S\'il n\'est pas fourni, on accède la racine',
	'Class:MailInboxBase/Attribute:password' => 'Mot de passe',
	'Class:MailInboxBase/Attribute:port' => 'Port',
	'Class:MailInboxBase/Attribute:port+' => '143 (securisé: 993) pour IMAP et 110 (securisé: 995) pour POP3',
	'Class:MailInboxBase/Attribute:protocol' => 'Protocole',
	'Class:MailInboxBase/Attribute:protocol+' => 'Attention POP3 n\'est plus supporté à partir d\'iTop 3.1',
	'Class:MailInboxBase/Attribute:protocol/Value:imap' => 'IMAP',
	'Class:MailInboxBase/Attribute:protocol/Value:pop3' => 'POP3',
	'Class:MailInboxBase/Attribute:server' => 'Serveur d\'eMails',
	'Class:MailInboxBase/Attribute:server+' => 'L\'adresse IP ou nom complet (DNS) du Serveur d\'eMails',
	'Class:TriggerOnMailUpdate' => 'Déclencheur sur mise à jour par mail',
	'Class:TriggerOnMailUpdate+' => 'Déclencheur activé sur la mise à jour de tickets par mail',
	'MailInbox:Date' => 'Date',
	'MailInbox:DeleteMessage' => 'Effacer l\'email',
	'MailInbox:Display_X_eMailsStartingFrom_Y' => 'Afficher %1$s eMail(s), à partir du numéro %2$s',
	'MailInbox:DownloadEml' => 'Télécharger l\'eml',
	'MailInbox:EmptyMailbox' => 'Aucun message à afficher',
	'MailInbox:ErrorMessage' => 'Message d\'Erreur',
	'MailInbox:From' => 'De',
	'MailInbox:IgnoreMessage' => 'Ignorer l\'email',
	'MailInbox:Login/Server/MailboxMustBeUnique' => 'La combinaison Identifiant (%1$s), Serveur (%2$s) et boîte mail (%3$s) est déjà utilisée par une Boîte Mail.',
	'MailInbox:Login/ServerMustBeUnique' => 'La combinaison Identifiant (%1$s) et Serveur (%2$s) est déjà utilisée par une Boîte Mail.',
	'MailInbox:MailboxContent' => 'Contenu de la boîte mail',
	'MailInbox:MailboxContent:ConfirmMessage' => 'Etes-vous sûr(e) ?',
	'MailInbox:MaxAllowedPacketTooSmall' => 'Le paramètre MySQL max_allowed_packet dans le fichier "my.ini" est trop petit : %1$s. La valeur recommandée est au moins : %2$s',
	'MailInbox:MessageDetails' => 'Details du message',
	'MailInbox:RelatedTicket' => 'Ticket Lié',
	'MailInbox:ResetStatus' => 'RàZ de l\'état',
	'MailInbox:Status' => 'Etat',
	'MailInbox:Status/Error' => 'Erreur',
	'MailInbox:Status/Ignored' => 'Ignoré',
	'MailInbox:Status/New' => 'Nouveau',
	'MailInbox:Status/Processed' => 'Déjà Traité',
	'MailInbox:Status/Undesired' => 'Indésirable',
	'MailInbox:Subject' => 'Objet',
	'MailInbox:WithSelectedDo' => 'Pour les éléments sélectionnés : ',
	'MailInbox:Z_DisplayedThereAre_X_Msg_Y_NewInTheMailbox' => '%1$d eMails affichés. Il y a au total %2$d eMail(s) dans la boîte : %3$d nouveaux (dont %4$d illisibles), et %5$d traités.',
	'MailInboxProcessor:MessageTooBig_Size_MaxSize' => 'Message trop gros: %1$s (la taille max autorisée est %2$s)',
]);
