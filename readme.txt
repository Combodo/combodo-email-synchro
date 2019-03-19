/////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Ticket creation and update from eMails
/////////////////////////////////////////////////////////////////////////////////////////////////////////////

Interface changes from the 2.5 branch

EmailProcessor class:
====================

ProcessMessage
--------------

Changed from:

public function ProcessMessage(EmailSource $oSource, $index, EmailMessage $oEmail, $oEmailReplica = null)

To:

public function ProcessMessage(EmailSource $oSource, $index, EmailMessage $oEmail, EmailReplica $oEmailReplica)

Previously new messages were not associated with an EmailReplica and null was passed as the last parameter.
Now an EmailReplica object is ALWAYS passed, but for new emails this EmailReplica is not yet stored in the database
(Meaning that $oEmailReplica->IsNew() returns true)

OnDecodeError
-------------
OnDecodeError(EmailSource $oSource, $sUIDL, $oEmail, RawEmailMessage $oRawEmail, &$aErrors = array())

Is now expected to return the "next action code": either: DELETE_MESSAGE or MARK_MESSAGE_AS_ERROR.

MailInbox class:
================

DispatchEmail
-------------
Changed from:

public function DispatchEmail($oEmailReplica = null)

To:

public function DispatchEmail(EmailReplica $oEmailReplica)
	
Instead of being passed null, a freshly created (not yet saved) replica is passed when processing a new email.

/////////////////////////////////////////////////////////////////////////////////////////////////////////////

Test cases
==========
Normal cases:
1. Create a ticket from an incoming email (existing contact): Ok. (ticket Ok, replica Ok).
2. Reset the replica and relaunch cron.php a new ticket will be created: Ok (ticket Ok, replica Ok).
3. Delete the ticket and relaunch cron.php, the email and replicas will be deleted: Ok (email Ok, replica Ok).
4. Send an email to update an existing ticket: Ok (ticket Ok, replica Ok).

Error cases with unknown caller:
5. Try to create a ticket for an unknown caller:
5.1. Error = delete email: Ok (replica Ok, email deleted Ok)
5.2. Error = mark as error: Ok (replica Ok)
5.3. With forward configured: Ok (replica Ok)

6. Automatic Person creation
6.1. Invalid default value(s) - the contact will not be created and the replica reports the error: Ok (replica Ok)
6.2. Correct default values(s) - the contact and the tickets are created:  Ok (replica Ok)

7. Automatic deletion of unused replicas (when there are emails in the mailbox): Ok.