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
			'itop-tickets/1.0.0'
		),
		'mandatory' => false,
		'visible' => true,

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
			'pop3_server' => 'pop3.combodo.com',	// host or IP address of your mail POP3 server
			'pop3_port' => 110,		  				// POP3 port (std: 110)
			'mailbox_name' => 'test@combodo.com', 	// Name of the mailbox, i.e. email address: test@combodo.com
			'mailbox_pwd' => 'combodo',  			// Password to access the mailbox
		),
	)
);

?>
