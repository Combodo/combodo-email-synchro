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
			'notify_errors_to' => '',  	// A valid email address to notify in case of error
			'notify_errors_from' => '',	// A valid 'From' email address for sending the notifications
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
