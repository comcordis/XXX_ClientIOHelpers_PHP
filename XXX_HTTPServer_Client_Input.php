<?php

abstract class XXX_HTTPServer_Client_Input
{
	const CLASS_NAME = 'XXX_HTTPServer_Client_Input';
	
	public static $profiles = array
	(
		'default' => array
		(
			'acceptFileUpload' => false,
			
			'minimumFileSize' => 0,
			'maximumFileSize' => 0,
			
			'maximumFileTotal' => 0,
			
			'acceptFileExtensions' => array(),
			'acceptFileMIMETypes' => array()
		)
	);
	
	public static $withinRequestBodyLimits = true;
	
	public static $headers = array();
	
	public static $onlyIfModifiedSinceTimestamp = 0;
	
	public static $responseRange = false;
	
	public static $failedFileUpload = false;	
	public static $successfulFileUpload = false;
		
	public static $parsedURIVariables = array();
	public static $parsedBodyVariables = array();
	public static $parsedJSONVariables = array();
	
	public static $parsedFileUploads = array();
	public static $parsedSplitFileUploads = array();
	public static $parsedCombinedFileUploads = array();
		
	public static $maliciousVariables = array();
	
	public static function initialize ()
	{
		// Check for too much POST data (resulting in empty $_POST && $_FILES)
		self::$withinRequestBodyLimits = !(strtolower($_SERVER['REQUEST_METHOD']) == 'post' && empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) || strtolower($_SERVER['REQUEST_METHOD']) == 'get';
		
		// See apache documentation > http://httpd.apache.org/docs/2.2/mod/mod_rewrite.html
		foreach ($_SERVER as $key => $value)
		{
			if (XXX_String::beginsWith($key, 'HTTP_'))
			{
				self::$headers[XXX_String::getPart($key, 5)] = $value;
			}
		}
						
		// Only serve a page if it has changed since then
			if (isset(self::$headers['IF_MODIFIED_SINCE']))
			{
				self::$onlyIfModifiedSinceTimestamp = strtotime(self::$headers['IF_MODIFIED_SINCE']);
			}
		
		// Range (For partial downloads)
		
			$range = '';
			$seekStart = 0;
			$seekEnd = 0;
			
			if(isset(self::$headers['RANGE']))
			{
				list($sizeUnit, $rawRanges) = explode('=', self::$headers['RANGE'], 2);
				
				if ($sizeUnit == 'bytes')
				{
					// Multiple ranges could be specified at the same time, but for simplicity only serve the first range
					// http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
					list($range, $extraRanges) = explode(',', $rawRanges, 2);
					
					$isPartial = true;
				}
			}
			
			if ($isPartial)
			{
				if ($range != '')
				{
					list($seekStart, $seekEnd) = explode('-', $range, 2);
					
					// Set start and end based on range (if set), else set defaults, and check for invalid ranges.
				    
				    if ($seekEnd == '')
				    {
				    	$seekEnd = $byteSize - 1;
				    }
				    else
				    {
				    	$seekEnd = XXX_Number::lowest(XXX_Number::absolute(XXX_Type::makeInteger($seekEnd)), $byteSize - 1);
				    }
				    
				    if ($seekStart == '')
				    {
				    	if ($seekEnd < XXX_Number::absolute(XXX_Number::makeInteger($seekStart)))
				    	{
				    		$seekStart = 0;
				    	}
				    	else
				    	{
				    		$seekStart = XXX_Number::highest(XXX_Number::absolute(XXX_Type::makeInteger($seekStart)), 0);
				    	}
				    }
				}
			}
			
			if ($seekStart != 0 && $seekEnd != 0)
			{
				self::$responseRange = array
				(
					'seekStart' => $seekStart,
					'seekEnd' => $seekEnd
				);
			}
		
		
		if (!self::$withinRequestBodyLimits)
		{			
			trigger_error('Not within request body limits.', E_USER_ERROR);
			
			XXX::dispatchEventToListeners('notWithinRequestBodyLimits', array());
		}
		
		self::$parsedURIVariables = self::parseVariables($_GET);
		
		self::$parsedBodyVariables = self::parseVariables($_POST);
		
		$rawBodyData = self::getRawBodyData();
		
		if ($rawBodyData != '')
		{
			$rawBodyDataJSONDecoded = XXX_String_JSON::decode($rawBodyData);
			
			if (XXX_Type::isArray($rawBodyDataJSONDecoded))
			{
				self::$parsedJSONVariables = $rawBodyDataJSONDecoded;
			}
		}		
		
		self::$parsedFileUploads = self::parseFileUploads($_FILES);
			self::$parsedSplitFileUploads = self::splitParsedFileUploadsByInputName(self::$parsedFileUploads);		
			self::$parsedCombinedFileUploads = self::combineParsedFileUploads(self::$parsedFileUploads);	
	}
	
	public static function addProfile ($name = '', $profile = array())
	{
		$defaultProfile = self::$profiles['default'];
		
		$profile = XXX_Array::merge($defaultProfile, $profile);
		
		self::$profiles[$name] = $profile;
	}
	
	public static function getHeaders ()
	{
		return self::$headers;
	}
		
	// body
	
		public static function getBodyVariable ($inputName, $filter = 'string', $parameters = array())
		{
			$value = '';
			
			if (array_key_exists($inputName, self::$parsedBodyVariables))
			{
				$value = self::$parsedBodyVariables[$inputName];
			}
			
			$result = XXX_Client_Input::sanitizeVariable($inputName, $value, $filter, $parameters, true);
			
			return $result;	
		}
		
		public static function setBodyVariable ($inputName, $value = '')
		{
			self::$parsedBodyVariables[$inputName] = $value;
		}
		
		public static function getRawBodyVariables ()
		{
			return self::$parsedBodyVariables;
		}
		
	// uri
	
		public static function getURIVariable ($inputName, $filter = 'string', $parameters = array())
		{
			$value = '';
			
			if (array_key_exists($inputName, self::$parsedURIVariables))
			{
				$value = self::$parsedURIVariables[$inputName];
			}
			
			$result = XXX_Client_Input::sanitizeVariable($inputName, $value, $filter, $parameters, true);
			
			return $result;
		}
		
		public static function setURIVariable ($inputName, $value = '')
		{
			self::$parsedURIVariables[$inputName] = $value;
		}
		
		public static function getRawURIVariables ()
		{
			return self::$parsedURIVariables;
		}
		
	// json
		
		public static function getJSONVariable ($inputName, $filter = 'string', $parameters = array())
		{
			$value = '';
			
			if (array_key_exists($inputName, self::$parsedJSONVariables))
			{
				$value = self::$parsedJSONVariables[$inputName];
			}
			
			$result = XXX_Client_Input::sanitizeVariable($inputName, $value, $filter, $parameters, true);
			
			return $result;
		}
		
		public static function setJSONVariable ($inputName, $value = '')
		{
			self::$parsedJSONVariables[$inputName] = $value;
		}
		
		public static function getRawJSONVariables ()
		{
			return self::$parsedJSONVariables;
		}
	
	
	public static function getVariable ($inputName, $filter = 'string', $parameters = array(), $order = array('json', 'body', 'uri'))
	{
		$value = '';
		
		foreach ($order as $type)
		{
			$found = false;
			
			switch ($type)
			{
				case 'json':
					if (array_key_exists($inputName, self::$parsedJSONVariables))
					{
						$value = self::$parsedJSONVariables[$inputName];
						
						$found = true;
					}
					break;
				case 'body':
					if (array_key_exists($inputName, self::$parsedBodyVariables))
					{
						$value = self::$parsedBodyVariables[$inputName];
						
						$found = true;
					}
					break;
				case 'uri':
					if (array_key_exists($inputName, self::$parsedURIVariables))
					{
						$value = self::$parsedURIVariables[$inputName];
						
						$found = true;
					}
					break;
			}
			
			if ($found)
			{
				break;
			}
		}
		
		$result = XXX_Client_Input::sanitizeVariable($inputName, $value, $filter, $parameters, true);
		
		return $result;
	}	
	
	/*
	
	http://php.net/manual/en/wrappers.php.php
	
	*/
	
	public static function getRawBodyData ($parser = '')
	{
		$result = false;
		
		$data = file_get_contents('php://input');
		
		$result = $data;
		
		if ($parser && $result != '')
		{
			switch ($parser)
			{
				case 'phpon':
					$result = XXX_String_PHPON::decode($result);
					break;
				case 'json':
					$result = XXX_String_JSON::decode($result);
					break;
				case 'csv':
					$result = XXX_String_CSV::parse($result);
					break;
			}
		}
		
		return $result;
	}
	
	public static function getRawURIData ()
	{
		return $_SERVER['REQUEST_URI'];
	}
	
	public static function recursiveCleanValue ($key, $value)
	{
		if (XXX_Type::isArray($value))
		{
			foreach ($value as $subKey => $subValue)
			{
				$value[$subKey] = self::recursiveCleanValue($subKey, $subValue);
			}
		}
		else
		{
			if (XXX_PHP::$magicQuotes)
			{
				$value = XXX_String::stripSlashes($value);
			}
			
			if (XXX_String::endsWith($key, 'ID'))
			{
				$value = XXX_Type::makeNumber($value);
			}
		}
		
		return $value;
	}
	
	public static function parseVariables (array $variables = array())
	{
		$newVariables = array();
		
		foreach ($variables as $key => $value)
		{
			$value = self::recursiveCleanValue($key, $value);
			
			$newVariables[$key] = $value;
		}
		
		return $newVariables;
	}
		
	public static function getFileUploadsForInput ($inputName = 'Filedata', $default = false)
	{
		$result = $default;
		
		if (XXX_Array::hasKey(self::$parsedSplitFileUploads, $inputName))
		{
			$result = self::$parsedSplitFileUploads[$inputName];
		}
		
		return $result;
	}
	
	public static function getFileUploads ()
	{
		return self::$parsedCombinedFileUploads;
	}
		
	public static function getNativeHTMLFileUploads ($default = false)
	{
		return self::getFileUploadsForInput('Filedata', $default);
	}
	
	public static function getNativeFlashFileUploads ($default = false)
	{
		return self::getFileUploadsForInput('Filedata', $default);
	}
	
	
	public static function getEffectiveHTTPServer_Client_Input_LimitsFilteredFileUploads ($HTTPServer_Client_Input_LimitsProfile = 'default')
	{
		$fileUploads = self::getFileUploads();
		
		$effectiveHTTPServer_Client_Input_Limits = XXX_HTTPServer_Client_Input::composeEffectiveHTTPServer_Client_Input_Limits($HTTPServer_Client_Input_LimitsProfile);
		
		$fileUploads = XXX_HTTPServer_Client_Input::filterFileUploadsWithEffectiveHTTPServer_Client_Input_Limits($fileUploads, $effectiveHTTPServer_Client_Input_Limits);
		
		return $fileUploads;
	}
	
	
	public static function getFileUploadProgress ($ID)
	{
		$result = false;
		
		if (XXX_PHP::$HTTPServer_Client_InputFileUploadProgress && XXX_Type::isValue($ID))
		{
			// Uses filesystem (Preffered) - http://www.scriptorama.nl/browsers/hoe-maak-ik-een-file-upload-progress-bar-met-php
			if (XXX_PHP::hasExtension('uploadprogress'))
			{
				$progress = uploadprogress_get_info($ID);
				
				if (XXX_Type::isArray($progress))
				{
					$result = array
					(
						// Time stamp of when the input began
						'startTimestamp' => $progress['time_start'],
						
						// Last time stamp of when the progress event was updated
						'lastUpdatedTimestamp' => $progress['time_last'],
						
						// Average speed (bytes per second)
						'averageSpeed' => $progress['speed_average'],
						
						// Last measured speed (bytes per second)
						'lastMeasuredSpeed' => $progress['speed_last'],
						
						// Bytes uploaded
						'bytesUploaded' => $progress['bytes_uploaded'],
						
						// Bytes total
						'bytesTotal' => $progress['bytes_total'],
						
						// Files uploaded
						'filesUploaded' => $progress['files_uploaded'],
						
						// Estimated seconds remaining
						'estimatedSecondsRemaining' => $progress['est_sec'],
						
						// Fraction
						'progress' => XXX_Number::round($progress['bytes_uploaded'] / $progress['bytes_total'], 3)
					);
				}
			}
			// Uses shared memory (Impractical in production) and requires APC - http://www.phpriot.com/articles/php-ajax-file-uploads/3
			else if (XXX_PHP::hasExtension('apc'))
			{
				$progress = apc_fetch('upload_' . $ID);
				
				if (XXX_Type::isArray($progress))
				{
					$result = array
					(
						// Upload done
						'done' => $progress['done'],
						
						// Bytes uploaded
						'bytesUploaded' => $progress['current'],
						
						// Bytes total
						'bytesTotal' => $progress['total'],
						
						// Fraction
						'progress' => XXX_Number::round($progress['current'] / $progress['total'], 3)
					);
				}
			}
		}
		
		return $result;
	}
	
	public static function parseFileUploads (array $files = array())
	{
		// Step 1 (Reformat arrays on a per file basis (By default, file properties are arrays instead of files), and separate failed from uploaded etc.)
				
			$inputNames = array();
			
			$uploadedFiles = array();
			$failedFiles = array();
			
			// Standardize files and determine if it uploaded or failed
			if (is_array($files) && count($files))
			{
				foreach ($files as $key => $tempFile)
				{
					$uploadedFiles[$key] = array();
					$failedFiles[$key] = array();
					
					if (!array_key_exists($key, $inputNames))
					{
						$inputNames[] = $key;
					}
					
					// Array of files with the same input name
					if (is_array($tempFile['name']))
					{
						for ($i = 0, $iEnd = count($tempFile['name']); $i < $iEnd; ++$i)
						{
							$subTempFile = array
							(
								'inputName' => $key,
								'name' => $tempFile['name'][$i],
								'type' => $tempFile['type'][$i],
								'size' => $tempFile['size'][$i],
								'tmp_name' => $tempFile['tmp_name'][$i],
								'error' => $tempFile['error'][$i]
							);
							
							if (!$subTempFile['error'])
							{
								if ($subTempFile['size'] == 0 && $subTempFile['error'] == 0)
								{
									$subTempFile['error'] = 5;
									
									$failedFiles[$key][] = $subTempFile;
								}
								else
								{								
									// Avoid file upload attacks
									if (is_uploaded_file($subTempFile['tmp_name']))
									{
										$uploadedFiles[$key][] = $subTempFile;
									}
									else
									{
										$subTempFile['error'] = 9;
										
										$failedFiles[$key][] = $subTempFile;
									}
								}
							}
							else
							{
								$failedFiles[$key][] = $subTempFile;
							}
						}
					}
					// Separate files 
					else
					{
						$tempFile['inputName'] = $key;
						
						if (!$tempFile['error'])
						{
							if ($tempFile['size'] == 0 && $tempFile['error'] == 0)
							{
								$tempFile['error'] = 5;
								
								$failedFiles[$key][] = $tempFile;
							}
							else
							{	
								// Avoid file upload attacks
								if (is_uploaded_file($tempFile['tmp_name']))
								{
									$uploadedFiles[$key][] = $tempFile;
								}
								else
								{
									$tempFile['error'] = 9;
									
									$failedFiles[$key][] = $tempFile;
								}
							}
						}
						else
						{
							$failedFiles[$key][] = $tempFile;
						}
					}
				}
			}
		
		// Step 2 (Process (rename and complement information)
				
			$fileTotal = 0;
			$failedFileTotal = 0;
			$uploadedFileTotal = 0;
			
			$fileSizeTotal = 0;
			$failedFileSizeTotal = 0;
			$uploadedFileSizeTotal = 0;
			
			$manipulated = false;
			
			// Uploaded files
			$newUploadedFiles = array();
			
			foreach ($uploadedFiles as $inputName => $inputFiles)
			{
				if (XXX_Array::getFirstLevelItemTotal($inputFiles) > 0)
				{
					$newUploadedFiles[$inputName] = array();
					
					foreach ($inputFiles as $uploadedFile)
					{
						$newUploadedFile = array();
						
						$newUploadedFile['inputName'] = $uploadedFile['inputName'];
						
						$newUploadedFile['file'] = $uploadedFile['name'];
						$newUploadedFile['name'] = XXX_FileSystem_Local::getFileName($uploadedFile['name']);
						$newUploadedFile['extension'] = XXX_FileSystem_Local::getFileExtension($uploadedFile['name']);
						
						$tempFile = $newUploadedFile['name'];
						
						if ($newUploadedFile['extension'] != '')
						{
							$tempFile .= '.' . $newUploadedFile['extension'];
						}
						
						$newUploadedFile['file'] = $tempFile;
						
						$newUploadedFile['browserMIMEType'] = $uploadedFile['type'];
						$newUploadedFile['serverMIMEType'] = XXX_FileSystem_Local::getFileMIMEType($uploadedFile['tmp_name']);						
						$newUploadedFile['mimeType'] = XXX_FileSystem_Local::determineMostSpecificMIMEType(array($newUploadedFile['serverMIMEType'], $newUploadedFile['browserMIMEType']));
						
						$newUploadedFile['size'] = $uploadedFile['size'];
						
						$newUploadedFile['checksum'] = XXX_FileSystem_Local::getFileChecksum($uploadedFile['tmp_name'], 'md5');
						
						$newUploadedFile['temporaryFile'] = $uploadedFile['tmp_name'];
						
						if (XXX_PHP::$debug)
						{
							$newUploadedFile['owner'] = XXX_FileSystem_Local::getFileOwner($uploadedFile['tmp_name']);
						}
												
						$newUploadedFiles[$inputName][] = $newUploadedFile;
						
						$uploadedFileSizeTotal += $newUploadedFile['size'];
						$fileSizeTotal += $newUploadedFile['size'];
						
						++$uploadedFileTotal;
						
						self::$successfulFileUpload = true;
						
						++$fileTotal;
					}
				}
			}
			
			$uploadedFiles = $newUploadedFiles;
						
			// Failed files
			$newFailedFiles = array();
			
			foreach ($failedFiles as $inputName => $inputFiles)
			{
				if (XXX_Array::getFirstLevelItemTotal($inputFiles) > 0)
				{
					$newFailedFiles[$inputName] = array();
					
					foreach ($inputFiles as $failedFile)
					{
						$newFailedFile = array();
						
						$newFailedFile['inputName'] = $failedFile['inputName'];
						
						// Error / Manipulated
					
							$newFailedFile['manipulated'] = false;
													
							switch ($failedFile['error'])
							{
								case 1:
									$newFailedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'exceedsUploadMaxFilesizeServerDirective');
									break;
								case 2:
									$newFailedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'exceedsMaxFileSizeClientDirective');
									break;
								case 3:
									$newFailedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'partialFileUpload');
									break;
								case 4:
									$newFailedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'noFileUploaded');
									break;
								case 5:
									$newFailedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'emptyFileUploaded');
									break;
								case 6:
									$newFailedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'missingTemporaryDirectory');
									break;
								case 7:
									$newFailedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'unableToWriteToTemporaryDirectory');
									break;
								case 8:
									$newFailedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'stoppedByExtension');
									break;
								case 9:
									// Custom error
									$newFailedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'fileUploadAttackAttempt');
									
									$newFailedFile['manipulated'] = true;
									$manipulated = true;
									break;
								default:
									$newFailedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'unknown');
									break;
							}
						
						if (strlen($failedFile['name']) > 0)
						{
							$newFailedFile['file'] = $failedFile['name'];
							$newFailedFile['name'] = XXX_FileSystem_Local::getFileName($failedFile['name']);
							$newFailedFile['extension'] = XXX_FileSystem_Local::getFileExtension($failedFile['name']);
							
							$tempFile = $newFailedFile['name'];
						
							if ($newFailedFile['extension'] != '')
							{
								$tempFile .= '.' . $newFailedFile['extension'];
							}
							
							$newFailedFile['file'] = $tempFile;
							
							$newFailedFile['browserMIMEType'] = $failedFile['type'];
							$newFailedFile['mimeType'] = $newFailedFile['browserMIMEType'];
							
							if (strlen($failedFile['size']) > 0)
							{
								$newFailedFile['size'] = $failedFile['size'];
							}
							else
							{
								$newFailedFile['size'] = 0;
							}
						}
						else
						{
							$newFailedFile['file'] = '';
							$newFailedFile['name'] = '';
							$newFailedFile['extension'] = '';
							$newFailedFile['browserMIMEType'] = '';
							$newFailedFile['mimeType'] = '';
							$newFailedFile['size'] = 0;
						}
												
						$newFailedFiles[$inputName][] = $newFailedFile;
						
						$failedFileSizeTotal += $newFailedFile['size'];
						$fileSizeTotal += $newFailedFile['size'];
						
						++$failedFileTotal;
						
						self::$failedFileUpload = true;
						
						++$fileTotal;
					}
				}
			}
			
			$failedFiles = $newFailedFiles;
						
			$files = array
			(
				'inputNames' => $inputNames,
				
				'files' => array
				(
					'failed' => $failedFiles,
					'uploaded' => $uploadedFiles
				),
				
				'fileTotal' => $fileTotal,
				'failedFileTotal' => $failedFileTotal,
				'uploadedFileTotal' => $uploadedFileTotal,
				
				'fileSizeTotal' => $fileSizeTotal,
				'failedFileSizeTotal' => $failedFileSizeTotal,
				'uploadedFileSizeTotal' => $uploadedFileSizeTotal,
				
				'manipulated' => $manipulated,
				
				'withinRequestBodyLimits' => self::$withinRequestBodyLimits,
				'error' => !self::$withinRequestBodyLimits ? XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'exceedsPostMaxSizeOrMaxInputTimeServerDirective') : ''
			);
		
		
		// Step 3 apply server configured HTTPServer_Client_Input_Limits	
		
			$newFailedFiles = array();
			$newUploadedFiles = array();
			
			$fileTotal = 0;
			$failedFileTotal = 0;
			$uploadedFileTotal = 0;
			
			$fileSizeTotal = 0;
			$failedFileSizeTotal = 0;
			$uploadedFileSizeTotal = 0;
			
			$phpHTTPServer_Client_Input_Limits = XXX_HTTPServer::$inputLimits;		
			
			$acceptFileUpload = $phpHTTPServer_Client_Input_Limits['acceptFileUpload'];
			$maximumFileSize = $phpHTTPServer_Client_Input_Limits['maximumFileSize'];
			$maximumFileTotal = $phpHTTPServer_Client_Input_Limits['maximumFileTotal'];
			$maximumFileSizeTotal = $phpHTTPServer_Client_Input_Limits['maximumFileSizeTotal'];
			$maximumRequestSize = $phpHTTPServer_Client_Input_Limits['maximumRequestSize'];
			$maximumInputTime = $phpHTTPServer_Client_Input_Limits['maximumInputTime'];
			$acceptFileExtensions = $phpHTTPServer_Client_Input_Limits['acceptFileExtensions'];
			$acceptFileMIMETypes = $phpHTTPServer_Client_Input_Limits['acceptFileMIMETypes'];
			
			if (XXX_Array::getFirstLevelItemTotal($files['files']['uploaded']) > 0)
			{
				foreach ($files['files']['uploaded'] as $inputName => $inputFiles)
				{
					if (XXX_Array::getFirstLevelItemTotal($inputFiles) > 0)
					{
						foreach ($inputFiles as $uploadedFile)
						{
							$valid = true;
							
							if ($valid)
							{
								// Sanitize the file string
								$normalizedFile = XXX_Client_Input::sanitizeVariable($uploadedFile['inputName'], $uploadedFile['file'], 'string', false, true);
								
								if ($normalizedFile != $uploadedFile['file'])
								{
									$uploadedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'fileNameManipulatedForLocation');
									
									$result['manipulated'] = true;
									
									$valid = false;
								}
							}
							
							if ($valid)
							{
								// ../ ./ prefix etc.
								$normalizedFile = XXX_Path_Local::normalizePath($uploadedFile['file'], '/', true);
								
								if ($normalizedFile != $uploadedFile['file'])
								{
									$uploadedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'fileNameManipulatedForLocation');
									
									$result['manipulated'] = true;
									
									$valid = false;
								}
							}
							
							if ($valid)
							{
								if (!$acceptFileUpload)
								{
									$uploadedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'tryingToByPassFileUploadsServerDirective');
									
									$result['manipulated'] = true;
									
									$valid = false;
								}
							}
							
							if ($valid)
							{
								if (!XXX_Client_Input::validateMaximumFileSize($maximumFileSize, $uploadedFile['size']))
								{
									$uploadedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'exceedsUploadMaxFilesizeServerDirective');
									
									$valid = false;
								}
							}
							
							if ($valid)
							{
								if (($uploadedFileTotal + 1) > $maximumFileTotal)
								{
									$uploadedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'exceedsMaxFileUploadsServerDirective');
									
									$valid = false;								
								}
							}
							
							if ($valid)
							{
								if (($uploadedFileSizeTotal + $uploadedFile['size']) > $maximumFileSizeTotal)
								{
									$uploadedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'exceedsMaximumFileSizeTotal');
									
									$valid = false;									
								}
							}
							
							if ($valid)
							{
								if (($uploadedFileSizeTotal + $uploadedFile['size']) > $maximumRequestSize)
								{
									$uploadedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'exceedsPostMaxSizeServerDirective');
									
									$valid = false;									
								}
							}
							
							if ($valid)
							{
								if (!XXX_Client_Input::validateFileExtension($acceptFileExtensions, $uploadedFile['extension']))
								{
									$uploadedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'unacceptedFileExtension');
									
									$valid = false;									
								}
							}
							
							if ($valid)
							{
								if (!XXX_Client_Input::validateFileMIMEType($acceptFileMIMETypes, $uploadedFile['mimeType']))
								{
									$uploadedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'unacceptedFileMIMEType');
									
									$valid = false;									
								}
							}
							
							
							++$fileTotal;
							$fileSizeTotal += $uploadedFile['size'];
							
							if (!$valid)
							{
								++$failedFileTotal;
								$failedFileSizeTotal += $uploadedFile['size'];
								
								if (!XXX_Array::hasKey($newFailedFiles, $inputName))
								{
									$newFailedFiles[$inputName] = array();
								}
								
								$newFailedFiles[$inputName][] = $uploadedFile;
								
								XXX_FileSystem_Local::deleteFile($uploadedFile['temporaryFile']);
							}
							else
							{
								++$uploadedFileTotal;
								$uploadedFileSizeTotal += $uploadedFile['size'];
								
								if (!XXX_Array::hasKey($newUploadedFiles, $inputName))
								{
									$newUploadedFiles[$inputName] = array();
								}
								
								$newUploadedFiles[$inputName][] = $uploadedFile;
							}
						}
					}
				}
			}
			
			if (XXX_Array::getFirstLevelItemTotal($files['files']['failed']) > 0)
			{
				foreach ($files['files']['failed'] as $inputName => $inputFiles)
				{
					if (XXX_Array::getFirstLevelItemTotal($inputFiles) > 0)
					{
						foreach ($inputFiles as $failedFile)
						{
							++$fileTotal;
							$fileSizeTotal += $failedFile['size'];
							
							++$failedFileTotal;
							$failedFileSizeTotal += $failedFile['size'];
							
							if (!XXX_Array::hasKey($newFailedFiles, $inputName))
							{
								$newFailedFiles[$inputName] = array();
							}
							
							$newFailedFiles[$inputName][] = $failedFile;
						}
					}
				}
			}
			
			
			
			$files['files']['failed'] = $newFailedFiles;
			$files['files']['uploaded'] = $newUploadedFiles;
			
			$files['fileTotal'] = $fileTotal;
			$files['failedFileTotal'] = $failedFileTotal;
			$files['uploadedFileTotal'] = $uploadedFileTotal;
						
			$files['fileSizeTotal'] = $fileSizeTotal;
			$files['failedFileSizeTotal'] = $failedFileSizeTotal;
			$files['uploadedFileSizeTotal'] = $uploadedFileSizeTotal;
			
		
		return $files;
	}
	
		public static function splitParsedFileUploadsByInputName ($parsedFileUploads)
		{
			$parsedSplitFileUploads = array();
			
			// Per input		
			
				// Make available on a per input name basis
				foreach ($parsedFileUploads['inputNames'] as $inputName)
				{
					$failedFiles = array();
					$uploadedFiles = array();
					
					$fileTotal = 0;
					$failedFileTotal = 0;
					$uploadedFileTotal = 0;
					
					$fileSizeTotal = 0;
					$failedFileSizeTotal = 0;
					$uploadedFileSizeTotal = 0;
					
					$manipulated = false;
								
					if ($parsedFileUploads['files']['failed'][$inputName])
					{
						foreach ($parsedFileUploads['files']['failed'][$inputName] as $failedFile)
						{
							$failedFiles[] = $failedFile;
							
							if ($failedFile['manipulated'])
							{
								$manipulated = true;
							}
							
							$fileSizeTotal += $failedFile['size'];
							$failedFileSizeTotal += $failedFile['size'];
							
							++$fileTotal;
							++$failedFileTotal;
						}
					}
					
					if ($parsedFileUploads['files']['uploaded'][$inputName])
					{
						foreach ($parsedFileUploads['files']['uploaded'][$inputName] as $uploadedFile)
						{
							$uploadedFiles[] = $uploadedFile;
							
							$fileSizeTotal += $uploadedFile['size'];
							$uploadedFileSizeTotal += $uploadedFile['size'];
							
							++$fileTotal;
							++$uploadedFileTotal;
						}
					}
								
					$parsedSplitFileUploads[$inputName] = array
					(
						'inputName' => $inputName,
						
						'files' => array
						(
							'uploaded' => $uploadedFiles,
							'failed' => $failedFiles
						),
						
						'fileTotal' => $fileTotal,
						'failedFileTotal' => $failedFileTotal,
						'uploadedFileTotal' => $uploadedFileTotal,
						
						'fileSizeTotal' => $fileSizeTotal,
						'failedFileSizeTotal' => $failedFileSizeTotal,
						'uploadedFileSizeTotal' => $uploadedFileSizeTotal,
						
						'manipulated' => $manipulated,
						
						'withinRequestBodyLimits' => self::$withinRequestBodyLimits,
						'error' => $parsedFileUploads['error']
					);
				}
				
			return $parsedSplitFileUploads;
		}
		
		public static function combineParsedFileUploads ($parsedFileUploads)
		{
			$parsedCombinedFileUploads = array();
			
			$failedFiles = array();
			$uploadedFiles = array();
			
			foreach ($parsedFileUploads['files']['failed'] as $tempFailedFiles)
			{
				foreach ($tempFailedFiles as $failedFile)
				{
					$failedFiles[] = $failedFile;
				}
			}
			
			foreach ($parsedFileUploads['files']['uploaded'] as $tempUploadedFiles)
			{
				foreach ($tempUploadedFiles as $uploadedFile)
				{
					$uploadedFiles[] = $uploadedFile;
				}
			}
			
			$parsedCombinedFileUploads = $parsedFileUploads;
			$parsedCombinedFileUploads['files']['uploaded'] = $uploadedFiles;
			$parsedCombinedFileUploads['files']['failed'] = $failedFiles;
				
			return $parsedCombinedFileUploads;
		}
	
	
	
	
	
	
	
	// Only split input or combined parsedFileUploads
	public static function filterFileUploadsWithEffectiveHTTPServer_Client_Input_Limits ($fileUploads = array(), $customHTTPServer_Client_Input_Limits = array())
	{
		$filteredFileUploads = array();
		
		
			$failedFiles = array();
			$uploadedFiles = array();
			
			$fileTotal = 0;
			$failedFileTotal = 0;
			$uploadedFileTotal = 0;
			
			$fileSizeTotal = 0;
			$failedFileSizeTotal = 0;
			$uploadedFileSizeTotal = 0;
												
			for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($fileUploads['files']['uploaded']); $i < $iEnd; ++$i)
			{
				$uploadedFile = $fileUploads['files']['uploaded'][$i];
				
				$valid = true;
				
				if ($valid)
				{
					if (!$customHTTPServer_Client_Input_Limits['acceptFileUpload'])
					{
						$uploadedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'unacceptedFileUpload');
						
						$valid = false;									
					}
				}
				
				if ($valid)
				{
					if (XXX_Type::isPositiveInteger($customHTTPServer_Client_Input_Limits['minimumFileSize']) && !XXX_Client_Input::validateMinimumFileSize($customHTTPServer_Client_Input_Limits['minimumFileSize'], $uploadedFile['size']))
					{
						$uploadedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'underMinimumFileSize');
						
						$valid = false;									
					}
				}
				
				if ($valid)
				{
					if (XXX_Type::isPositiveInteger($customHTTPServer_Client_Input_Limits['maximumFileSize']) && !XXX_Client_Input::validateMaximumFileSize($customHTTPServer_Client_Input_Limits['maximumFileSize'], $uploadedFile['size']))
					{
						$uploadedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'exceedsMaximumFileSize');
						
						$valid = false;									
					}
				}
				
				if ($valid)
				{
					if (XXX_Type::isPositiveInteger($customHTTPServer_Client_Input_Limits['maximumFileTotal']) && !XXX_Client_Input::validateMaximumFileTotal($customHTTPServer_Client_Input_Limits['maximumFileTotal'], ($fileTotal + 1)))
					{
						$uploadedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'exceedsMaximumFileTotal');
						
						$valid = false;									
					}
				}
				
				if ($valid)
				{
					if (XXX_Type::isPositiveInteger($customHTTPServer_Client_Input_Limits['maximumFileSizeTotal']) && !XXX_Client_Input::validateMaximumFileSizeTotal($customHTTPServer_Client_Input_Limits['maximumFileSizeTotal'], ($uploadedFileSizeTotal + $uploadedFile['size'])))
					{
						$uploadedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'exceedsMaximumFileSizeTotal');
						
						$valid = false;									
					}
				}
				
				if ($valid)
				{
					if (!$customHTTPServer_Client_Input_Limits['acceptAnyFileExtension'] && !XXX_Client_Input::validateFileExtension($customHTTPServer_Client_Input_Limits['acceptFileExtensions'], $uploadedFile['extension']))
					{
						$uploadedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'unacceptedFileExtension');
						
						$valid = false;
					}
				}
				
				if ($valid)
				{
					if (!$customHTTPServer_Client_Input_Limits['acceptAnyFileMIMEType'] && !XXX_Client_Input::validateFileMIMEType($customHTTPServer_Client_Input_Limits['acceptFileMIMETypes'], $uploadedFile['mimeType']))
					{
						$uploadedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'unacceptedFileMIMEType');
						
						$valid = false;
					}
				}
								
				++$fileTotal;
				$fileSizeTotal += $uploadedFile['size'];
				
				if (!$valid)
				{
					++$failedFileTotal;
					$failedFileSizeTotal += $uploadedFile['size'];
										
					$failedFiles[] = $uploadedFile;
					
					XXX_FileSystem_Local::deleteFile($uploadedFile['temporaryFile']);
				}
				else
				{
					++$uploadedFileTotal;
					$uploadedFileSizeTotal += $uploadedFile['size'];
					
					$uploadedFiles[] = $uploadedFile;					
				}
			}
			
			for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($fileUploads['files']['failed']); $i < $iEnd; ++$i)
			{
				$failedFile = $fileUploads['files']['failed'][$i];

				++$fileTotal;
				$fileSizeTotal += $failedFile['size'];
				
				++$failedFileTotal;
				$failedFileSizeTotal += $failedFile['size'];
				
				$failedFiles[] = $failedFile;
			}
			
			// Events
				
				for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($uploadedFiles); $i < $iEnd; ++$i)
				{
					$uploadedFile = $uploadedFiles[$i];
					
					XXX::dispatchEventToListeners('uploadedFile', $uploadedFile);
				}
			
				for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($failedFiles); $i < $iEnd; ++$i)
				{
					$failedFile = $failedFiles[$i];
					
					if ($failedFile['error']['code'] != 4)
					{
						XXX::dispatchEventToListeners('failedFile', $failedFile);
					}				
				}
			
			
		$filteredFileUploads = array
		(
			'files' => array
			(
				'uploaded' => $uploadedFiles,
				'failed' => $failedFiles
			),
			
			'fileTotal' => $fileTotal,
			'failedFileTotal' => $failedFileTotal,
			'uploadedFileTotal' => $uploadedFileTotal,
			
			'fileSizeTotal' => $fileSizeTotal,
			'failedFileSizeTotal' => $failedFileSizeTotal,
			'uploadedFileSizeTotal' => $uploadedFileSizeTotal,
			
			'manipulated' => $fileUploads['manipulated'],
			
			'withinRequestBodyLimits' => $fileUploads['withinRequestBodyLimits'],
			'error' => $fileUploads['error']
		);
		
		return $filteredFileUploads;
	}
	
	public static function filterFileUploadsAfterApplicationProcessing ($fileUploads = array())
	{
		$filteredFileUploads = array();
		
			$failedFiles = array();
			$uploadedFiles = array();
			
			$fileTotal = 0;
			$failedFileTotal = 0;
			$uploadedFileTotal = 0;
			
			$fileSizeTotal = 0;
			$failedFileSizeTotal = 0;
			$uploadedFileSizeTotal = 0;
									
			for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($fileUploads['files']['uploaded']); $i < $iEnd; ++$i)
			{
				$uploadedFile = $fileUploads['files']['uploaded'][$i];
				
				$valid = true;
								
				if ($valid)
				{
					if (!$uploadedFile['valid'])
					{
						$valid = false;
						
						if (!XXX_Type::isFilledArray($uploadedFile['error']))
						{
							$uploadedFile['error'] = XXX_I18n_Translation::get('HTTPServer_Client_Input', 'errors', 'unknown');						
						}
					}
					else if (XXX_Type::isFilledArray($uploadedFile['error']))
					{
						$valid = false;
						
						$uploadedFile['valid'] = false;
					}
				}
				
				++$fileTotal;
				$fileSizeTotal += $uploadedFile['size'];
				
				if (!$valid)
				{
					++$failedFileTotal;
					$failedFileSizeTotal += $uploadedFile['size'];
										
					$failedFiles[] = $uploadedFile;
					
					XXX_FileSystem_Local::deleteFile($uploadedFile['temporaryFile']);
				}
				else
				{
					++$uploadedFileTotal;
					$uploadedFileSizeTotal += $uploadedFile['size'];
					
					$uploadedFiles[] = $uploadedFile;
				}
			}
			
			for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($fileUploads['files']['failed']); $i < $iEnd; ++$i)
			{
				$failedFile = $fileUploads['files']['failed'][$i];

				++$fileTotal;
				$fileSizeTotal += $failedFile['size'];
				
				++$failedFileTotal;
				$failedFileSizeTotal += $failedFile['size'];
				
				$failedFiles[] = $failedFile;
			}
			
		$filteredFileUploads = array
		(
			'files' => array
			(
				'uploaded' => $uploadedFiles,
				'failed' => $failedFiles
			),
			
			'fileTotal' => $fileTotal,
			'failedFileTotal' => $failedFileTotal,
			'uploadedFileTotal' => $uploadedFileTotal,
			
			'fileSizeTotal' => $fileSizeTotal,
			'failedFileSizeTotal' => $failedFileSizeTotal,
			'uploadedFileSizeTotal' => $uploadedFileSizeTotal,
			
			'manipulated' => $fileUploads['manipulated'],
			
			'withinRequestBodyLimits' => $fileUploads['withinRequestBodyLimits'],
			'error' => $fileUploads['error']
		);
		
		return $filteredFileUploads;
	}
	
	
	
	public static function composeEffectiveHTTPServer_Client_Input_Limits ($HTTPServer_Client_Input_LimitsProfile = '')
	{
		// See if submitted with form
		if ($HTTPServer_Client_Input_LimitsProfile == '')
		{
			$HTTPServer_Client_Input_LimitsProfile = self::getBodyVariable('HTTPServer_Client_Input_Limits_PROFILE', 'string', 'default');
		}
		
		// Only existing
		if (!XXX_Array::hasKey(self::$profiles, $HTTPServer_Client_Input_LimitsProfile))
		{
			$HTTPServer_Client_Input_LimitsProfile = 'default';
		}
			
		$acceptFileUpload = false;
		
		$minimumFileSize = 0;
		$maximumFileSize = 0;
		
		$maximumFileTotal = 0;
		
		$maximumFileSizeTotal = 0;
		
		$maximumRequestSize = 0;
		
		$maximumInputTime = 0;
		
		$acceptFileExtensions = array();
		$acceptAnyFileExtension = false;
		$acceptFileMIMETypes = array();
		$acceptAnyFileMIMEType = false;
		
		$profile = $HTTPServer_Client_Input_LimitsProfile;
		
		$disabledReason = false;
		
		// Determine the most restrictive limits (server configuration or profile)
		
			$phpHTTPServer_Client_Input_Limits = XXX_PHP::getHTTPServer_Client_Input_Limits();
		
			if ($phpHTTPServer_Client_Input_Limits)
			{
				$acceptFileUpload = $phpHTTPServer_Client_Input_Limits['acceptFileUpload'];
				
				$minimumFileSize = $phpHTTPServer_Client_Input_Limits['minimumFileSize'];
				$maximumFileSize = $phpHTTPServer_Client_Input_Limits['maximumFileSize'];
				
				$maximumFileTotal = $phpHTTPServer_Client_Input_Limits['maximumFileTotal'];
				
				$maximumFileSizeTotal = $phpHTTPServer_Client_Input_Limits['maximumFileSizeTotal'];
				
				$maximumRequestSize = $phpHTTPServer_Client_Input_Limits['maximumRequestSize'];
				
				$maximumInputTime = $phpHTTPServer_Client_Input_Limits['maximumInputTime'];
				
				$acceptFileExtensions = $phpHTTPServer_Client_Input_Limits['acceptFileExtensions'];
				
				$acceptFileMIMETypes = $phpHTTPServer_Client_Input_Limits['acceptFileMIMETypes'];
				
				$disabledReason = 'phpHTTPServer_Client_Input_Limits';
			}
			
			$HTTPServer_Client_Input_LimitsProfile = self::$profiles[$HTTPServer_Client_Input_LimitsProfile];
			
			if (XXX_Type::isBoolean($HTTPServer_Client_Input_LimitsProfile['acceptFileUpload']) && $acceptFileUpload && !$HTTPServer_Client_Input_LimitsProfile['acceptFileUpload'])
			{
				$acceptFileUpload = $HTTPServer_Client_Input_LimitsProfile['acceptFileUpload'];
				
				$disabledReason = 'HTTPServer_Client_Input_LimitsProfile';
			}
						
			if (XXX_Type::isPositiveInteger($HTTPServer_Client_Input_LimitsProfile['minimumFileSize']))
			{
				$minimumFileSize = $HTTPServer_Client_Input_LimitsProfile['minimumFileSize'];
			}
			
			if (XXX_Type::isPositiveInteger($HTTPServer_Client_Input_LimitsProfile['maximumFileSize']) && $HTTPServer_Client_Input_LimitsProfile['maximumFileSize'] <= $maximumFileSize)
			{
				$maximumFileSize = $HTTPServer_Client_Input_LimitsProfile['maximumFileSize'];
			}
						
			if (XXX_Type::isPositiveInteger($HTTPServer_Client_Input_LimitsProfile['maximumFileTotal']) && $HTTPServer_Client_Input_LimitsProfile['maximumFileTotal'] <= $maximumFileTotal)
			{
				$maximumFileTotal = $HTTPServer_Client_Input_LimitsProfile['maximumFileTotal'];
			}
			
			if (XXX_Type::isPositiveInteger($HTTPServer_Client_Input_LimitsProfile['maximumFileSizeTotal']) && $HTTPServer_Client_Input_LimitsProfile['maximumFileSizeTotal'] <= $maximumFileSizeTotal)
			{
				$maximumFileSizeTotal = $HTTPServer_Client_Input_LimitsProfile['maximumFileSizeTotal'];
			}
			
			if (XXX_Type::isPositiveInteger($HTTPServer_Client_Input_LimitsProfile['maximumRequestSize']) && $HTTPServer_Client_Input_LimitsProfile['maximumRequestSize'] <= $maximumRequestSize)
			{
				$maximumRequestSize = $HTTPServer_Client_Input_LimitsProfile['maximumRequestSize'];
			}
			
			if (XXX_Type::isPositiveInteger($HTTPServer_Client_Input_LimitsProfile['maximumInputTime']) && $HTTPServer_Client_Input_LimitsProfile['maximumInputTime'] <= $maximumInputTime)
			{
				$maximumInputTime = $HTTPServer_Client_Input_LimitsProfile['maximumInputTime'];
			}
			
		// File extensions
		
			if ($acceptFileUpload)
			{			
				// Nothing allowed
				if (XXX_Array::getFirstLevelItemTotal($acceptFileExtensions) == 0 || (XXX_Array::getFirstLevelItemTotal($acceptFileExtensions) == 1 && $acceptFileExtensions[0] == ''))
				{
					$acceptFileUpload = false;
					
					$disabledReason = 'noFileExtensions';
				}
				// All allowed, see if HTTPServer_Client_Input_LimitsProfile is more specific
				else if (XXX_Type::isArray($acceptFileExtensions) && XXX_Array::getFirstLevelItemTotal($acceptFileExtensions) == 1 && $acceptFileExtensions[0] == '*')
				{				
					if (XXX_Type::isArray($HTTPServer_Client_Input_LimitsProfile['acceptFileExtensions']))
					{
						// Nothing allowed
						if (XXX_Array::getFirstLevelItemTotal($HTTPServer_Client_Input_LimitsProfile['acceptFileExtensions']) == 0 || (XXX_Array::getFirstLevelItemTotal($HTTPServer_Client_Input_LimitsProfile['acceptFileExtensions']) == 1 && $HTTPServer_Client_Input_LimitsProfile['acceptFileExtensions'][0] == ''))
						{
							$acceptFileExtensions = array();
							
							$acceptFileUpload = false;
							
							$disabledReason = 'noFileExtensions';
						}
						// The same
						else if (XXX_Array::getFirstLevelItemTotal($HTTPServer_Client_Input_LimitsProfile['acceptFileExtensions']) == 1 && $HTTPServer_Client_Input_LimitsProfile['acceptFileExtensions'][0] == '*')
						{
						}
						// More specific
						else
						{
							$acceptFileExtensions = $HTTPServer_Client_Input_LimitsProfile['acceptFileExtensions'];
						}
					}
				}
				// Specific
				else
				{
					if (XXX_Type::isArray($HTTPServer_Client_Input_LimitsProfile['acceptFileExtensions']))
					{
						// Nothing allowed
						if (XXX_Array::getFirstLevelItemTotal($HTTPServer_Client_Input_LimitsProfile['acceptFileExtensions']) == 0 || (XXX_Array::getFirstLevelItemTotal($HTTPServer_Client_Input_LimitsProfile['acceptFileExtensions']) == 1 && $HTTPServer_Client_Input_LimitsProfile['acceptFileExtensions'][0] == ''))
						{
							$acceptFileExtensions = array();
							
							$acceptFileUpload = false;
							
							$disabledReason = 'noFileExtensions';
						}
						// Only accept the limited ones
						else if (XXX_Array::getFirstLevelItemTotal($HTTPServer_Client_Input_LimitsProfile['acceptFileExtensions']) == 1 && $HTTPServer_Client_Input_LimitsProfile['acceptFileExtensions'][0] == '*')
						{
						}
						// Filter those within limited ones
						else
						{
							$temp = array();
							
							for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($HTTPServer_Client_Input_LimitsProfile['acceptFileExtensions']); $i < $iEnd; ++$i)
							{
								if (XXX_Client_Input::validateFileExtension($acceptFileExtensions, $HTTPServer_Client_Input_LimitsProfile['acceptFileExtensions'][$i]))
								{
									$temp[] = $HTTPServer_Client_Input_LimitsProfile['acceptFileExtensions'][$i];
								}
							}
							
							$acceptFileExtensions = $temp;
						}
					}
				}
			}
			else
			{
				$acceptFileExtensions = array();
			}
			
			if (XXX_Type::isArray($acceptFileExtensions) && XXX_Array::getFirstLevelItemTotal($acceptFileExtensions) == 1 && $acceptFileExtensions[0] == '*')
			{
				$acceptAnyFileExtension = true;
			}
		
		// Accept file mime types
		
			if ($acceptFileUpload)
			{
				// Nothing allowed
				if (XXX_Array::getFirstLevelItemTotal($acceptFileMIMETypes) == 0 || (XXX_Array::getFirstLevelItemTotal($acceptFileMIMETypes) == 1 && $acceptFileMIMETypes[0] == ''))
				{
					$acceptFileUpload = false;
					
					$disabledReason = 'noFileMIMETypes';
				}
				// All allowed, see if HTTPServer_Client_Input_LimitsProfile is more specific
				else if (XXX_Type::isArray($acceptFileMIMETypes) && XXX_Array::getFirstLevelItemTotal($acceptFileMIMETypes) == 1 && $acceptFileMIMETypes[0] == '*/*')
				{				
					if (XXX_Type::isArray($HTTPServer_Client_Input_LimitsProfile['acceptFileMIMETypes']))
					{
						// Nothing allowed
						if (XXX_Array::getFirstLevelItemTotal($HTTPServer_Client_Input_LimitsProfile['acceptFileMIMETypes']) == 0 || (XXX_Array::getFirstLevelItemTotal($HTTPServer_Client_Input_LimitsProfile['acceptFileMIMETypes']) == 1 && $HTTPServer_Client_Input_LimitsProfile['acceptFileMIMETypes'][0] == ''))
						{
							$acceptFileMIMETypes = array();
							
							$acceptFileUpload = false;
							
							$disabledReason = 'noFileMIMETypes';
						}
						// The same
						else if (XXX_Array::getFirstLevelItemTotal($HTTPServer_Client_Input_LimitsProfile['acceptFileMIMETypes']) == 1 && $HTTPServer_Client_Input_LimitsProfile['acceptFileMIMETypes'][0] == '*/*')
						{
						}
						// More specific
						else
						{
							$acceptFileMIMETypes = $HTTPServer_Client_Input_LimitsProfile['acceptFileMIMETypes'];
						}
					}
				}
				// Specific
				else
				{
					if (XXX_Type::isArray($HTTPServer_Client_Input_LimitsProfile['acceptFileMIMETypes']))
					{
						// Nothing allowed
						if (XXX_Array::getFirstLevelItemTotal($HTTPServer_Client_Input_LimitsProfile['acceptFileMIMETypes']) == 0 || (XXX_Array::getFirstLevelItemTotal($HTTPServer_Client_Input_LimitsProfile['acceptFileMIMETypes']) == 1 && $HTTPServer_Client_Input_LimitsProfile['acceptFileMIMETypes'][0] == ''))
						{
							$acceptFileMIMETypes = array();
							
							$acceptFileUpload = false;
							
							$disabledReason = 'noFileMIMETypes';
						}
						// Only accept the limited ones
						else if (XXX_Array::getFirstLevelItemTotal($HTTPServer_Client_Input_LimitsProfile['acceptFileMIMETypes']) == 1 && $HTTPServer_Client_Input_LimitsProfile['acceptFileMIMETypes'][0] == '*/*')
						{
						}
						// Filter those within limited ones
						else
						{
							$temp = array();
							
							for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($HTTPServer_Client_Input_LimitsProfile['acceptFileMIMETypes']); $i < $iEnd; ++$i)
							{
								if (XXX_Client_Input::validateFileMIMEType($acceptFileMIMETypes, $HTTPServer_Client_Input_LimitsProfile['acceptFileMIMETypes'][$i]))
								{
									$temp[] = $HTTPServer_Client_Input_LimitsProfile['acceptFileMIMETypes'][$i];
								}
							}
							
							$acceptFileMIMETypes = $temp;
						}
					}
				}
			}
			else
			{
				$acceptFileMIMETypes = array();
			}
					
			if (XXX_Type::isArray($acceptFileMIMETypes) && XXX_Array::getFirstLevelItemTotal($acceptFileMIMETypes) == 1 && $acceptFileMIMETypes[0] == '*/*')
			{
				$acceptAnyFileMIMEType = true;
			}
		
		// Last correction
		
			if ($acceptFileUpload)
			{
				if ($maximumFileSize > $maximumFileSizeTotal)
				{
					$maximumFileSize = $maximumFileSizeTotal;
				}
				
				if ($minimumFileSize < 0)
				{
					$minimumFileSize = 0;
				}
				
				if ($minimumFileSize > $maximumFileSize)
				{
					$minimumFileSize = $maximumFileSize;
				}
				
				if ($minimumFileSize * $maximumFileTotal > $maximumFileSizeTotal)
				{
					$maximumFileTotal = XXX_Number.floor($maximumFileSizeTotal / $minimumFileSize);
				}
				
				if ($maximumFileSize * $maximumFileTotal < $maximumFileSizeTotal)
				{
					$maximumFileSizeTotal = $maximumFileSize * $maximumFileTotal;
				}
				
				if ($maximumFileSizeTotal == 0)
				{
					$acceptFileUpload = false;
					
					$disabledReason = 'noMaximumFileSizeTotal';
				}
				else if ($maximumFileSize == 0)
				{
					$acceptFileUpload = false;
					
					$disabledReason = 'noMaximumFileSize';
				}
				else if ($maximumFileTotal == 0)
				{
					$acceptFileUpload = false;
					
					$disabledReason = 'noMaximumFileTotal';
				}
			}
					
			if (!$acceptFileUpload)
			{
				$minimumFileSize = 0;
				$maximumFileSize = 0;
				
				$maximumFileTotal = 0;
			
				$maximumFileSizeTotal = 0;
				
				$acceptFileExtensions = array();
				$acceptAnyFileExtension = false;
				$acceptFileMIMETypes = array();
				$acceptAnyFileMIMEType = false;
			}
			else
			{
				$disabledReason = false;
			}
					
		$limits = array
		(			
			'acceptFileUpload' => $acceptFileUpload,
			
			'minimumFileSize' => $minimumFileSize,
			'maximumFileSize' => $maximumFileSize,
			
			'maximumFileTotal' => $maximumFileTotal,
			
			'maximumFileSizeTotal' => $maximumFileSizeTotal,
			
			'maximumRequestSize' => $maximumRequestSize,
			
			'maximumInputTime' => $maximumInputTime,
			
			'acceptFileExtensions' => $acceptFileExtensions,
			'acceptAnyFileExtension' => $acceptAnyFileExtension,
			'acceptFileMIMETypes' => $acceptFileMIMETypes,
			'acceptAnyFileMIMEType' => $acceptAnyFileMIMEType,
			
			'profile' => $profile,
			
			'disabledReason' => $disabledReason
		);
				
		return $limits;
	}
	
	public static function filterFileUploadResponseForClientSide ($fileUploadResponse)
	{
		if (XXX_Type::isArray($fileUploadResponse))
		{
			if (XXX_Array::getFirstLevelItemTotal($fileUploadResponse['files']['uploaded']))
			{
				for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($fileUploadResponse['files']['uploaded']); $i < $iEnd; ++$i)
				{
					$uploadedFile = $fileUploadResponse['files']['uploaded'][$i];
					
					$fileUploadResponse['files']['uploaded'][$i] = array
					(
						'file' => $uploadedFile['file'],
						'name' => $uploadedFile['name'],
						'extension' => $uploadedFile['extension'],
						'size' => $uploadedFile['size'],
						'mimeType' => $uploadedFile['mimeType'],
						'checksum' => $uploadedFile['checksum']
					);
					
					$fileUploadResponse['files']['uploaded'][$i]['publicWebURI'] = $uploadedFile['publicWebURI'];
					$fileUploadResponse['files']['uploaded'][$i]['ID'] = $uploadedFile['ID'];
					$fileUploadResponse['files']['uploaded'][$i]['hash'] = $uploadedFile['hash'];
					$fileUploadResponse['files']['uploaded'][$i]['uploaded'] = $uploadedFile['uploaded'];
				}
			}
			
			if (XXX_Array::getFirstLevelItemTotal($fileUploadResponse['files']['failed']))
			{
				for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($fileUploadResponse['files']['failed']); $i < $iEnd; ++$i)
				{
					$failedFile = $fileUploadResponse['files']['failed'][$i];
					
					
					$fileUploadResponse['files']['failed'][$i] = array
					(
						'file' => $failedFile['file'],
						'name' => $failedFile['name'],
						'extension' => $failedFile['extension'],
						'size' => $failedFile['size'],
						'mimeType' => $failedFile['mimeType'],						
						// TODO potentially hide errors that might serve hacker purposes
						'error' => $failedFile['error']
					);
				}
			}
		}
		
		return $fileUploadResponse;
	}
}

?>