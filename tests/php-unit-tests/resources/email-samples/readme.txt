email_131.eml: Email with "us-ascii" charset and binary attachment. (Issue was that attachment was converted through iconv() when it should not)
email_132.eml: Email with uppercase content-transfer-encoding ("BASE64"). (Issue was that mail had "Quote-Printable" and "BASE64" instead of lowercase ones)
email_133_kb4170_multiple_lines_encoded_data.eml: Email with a multiline subject with different MIME encodings (Issue that caused white space in front of subject)
