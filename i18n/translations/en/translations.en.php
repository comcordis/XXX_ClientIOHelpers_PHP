<?php

global $XXX_I18n_Translations;

	$XXX_I18n_Translation['en']['flash'] = array
	(
		'notLoaded' => 'This part of the page uses Flash (An external browser plugin). It hasn\'t fully loaded yet! Please try again in a moment...'
	);
	
	
	$XXX_I18n_Translations['en']['optionSelectionManipulator'] = array
	(
		'select' => 'Select:',
		'all' => 'All',
		'none' => 'None',
		'invert' => 'Reverse'
	);
	
	
	$XXX_I18n_Translations['en']['slotCountManipulator'] = array
	(
		'slots' => 'Slots:',
		'add' => 'Add',
		'remove' => 'Remove'
	);
	
	
	$XXX_I18n_Translations['en']['calendarManipulator'] = array
	(
		'calendar' => 'Calendar:',
		'previousMonth' => '&lt;',
		'month' => 'Month',
		'nextMonth' => '&gt;',
		'previousYear' => '&lt;&lt;',
		'year' => 'Year',
		'nextYear' => '&gt;&gt;',
		'selectedDate' => 'Selected date',
		'today' => 'Today'
	);
	
	
	$XXX_I18n_Translations['en']['fileUploadManager'] = array
	(
		'connecting' => 'Uploading...',
			
		'estimatedTimeRemaining' => 'Estimated time remaining: %estimatedTimeRemaining%',
		
		'fileTotal' => 'File total',
		'fileSizeTotal' => 'File size total',
		
		'all' => 'All files',
		'queued' => 'Queued files',
		'failed' => 'Failed files',
		'uploaded' => 'Uploaded files',
		'imagePreview' => '(Preview thumbnails of images < <b>%maximumFileSize%</b>)',
		
		'uploadedFiles' => '<b>Uploaded:</b> <b>%fileTotal%</b> file(s) / <b>%fileSizeTotal%</b>',
		'failedFiles' => '<b>Failed:</b> <b>%fileTotal%</b> file(s) / <b>%fileSizeTotal%</b>',
		'queuedFiles' => '<b>Queued:</b> <b>%fileTotal%</b> file(s) / <b>%fileSizeTotal%</b>'
	);
	
	
	$XXX_I18n_Translations['en']['HTTPServer_Client_Input_Limits'] = array
	(
		'default' => 'None',
		'all' => 'All',
		'image' => 'Images',
		'avatarImage' => 'Avatar images'
	);
	
	
	$XXX_I18n_Translations['en']['email'] = array
	(
		'organization' => 'Comcordis B.V.',
		
		'sender' => 'service@comcordis.com',
		'errorReceiver' => 'infrastructure@comcordis.com',
		'replyReceiver' => 'service@comcordis.com'
	);
	
	
	$XXX_I18n_Translations['en']['page'] = array
	(
		'language' => 'en',
		'dialect' => 'us',
		'title' => 'Comcordis',
		'tags' => '',
		'description' => 'Hello world'
	);
	
	
	$XXX_I18n_Translations['en']['HTTPServer_Client_Input'] = array
	(
		'errors' => array
		(
			// Native PHP (http://php.net/manual/en/features.file-upload.errors.php)
			
				// Per file
			
					// 1 UPLOAD_ERR_INI_SIZE
					'exceedsUploadMaxFilesizeServerDirective' => array
					(
						'code' => 1,
						'description' => 'The file size exceeds the PHP "upload_max_filesize (maximumFileSize)" directive on the server.',
						'responsible' => 'server'
					),
												
					// 2 UPLOAD_ERR_FORM_SIZE
					'exceedsMaxFileSizeClientDirective' => array
					(
						'code' => 2,
						'description' => 'The file size exceeds a preceeding "MAX_FILE_SIZE (maximumFileSize)" directive in the HTML form on the client.',
						'responsible' => 'client'
					),							
					
					// 3 UPLOAD_ERR_PARTIAL
					'partialFileUpload' => array
					(
						'code' => 3,
						'description' => 'The file was only partially uploaded (the process was aborted by the client (browser) before it completed).',
						'responsible' => 'client'
					),
					
					// 4 UPLOAD_ERR_NO_FILE
					'noFileUploaded' => array
					(
						'code' => 4,
						'description' => 'No file was uploaded (the native HTML file upload input was left empty).',
						'responsible' => 'client'
					),
					
					// 5 UPLOAD_ERR_EMPTY
					'emptyFileUploaded' => array
					(
						'code' => 5,
						'description' => 'The file is empty.',
						'responsible' => 'client'
					),
					
					// 6 UPLOAD_ERR_NO_TMP_DIR
					'missingTemporaryDirectory' => array
					(
						'code' => 6,
						'description' => 'Missing a temporary directory. Check either the server OS default temporary directory or PHP "upload_tmp_dir (httpFileUploadDirectory)" directive on the server. Is it configured right, does it exists and does it have the right permissions?',
						'responsible' => 'server'
					),
					
					// 7 UPLOAD_ERR_CANT_WRITE
					'unableToWriteToTemporaryDirectory' => array
					(
						'code' => 7,
						'description' => 'Unable to write to the temporary directory. Check either the server OS default temporary directory or PHP "upload_tmp_dir (httpFileUploadDirectory)" directive on the server. Is it configured right, does it exists and does it have the right permissions?',
						'responsible' => 'server'
					),
					
					// 8 UPLOAD_ERR_EXTENSION
					'stoppedByExtension' => array
					(
						'code' => 8,
						'description' => 'The file upload was stopped by a PHP extension on the server.',
						'responsible' => 'server'
					),
											
					'fileUploadAttackAttempt' => array
					(
						'code' => 9,
						'description' => 'You\'re trying to access a non-uploaded file by manipulating the request.',
						'responsible' => 'client'
					),
					
				// Per request
					
					'tryingToByPassFileUploadsServerDirective' => array
					(
						'code' => 10,
						'description' => 'The file upload is not allowed "file_uploads (acceptFileUpload)" directive on the server."',
						'responsible' => 'server'
					),
					
					// Detectable by empty $_POST and $_FILES if expected filled
					'exceedsPostMaxSizeOrMaxInputTimeServerDirective' => array
					(
						'code' => 11,
						'description' => 'Either the file size total + the variable size total exceeds the PHP "post_max_size (maximumRequestSize)" directive on the server. Or the input time exceeds the PHP "max_input_time (maximumHTTPInputTime)" directive on the server.',
						'responsible' => 'server'
					),
					
					'exceedsPostMaxSizeServerDirective' => array
					(
						'code' => 12,
						'description' => 'The file size total + the variable size total exceeds the PHP "post_max_size (maximumRequestSize)" directive on the server.',
						'responsible' => 'server'
					),
					
					// PHP emits any files exceeding this limit
					'exceedsMaxFileUploadsServerDirective' => array
					(
						'code' => 13,
						'description' => 'The file total exceeds the PHP "max_file_uploads (maximumFileTotal)" directive on the server.',
						'responsible' => 'server'
					),
			
			// application
				
				// Per file
				
					'unacceptedFileUpload' => array
					(
						'code' => 14,
						'description' => 'No file uploads are accepted.',
						'responsible' => 'application'
					),
														
					'underMinimumFileSize' => array
					(
						'code' => 16,
						'description' => 'The file size is under the minimum file size limit.',
						'responsible' => 'application'
					),
										
					'exceedsMaximumFileSize' => array
					(
						'code' => 17,
						'description' => 'The file size exceeds the maximum file size limit. Please resize, split or select a smaller file.',
						'responsible' => 'application'
					),
					
					'unacceptedFileExtension' => array
					(
						'code' => 19,
						'description' => 'The file extension is not accepted.',
						'responsible' => 'application'
					),
					
					'unacceptedFileMIMEType' => array
					(
						'code' => 20,
						'description' => 'The file MIME type is not accepted.',
						'responsible' => 'application'
					),
									
				// Per request
				
					'exceedsMaximumFileTotal' => array
					(
						'code' => 23,
						'description' => 'The file total exceeds the limit.',
						'responsible' => 'application'
					),
					'exceedsMaximumFileSizeTotal' => array
					(
						'code' => 24,
						'description' => 'The file size total exceeds the limit.',
						'responsible' => 'application'
					),
											
					'unableToReadCrossDomainInlineFrameBody' => array
					(
						'code' => 25,
						'description' => 'Unable to read the inlineFrame body, potentially due to cross (sub-)domain browser security restrictions.',
						'responsible' => 'client'
					),
				
				'unableToMoveUploadedFile' => array
				(
					'code' => 26,
					'description' => 'Unable to move the uploaded file on the server.',
					'responsible' => 'server'
				),
				
				'invalidResponse' => array
				(
					'code' => 28,
					'description' => 'Invalid response from the server due to technical difficulties on our side. Please try again later.',
					'responsible' => 'application'
				),
				
				'fileNameManipulatedForLocation' => array
				(
					'code' => 29,
					'description' => 'You\'re trying to move the uploaded file to a manipulated location.',
					'responsible' => 'client'
				),
			
			'unknown' => array
			(
				'code' => 0,
				'description' => 'Unknown error.',
				'responsible' => 'unknown'
			)
		)
	);
	
	
	$XXX_I18n_Translations['en']['input'] = array
	(
		'filter' => array
		(
			'messages' => array
			(
				'integer' => 'Expected it to be an integer (number), but it wasn\'t so it was filtered.',
				'positiveInteger' => 'Expected it to be a positive integer (number), but it wasn\'t so it was filtered.',
				'float' => 'Expected it to be a float (number), but it wasn\'t so it was filtered.',
				'hash' => 'Expected it to be a hash (code / token), but it wasn\'t so it was filtered.',
				'base64' => 'Expected it to be a base64 encoded string (text), but it wasn\'t so it was filtered.',
				'stringUTF8' => 'Expected it to be a UTF-8 Unicode (International) string (text), but it wasn\'t so it was filtered.',
				'stringControlCharacters' => 'Expected it to be a string (text) without control (invisible) characters, but it wasn\'t so it was filtered.',
				'stringHTML' => 'Expected it to be a string (text) without HTML or JavaScript, but it wasn\'t so it was filtered.',
				'boolean' => 'Expected it to be a boolean (true or false), but it wasn\'t so it was filtered.'
			)	
		),
		
		'actions' => array
		(
			'value' => array
			(					
				'operation' => array
				(
					'removePattern' => 'Some invalid character(s) were filtered out.',
					'replacePattern' => 'Some invalid character(s) were replaced.',
					'string' => 'Converted it to text.',
					'maximumCharacterLength' => '%difference% character(s) were cut off,<br>only %maximumCharacterLength character(s) allowed.',
					'number' => 'Converted it to a number.',
					'minimumNumber' => 'Reset to <b>%minimumNumber%</b>, which is the minimum.',
					'maximumNumber' => 'Reset to <b>%maximumNumber%</b>, which is the maximum.',
					'float' => 'Converted it to a float (number with decimals).',
					'minimumFloat' => 'Reset to <b>%minimumFloat%</b>, which is the minimum.',
					'maximumFloat' => 'Reset to <b>%maximumFloat%</b>, which is the maximum.',
					'integer' => 'Converted it to an integer (number without decimals).',
					'minimumInteger' => 'Reset to <b>%minimumInteger%</b>, which is the minimum.',
					'maximumInteger' => 'Reset to <b>%maximumInteger%</b>, which is the maximum.',
					'round' => 'Rounded off.',
					'ceil' => 'Rounded up.',
					'floor' => 'Rounded down.'
				),
				
				'validation' => array
				(
					'required' => 'Required',
					'string' => 'Should be text.',
					'minimumByteSize' => 'Should be at least <b>%minimumByteSize%</b> byte(s).',
					'maximumByteSize' => 'Should be at most <b>%maximumByteSize%</b> byte(s).',
					'minimumCharacterLength' => 'Please add <b>%difference%</b> more character(s).<br>Should be at least <b>%minimumCharacterLength%</b> character(s) long.',
					'maximumCharacterLength' => 'Please remove <b>%difference%</b> character(s).<br>Should be at most <b>%maximumCharacterLength%</b> character(s) long.',
					'minimumWordCount' => 'Please add <b>%minimumWordCount%</b> word(s).<br>Should have at least <b>%minimumWordCount%</b> word(s).',
					'maximumWordCount' => 'Please remove <b>%maximumWordCount%</b> word(s).<br>Should have at most <b>%maximumWordCount%</b> word(s).',
					'number' => 'Should be a number.',
					'minimumNumber' => 'Should be at least <b>%minimumNumber%</b>.',
					'maximumNumber' => 'Should be at most <b>%maximumNumber%</b>',
					'float' => 'Should be a float (number with decimals)',
					'minimumFloat' => 'Should be at least <b>%minimumFloat%</b>.',
					'maximumFloat' => 'Should be at most <b>%maximumFloat%</b>',
					'integer' => 'Should be an integer (number without decimals).',
					'minimumInteger' => 'Should be at least <b>%minimumInteger%</b>.',
					'maximumInteger' => 'Should be at most <b>%maximumInteger%</b>.',
					'minimumPassSecurityRating' => 'Security rating should be at least <b>%minimumPassSecurityRating%%</b>,<br>currently only <b>%passSecurityRating%%</b>.',
					'matchValue' => 'Should match the following value(s): <b>%value%</b>',
					'doNotMatchValue' => 'Should not match the following value(s): <b>%value%</b>',
					'matchPattern' => 'Invalid',
					'doNotMatchPattern' => 'Invalid',						
					'synchronousCallback' => 'Invalid'
				),
				
				'confirmation' => array
				(
					'ok' => 'OK',
					'available' => 'Available',
					'thanks' => 'Thanks',
					'valid' => 'Valid',
					'secure' => 'Secure'
				),
				
				'information' => array
				(
					'byteSize' => '<b>%byteSize%</b> byte(s)',
					'characterLength' => '<b>%characterLength%</b> character(s)',
					'wordCount' => '<b>%wordCount%</b> word(s)',
					'suffixCharacterPeek' => 'Typo-check: <b>%suffixCharacterPeek%</b>',
					'passSecurityAdvice' => array
					(
						'digit' => '* Tip: Try using at least one digit: <b>0-9</b> (<b>+20%</b>)',
						'lowerCaseLetter' => '* Tip: Try using at least one lower case letter: <b>a-z</b> (<b>+20%</b>)',
						'upperCaseLetter' => '* Tip: Try using at least one UPPER case letter: <b>A-Z</b> (<b>+20%</b>)',
						'specialCharacter' => '* Tip: Try using at least one special character: <b>!?@#$%^&*</b> etc. (<b>+25%</b>)'
					),
					'passSecurityRating' => 'Security rating: <b>%passSecurityRating%%</b>'
				)
			),
			
			'option' => array
			(
				'operation' => array
				(
				),
				'validation' => array
				(					
					'required' => 'Selection is required.',
					'matchValue' => 'Should match the following value(s): <b>%value%</b>',
					'doNotMatchValue' => 'Should not match the following value(s): <b>%value%</b>',
					'synchronousCallback' => 'Invalid'
				),
				'confirmation' => array
				(					
					'' => ''
				),
				'information' => array
				(					
					'selected' => 'Selected',
					'notSelected' => 'Not selected',
					'selectedTotal' => 'Selected: <b>%selectedTotal%</b>'
				)
			),				
			
			'options' => array
			(
				'operation' => array
				(
					'maximumSelected' => 'Deselected <b>%difference%</b> options. Only <b>%maximumSelected%</b> allowed.'
				),
				'validation' => array
				(					
					'required' => 'A selection is required.',
					'minimumSelected' => 'Please select <b>%difference%</b> more option(s).<br>You should select at least <b>%minimumSelected%</b> option(s).',
					'maximumSelected' => 'Please deselect <b>%difference%</b> option(s).<br>You should select <b>%maximumSelected%</b> option(s) at most.',
					'matchValue' => 'Should match the following value(s): <b>%value%</b>',
					'doNotMatchValue' => 'Should not match the following value(s): <b>%value%</b>'
				),
				'confirmation' => array
				(					
					'' => ''
				),
				'information' => array
				(					
					'selectedTotal' => 'Selected: <b>%selectedTotal%</b>'
				)
			),
			
			'date' => array
			(
				'validation' => array
				(
					'past' => 'Should be a date in the past.',
					'future' => 'Should be a date in the future.',
					'minimumDateOfBirthYearAge' => 'Should be at least <b>%minimumDateOfBirthYearAge%</b> year(s) old.',
					'maximumDateOfBirthYearAge' => 'Should be at most <b>%maximumDateOfBirthYearAge%</b> year(s) old.',
					'exists' => 'Should be an existing date.<br>This month only has <b>%daysInMonth%</b> days.'
				),
				
				'information' => array
				(
					'dateOfBirthYearAge' => 'You\'re <b>%dateOfBirthYearAge%</b> year(s) old.',
					'dayOfTheWeek' => '<b>%dayOfTheWeek%</b>'
				)
			)
		),
		
		'nativeForm' => array
		(
			'unloadConfirmation' => 'Watch out! You\'ve made some changes on this page that aren\'t saved yet. Leaving this page will result in those changes being lost... So are you really sure you want to leave this page?',
			
			// Replaces the submit button labels
			'invalidFormInputs' => '^ Please correct the invalid field(s) first ^',
			'submitFormProcessing' => 'Thank you (Now processing...)',
			'submitFormUploading' => 'Thank you (Now uploading...)',
		),
		
		'fileUploadInput' => array
		(
			'browseLabel' => 'Browse',
			'browseTitle' => 'Browse for & select file(s) on your local device.',
			'uploadLabel' => 'Upload',
			'uploadTitle' => 'Upload (transfer file(s) from your device to the website).'
		)
	);
	
	
	$XXX_I18n_Translations['en']['sequence'] = array
	(
		'first' => false,
		'last' => ' and ',
		// Default
		'between' => ', '
	);
	
?>