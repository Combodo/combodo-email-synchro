<?php


SetupWebPage::AddModule(
	__FILE__, // Path to the current file, all other file names are relative to the directory containing this file
	'combodo-email-synchro/1.0.0',
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
			'debug' => false,  			// Set to true to turn on debugging
			'periodicity' => 30,		// interval at which to check for incoming emails (in s)
			'body_parts_order' => 'text/html,text/plain', // Order in which to read the parts of the incoming emails
			'pop3_auth_option' => 'USER',
			'imap_options' => array('imap'),
			'exclude_attachment_types' => array('application/exe'), // Example: 'application/exe', 'application/x-winexe', 'application/msdos-windows'
			// Lines to be removed just above the 'new part' in a reply-to message... add your own patterns below
			'introductory-patterns' => array(
				'/^le .+ a écrit :$/i', // Thunderbird French
				'/^on .+ wrote:$/i', // Thunderbird English
				'|^[0-9]{4}/[0-9]{1,2}/[0-9]{1,2} .+:$|', // Gmail style
			),
		),
	)
);

?>
