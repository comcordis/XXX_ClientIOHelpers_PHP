<?php

// TODO http://teddy.fr/blog/how-serve-big-files-through-php

abstract class XXX_HTTPServer_Client_Output
{
	public static $compressOutput = false;
	
	public static $mimeType = 'text/html';
	
	public static $headers = array();
	
	// CORS
	
		public static function detectPreflightCORSRequest ()
		{
			$result = false;
			
			if (strtolower($_SERVER['REQUEST_METHOD']) == 'options')
			{
				self::outputCORSHeaders();
				
				self::addHeader('Content-type', 'text/json; charset=utf-8');
				
				$result = true;
				
				exit();
			}
			
			return $result;
		}
		
		public static function outputCORSHeaders ()
		{
			if ($_SERVER['HTTP_ORIGIN'])
			{
				self::addHeader('Access-Control-Allow-Origin', $_SERVER['HTTP_ORIGIN']);
				self::addHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
				self::addHeader('Access-Control-Allow-Credentials', 'true');
				self::addHeader('Access-Control-Allow-Headers', $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
			}
		}
	
	public static function forceJSONResponse ($result = '')
	{
		self::outputCORSHeaders();
		
		$json = XXX_String_JSON::encode($result);
		
		// JSONP
		$jsonp = XXX_HTTPServer_Client_Input::getURIVariable('jsonp');
		$callback = XXX_HTTPServer_Client_Input::getURIVariable('callback');
		$function = XXX_HTTPServer_Client_Input::getURIVariable('function');
		
		if ($jsonp == '')
		{
			if ($callback != '')
			{
				$jsonp = $callback;
			}
			else if ($function != '')
			{
				$jsonp = $function;
			}
		}
		
		// text instead of application mimetypes for IE
		
		if ($jsonp != '')
		{
			self::setMIMETypeAndCharacterSet('text/plain');
		
			echo $jsonp . '(' . $json . ');';
		}
		else
		{
			//self::setMIMETypeAndCharacterSet('text/json');
			self::setMIMETypeAndCharacterSet('text/plain');
		
			echo $json;
		}
	}
	
	public static function prepareForFileServingOrDownload ($leaveOutputBuffer = false)
	{
		// Avoid any output
		error_reporting(0);
		
		ini_set('magic_quotes_runtime', 0);
		
		set_time_limit(0);
		
		// Disable Apache Gzip / output compression / output buffering
		
			if (function_exists('apache_setenv'))
			{
				apache_setenv('no-gzip', 1);
				apache_setenv('dont-vary', 1);
			}
		
			if (XXX_HTTPServer_Client::$browser == 'internetExplorer')
	        {
	        	// Required for IE, otherwise Content-Disposition may be ignored
	        	if(ini_get('zlib.output_compression'))
				{
					ini_set('zlib.output_compression', 'Off');
				}
			}
		
		if (!$leaveOutputBuffer)
		{
			// Turn off output buffering to decrease cpu usage
			ob_end_clean();
		}
	}
		
	public static function processDownloadHeaders ($fileIdentifier = '', $byteSize = 0, $mimeType = 'application/octet-stream', $fileModifiedTimestamp = false)
	{
		$result = false;
		
		// TODO If you want to do something on download abort/finish, use register_shutdown_function('function_name');
		
		if ($fileModifiedTimestamp == false)
		{
			$fileModifiedTimestamp = XXX_TimestampHelpers::getCurrentTimestamp() + (86400 * 365);
		}
		
		self::prepareForFileServingOrDownload(self::$compressOutput);
				
		if(XXX_HTTPServer_Client_Input::$onlyIfModifiedSinceTimestamp == $fileModifiedTimestamp)
		{
			self::sendNotModifiedHeader();
		}
		else
		{
			self::addHeader('Content-Type', 'application/force-download');
			self::addHeader('Content-Description', 'File Transfer');
			self::addHeader('Last-Modified', gmdate('D, d M Y H:i:s', $fileModifiedTimestamp) . ' GMT');
			
			$isPartial = XXX_HTTPServer_Client_Input::$responseRange !== false;
			
			$seekStart = 0;
			$seekEnd = 0;
			
			if ($isPartial)
			{
				$seekStart = XXX_HTTPServer_Client_Input::$responseRange['seekStart'];
				$seekEnd = XXX_HTTPServer_Client_Input::$responseRange['seekEnd'];
				
				if ($seekStart > 0 || $seekEnd < ($byteSize - 1))
		        {
		            self::sendHeader(XXX_HTTPServer_Client::$requestProtocolAndVersionPrefix . ' 206 Partial Content');
		        }
		
		        self::addHeader('Accept-Ranges', 'bytes');
		        self::addHeader('Content-Range', 'bytes ' . $seekStart . '-' . $seekEnd . '/' . $byteSize);
			}
			
	        if (XXX_HTTPServer_Client::$browser == 'internetExplorer')
	        {
	        	// encode dots in filenames with an extra dot in it... e.g. some.file.ext 
	        	$fileIdentifier = preg_replace('/\./', '%2e', $fileIdentifier, substr_count($fileIdentifier, '.') - 1);
	        	
	        	self::addHeader('Pragma: public');
				self::addHeader('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	        }
        	
			self::setMIMETypeAndCharacterSet($mimeType);	
			self::addHeader('Content-Disposition', 'attachment; filename="' . $fileIdentifier . '"');
			self::addHeader('Content-Transfer-Encoding', 'binary');
			self::addHeader('Content-Length', $byteSize);
			
			self::addHeader('Connection', 'close');
			
			// Cache invalidation
			self::sendNotCacheableHeaders();
			
			$result = array
			(
				'partial' => $isPartial,
				'seekStart' => $seekStart,
				'seekEnd' => $seekEnd
			);
		}
		
		return $result;
	}
	
	public static function processServeHeaders ($byteSize = 0, $mimeType = 'application/octet-stream', $fileModifiedTimestamp = false)
	{
		$result = false;
		
		if ($fileModifiedTimestamp == false)
		{
			$fileModifiedTimestamp = XXX_TimestampHelpers::getCurrentTimestamp() + (86400 * 365);
		}
		
		self::prepareForFileServingOrDownload(self::$compressOutput);
		
		if(XXX_HTTPServer_Client_Input::$onlyIfModifiedSinceTimestamp == $fileModifiedTimestamp)
		{
			self::sendNotModifiedHeader();
		}
		else
		{
			self::addHeader('Last-Modified', gmdate('D, d M Y H:i:s', $fileModifiedTimestamp) . ' GMT');
			self::addHeader('Expires', gmdate('D, d M Y H:i:s', time() + (86400 * 365)) . ' GMT');
			self::setMIMETypeAndCharacterSet($mimeType);
			self::addHeader('Content-Length', $byteSize);
			
			if (class_exists('XXX_Session'))
			{
				XXX::dispatchEventToListeners('beforeSaveSession');
				XXX_Session::save();
			}
			
			self::addHeader('Connection', 'close');
			
			$result = true;
		}
		
		return $result;
	}
		
	// Direct, Multipart or Resume
	
		public static function downloadFile ($file = '', $fileIdentifier = '', $chunkSize = 8192)
		{
			$result = false;
			
			if (XXX_FileSystem_Local::doesFileExist($file))
			{
				if ($fileIdentifier == '')
				{
					$fileIdentifier = XXX_Path_Local::getIdentifier($file);
				}
				
				$mimeType = self::determineAppropriateMIMEType($file);
				
				$mimeTypeParts = explode('/', $mimeType);
				if ($mimeTypeParts[0] != 'text')
				{
					self::$compressOutput = false;
				}
				$byteSize = XXX_FileSystem_Local::getFileSize($file);
				$fileModifiedTimestamp = XXX_FileSystem_Local::getFileModifiedTimestamp($file);
				
				$temp = self::processDownloadHeaders($fileIdentifier, $byteSize, $mimeType, $fileModifiedTimestamp);
				
				if ($temp)
				{
					$seekEnd = $temp['seekEnd'];
					
					$fileStream = XXX_FileSystem_Local::fileStream_openForReading($file, false);
					
					if ($fileStream)
					{
						if ($temp['seekStart'] > 0)
						{
							XXX_FileSystem_Local::fileStream_setReadOffset($fileStream, $temp['seekStart']);
						}
												
						// TODO seekEnd... (part not ending at the end)
						while (!XXX_HTTPServer_Client::isDisconnected() && !XXX_FileSystem_Local::fileStream_hasReadReachedEnd($fileStream))
						{
							$result = true;
						
							$buffer = XXX_FileSystem_Local::fileStream_readChunk($fileStream, $chunkSize);
							
							if ($buffer)
							{
								echo $buffer;
								flush();
							}
							else
							{
								break;
							}
						}
						
						XXX_FileSystem_Local::fileStream_close($fileStream);
					}
				}
			}
			
			return $result;
		}
		
		public static function serveFile ($file = '', $chunkSize = 8192)
		{
			$result = false;
			
			if (!XXX_FileSystem_Local::doesFileExist($file))
			{
				self::sendNotFoundHeader();
			}
			else
			{
				$fileIdentifier = XXX_Path_Local::getIdentifier($file);
				
				$fileModifiedTimestamp = XXX_FileSystem_Local::getFileModifiedTimestamp($file);
								
				$mimeType = self::determineAppropriateMIMEType($file);
				
				
				self::addHeader('X-Vince', 'Hello');
				//self::$compressOutput = false;
				
				$byteSize = XXX_FileSystem_Local::getFileSize($file);
				
				$mimeTypeParts = explode('/', $mimeType);
				
				if ($mimeTypeParts[0] != 'text')
				{
					self::$compressOutput = false;
				}
				
		XXX_Log::logLine('A1' . (XXX_HTTPServer_Client_Output::areHeadersSent() ? 'Headers already sent' : 'Headers not sent yet'), 'headers');
		XXX_Log::saveBuffers();
				
				$temp = self::processServeHeaders($byteSize, $mimeType, $fileModifiedTimestamp);
				
		XXX_Log::logLine('A2' . (XXX_HTTPServer_Client_Output::areHeadersSent() ? 'Headers already sent' : 'Headers not sent yet'), 'headers');
		XXX_Log::saveBuffers();
				if ($temp)
				{							   				
	   				$fileStream = XXX_FileSystem_Local::fileStream_openForReading($file, false);
				
					if ($fileStream)
					{
						while (!XXX_HTTPServer_Client::isDisconnected() && !XXX_FileSystem_Local::fileStream_hasReadReachedEnd($fileStream))
						{
							$result = true;
						
							$buffer = XXX_FileSystem_Local::fileStream_readChunk($fileStream, $chunkSize);
							
							if ($buffer)
							{
								echo $buffer;
								flush();
							}
							else
							{
								break;
							}
						}
						
						XXX_FileSystem_Local::fileStream_close($fileStream);
					}
				}
				
				
		XXX_Log::logLine('A3' . (XXX_HTTPServer_Client_Output::areHeadersSent() ? 'Headers already sent' : 'Headers not sent yet'), 'headers');
		XXX_Log::saveBuffers();
			}
			
			return $result;
		}
				
		public static function serveFileFromBasePath ($basePath, $subPath)
		{
			$tempFile = XXX_Path_Local::extendPath($basePath, $subPath);
		
			return self::serveFile($tempFile);
		}
		
		public static function serveFiles ($files = array())
		{
			$fileModifiedTimestamp = 0;
			
			foreach ($files as $file)
			{
				$tempFileModifiedTimestamp = XXX_FileSystem_Local::getFileModifiedTimestamp($file);
				
				$fileModifiedTimestamp = XXX_Number::highest($fileModifiedTimestamp, $tempFileModifiedTimestamp);
			}
			
			if ($fileModifiedTimestamp == 0)
			{
				$fileModifiedTimestamp = XXX_TimestampHelpers::getCurrentTimestamp();
			}
			
			$fileContent = '';
			$defaultMIMEType = 'application/octet-stream';
			$mimeType = $defaultMIMEType;
			
			foreach ($files as $file)
			{
				$tempFileMIMEType = self::determineAppropriateMIMEType($file);
				if ($tempFileMIMEType != $defaultMIMEType && $tempFileMIMEType != $mimeType)
				{
					$mimeType = $tempFileMIMEType;
				}
				$tempFileContent = XXX_FileSystem_Local::getFileContent($file);
				
				if ($tempFileContent)
				{
					$fileContent .= $tempFileContent;
					$fileContent .= "\r\n";
				}
			}
			
			if ($fileContent == '')
			{
				self::sendNotFoundHeader();
			}
			else
			{
				$byteSize = XXX_String::getByteSize($fileContent);
				
				$temp = self::processServeHeaders($byteSize, $mimeType, $fileModifiedTimestamp);
			
				if ($temp)
				{
					echo $fileContent;
				}
			}
		}
		
		public static function downloadStringAsFile ($string = '', $fileIdentifier = '', $mimeType = 'text/plain')
		{
			$result = false;
			
			$chunkSize = 8192;
			
			//$string = XXX_Type::makeBinaryString($string);		
			$byteSize = XXX_String::getByteSize($string);
			
			$temp = self::processDownloadHeaders($fileIdentifier, $byteSize, $mimeType);
			
			if ($temp)
			{	
				$bytesSend = 0;
				
				if ($temp['seekStart'] > 0)
				{
					$string = substr($string, $temp['seekStart']);
					
					$byteSize = ($temp['seekEnd'] - $temp['seekStart']) + 1;
				}
				
				// TODO seekEnd... (part not ending at the end)
				while (!XXX_HTTPServer_Client::isDisconnected() && strlen($string) && $bytesSend < $byteSize)
				{
					$result = true;
					
					$buffer = substr($string, 0, $chunkSize);
					
					if ($buffer)
					{
						echo $buffer;
						flush();
					}
					else
					{
						break;
					}
					
					$string = substr($string, strlen($buffer));
					$bytesSend += strlen($buffer);
				}
			}
			
			return $result;
		}
	
	// TODO after successful download, be sure no blank lines are omitted which might cause problems.
	
	public static function determineAppropriateMIMEType ($file)
	{
		$extension = XXX_String::convertToLowerCase(XXX_FileSystem_Local::getFileExtension($file));
		
		switch ($extension)
		{
			case 'css':
				$mimeType = 'text/css';
				break;
			case 'js':
				$mimeType = 'text/javascript';
				break;
			case 'json':
				$mimeType = 'text/json';
				break;
			case 'htm':
			case 'html':
				$mimeType = 'text/html';
				break;
			case 'txt':
				$mimeType = 'text/plain';
				break;
			case 'ico':
				$mimeType = 'image/x-icon';
				break;
			case 'jpg':
			case 'jpeg':
				$mimeType = 'image/jpg';
				break;
			case 'png':
				$mimeType = 'image/png';
				break;
			case 'gif':
				$mimeType = 'image/gif';
				break;
			default:
				$mimeType = XXX_FileSystem_Local::getFileMIMEType($file);
				break;
		}
		
		return $mimeType;
	}
	
	
	public static function sendNotCacheableHeaders ()
	{
		self::addHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		self::addHeader('Pragma', 'no-cache');
		self::addHeader('Expires', 'Thu, 19 Nov 1981 08:52:00 GMT');
	}
	
	public static function areHeadersSent ()
	{
		return headers_sent();
	}
	
	
	public static function addHeader ($key = '', $value = '', $overwrite = true)
	{
		$key = strtolower($key);
		/*
		for ($i = 0, $iEnd = count(self::$headers); $i < $iEnd; ++$i)
		{
			if (self::$headers[$i]['key'] == $key)
			{
				self::$headers[$i] = false;
			}
		}
		*/
		self::$headers[] = array
		(
			'key' => $key,
			'value' => $value
		);
		
		//XXX_Type::peakAtVariable(self::$headers);
		
		self::sendHeader($key . ': ' . $value);
	}
	
	public static function flushHeaders ()
	{
		foreach (self::$headers as $header)
		{
			if ($header !== false)
			{
				$headerString = $header['key'];
				
				if ($header['value'] != '')
				{
					$headerString .= ': ' . $header['value'];
				}
				
				self::sendHeader($headerString);
			}
		}
	}
	
	public static function setMIMETypeAndCharacterSet ($mimeType = 'text/html', $characterSet = false)
	{
		self::$mimeType = $mimeType;
		
		$suffix = '';
		
		switch ($mimeType)
		{
			case 'text/html':
			case 'text/xml':
			case 'text/javascript':
			case 'text/json':
			case 'text/css':
			case 'text/plain':
				if ($characterSet === '')
				{
					$characterSet = 'utf-8';
				}
				break;
		}
		
		if ($characterSet != '')
		{
			$suffix .= '; charset=' . $characterSet;
		}
		
		self::addHeader('Content-type', $mimeType . $suffix);
	}
	
	public static function sendNotFoundHeader ()
	{
		return self::sendHeader(XXX_HTTPServer_Client::$requestProtocolAndVersionPrefix . ' 404 Not Found');
	}
	
	public static function sendNotModifiedHeader ()
	{
		return self::sendHeader(XXX_HTTPServer_Client::$requestProtocolAndVersionPrefix . ' 304 Not Modified');
	}
	
	public static function sendCrossSubDomainAccessHeader ()
	{
		return self::addHeader('x-frame-options', 'SAMEORIGIN');
	}
	
	public static function sendHeader ($header = '')
	{
		return header($header);
	}
	
	public static function flush ()
	{
		return flush();
	}
	
	public static function bufferedOutputCallback ($output)
	{
		// TODO cpu load check via memcached or something if load is low
		if (XXX_HTTPServer_Client_Output::$compressOutput)
		{
			$output = XXX_HTTPServer_Client_Output::compressOutput($output);
		}
		
		//XXX_Log::logLine($output, 'bufferedOutput');
		
		XXX_Log::logLine('C' . (XXX_HTTPServer_Client_Output::areHeadersSent() ? 'Headers already sent' : 'Headers not sent yet'), 'headers');
		XXX_Log::saveBuffers();
				
		return $output;
	}
	
	public static function commentBasedOnFileType ($comment = '')
	{
		if (XXX_Type::isArray($comment))
		{
			$comment = XXX_Array::joinValuesToString($comment, XXX_String::$lineSeparator);
		}
		
		$newComment = '';
		
		switch (self::$mimeType)
		{
			case 'text/javascript':
			case 'application/javascript':
				$newComment .= XXX_String::$lineSeparator;
				$newComment .= '/*' . XXX_String::$lineSeparator;
				$newComment .= $comment . XXX_String::$lineSeparator;
				$newComment .= '*/' . XXX_String::$lineSeparator;
				break;
			case 'text/json':
			case 'application/json':
				break;
			case 'text/css':
				$newComment .= XXX_String::$lineSeparator;
				$newComment .= '/*' . XXX_String::$lineSeparator;
				$newComment .= $comment . XXX_String::$lineSeparator;
				$newComment .= '*/' . XXX_String::$lineSeparator;
				break;
			case 'text/html':
			case 'text/xml':
				$newComment .= XXX_String::$lineSeparator;
				$newComment .= '<!-- ' . XXX_String::$lineSeparator;
				$newComment .= $comment . XXX_String::$lineSeparator;
				$newComment .= ' -->' . XXX_String::$lineSeparator;
				break;
			case 'text/plain':
				$newComment .= XXX_String::$lineSeparator;
				$newComment .= XXX_String::$lineSeparator;
				$newComment .= $comment . XXX_String::$lineSeparator;
				break;
		}
		
		return $newComment;
	}
	
	public static function compressOutput ($output)
	{		
		$result = '';
		
		$compressed = false;
		
		$comment = array();
		
		XXX_Log::logLine('B' . (XXX_HTTPServer_Client_Output::areHeadersSent() ? 'Headers already sent' : 'Headers not sent yet'), 'headers');
		XXX_Log::saveBuffers();
		
		
		if (function_exists('gzencode'))
		{	
			$compressionLevel = -1; // Range 0 - 9, default = 5
					
			if (XXX_HTTPServer_Client::$outputEncoding['gzip'])
			{
				$result = gzencode($output, $compressionLevel, FORCE_GZIP);
				
				if ($result)
				{
					if (XXX_PHP::$debug)
					{
						$originalLength = XXX_String::getByteSize($output);
						$compressedLength = XXX_String::getByteSize($result);
						$compressionPercentage = XXX_Number::round(($compressedLength / $originalLength) * 100);
						$compressionRatio = XXX_Number::round(($originalLength / $compressedLength), 2);
						
						$comment[] = 'Compressed with: gzip';
						$comment[] = 'Original length: ' .$originalLength;
						$comment[] = 'Compressed length: ' . $compressedLength . ' (' . $compressionPercentage . '%)';
						$comment[] = 'Compression ratio: 1 / ' . $compressionRatio;
						$comment[] = 'Compression level: ' . $compressionLevel;
						
						$output .= self::commentBasedOnFileType($comment);
						
						$result = gzencode($output, $compressionLevel, FORCE_GZIP);
					}
					
					self::addHeader('Content-Encoding', 'gzip');
					self::addHeader('Vary', 'Accept-Encoding');
					$compressed = true;
				}
			}
			else if (XXX_HTTPServer_Client::$outputEncoding['deflate'])
			{
				$result = gzencode($output, $compressionLevel, FORCE_DEFLATE);
				
				if ($result)
				{
					if (XXX_PHP::$debug)
					{
						$originalLength = XXX_String::getByteSize($output);
						$compressedLength = XXX_String::getByteSize($result);
						$compressionPercentage = XXX_Number::round(($compressedLength / $originalLength) * 100);
						$compressionRatio = XXX_Number::round(($originalLength / $compressedLength), 2);
						
						$comment[] = 'Compressed with: deflate';
						$comment[] = 'Original length:  ' .$originalLength;
						$comment[] = 'Compressed length: ' . $compressedLength . ' (' . $compressionPercentage . '%)';
						$comment[] = 'Compression ratio: 1 / ' . $compressionRatio;
						
						$output .= self::commentBasedOnFileType($comment);
						
						$result = gzencode($output, $compressionLevel, FORCE_DEFLATE);
					}
					
					self::addHeader('Content-Encoding', 'deflate');
					self::addHeader('Vary', 'Accept-Encoding');
					$compressed = true;
				}
			}
		}
		
		if (!$compressed)
		{
			$result = $output;
			
			if (XXX_PHP::$debug)
			{
				$comment[] = 'No compression applied... (Browser didn\'t state (gzip) or (deflate) compression support in the request)';
				
				$result .= self::commentBasedOnFileType($comment);
			}
		}
		
		return $result;
	}
}

?>