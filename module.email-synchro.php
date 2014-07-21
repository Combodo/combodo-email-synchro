<?php


SetupWebPage::AddModule(
	__FILE__, // Path to the current file, all other file names are relative to the directory containing this file
	'combodo-email-synchro/2.6.3',
	array(
		// Identification
		//
		'label' => 'Tickets synchronization via e-mail',
		'category' => 'business',

		// Setup
		//
		'dependencies' => array(
		),
		'mandatory' => true,
		'visible' => false,

		// Components
		//
		'datamodel' => array(
			'model.email-synchro.php',
			'mailinbox.class.inc.php',
			'emailprocessor.class.inc.php',
			'emailbackgroundprocess.class.inc.php',
			'trigger.class.inc.php',
		),
		'dictionary' => array(
		),
		'data.struct' => array(
		),
		'data.sample' => array(
		),
		
		// Documentation
		//
		'doc.manual_setup' => '', // No manual installation required
		'doc.more_information' => '', // None

		// Default settings
		//
		'settings' => array(
			'notify_errors_to' => '', // mandatory to track errors not handled by the email processing module
			'notify_errors_from' => '', // mandatory as well (can be set at the same value as notify_errors_to)
			'debug' => false,  			// Set to true to turn on debugging
			'periodicity' => 30,		// interval at which to check for incoming emails (in s)
			'body_parts_order' => 'text/plain,text/html', // Order in which to read the parts of the incoming emails
			'pop3_auth_option' => 'USER',
			'imap_options' => array('imap'),
			'maximum_email_size' => '10M', // Maximum allowed size for incoming emails
			'big_files_dir' => '',
			'exclude_attachment_types' => array('application/exe'), // Example: 'application/exe', 'application/x-winexe', 'application/msdos-windows'
			// Lines to be removed just above the 'new part' in a reply-to message... add your own patterns below
			'introductory-patterns' => array(
				'/^le .+ a écrit :$/i', // Thunderbird French
				'/^on .+ wrote:$/i', // Thunderbird English
				'|^[0-9]{4}/[0-9]{1,2}/[0-9]{1,2} .+:$|', // Gmail style
			),
			// Some patterns which delimit the previous message in case of a Reply
			// The "new" part of the message is the text before the pattern
			// Add your own multi-line patterns (use \\R for a line break)
			// These patterns depend on the mail client/server used... feel free to add your own discoveries to the list
			'multiline-delimiter-patterns' => array(
            	'/\\RFrom: .+\\RSent: .+\\R/m', // Outlook English
                '/\\R_+\\R/m', // A whole line made only of underscore characters
                '/\\RDe : .+\\R\\R?Envoyé : /m', // Outlook French, HTML and rich text
                '/\\RDe : .+\\RDate d\'envoi : .+\\R/m', // Outlook French, plain text
                '/\\R-----Message d\'origine-----\\R/m',
			)
		),
	)
);

?>
