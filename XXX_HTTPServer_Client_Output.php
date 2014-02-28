<?php

// TODO http://teddy.fr/blog/how-serve-big-files-through-php

abstract class XXX_HTTPServer_Client_Output
{
	public static $compressOutput = false;
	
	public static $mimeType = 'text/html';
	
	public static $headers = array();
	
	public static function prepareForFileServingOrDownload ($leaveOutputBuffer = false)
	{
		// Avoid any output
		error_reporting(0);
				
		ini_set('magic_quotes_runtime', 0);
		
		set_time_limit(0);
			
		if (function_exists('apache_setenv'))
		{
			apache_setenv('no-gzip', 1);
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
		
	public static function processDownloadHeaders ($file = '', $byteSize = 0, $mimeType = 'application/octet-stream', $fileModifiedTimestamp = false)
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
			self::sendHeader('Content-Type: application/force-download');
			self::sendHeader('Content-Description: File Transfer');
			self::sendHeader('Last-Modified: '. gmdate('D, d M Y H:i:s', $fileModifiedTimestamp) . ' GMT');
	
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
		
		        self::sendHeader('Accept-Ranges: bytes');
		        self::sendHeader('Content-Range: bytes ' . $seekStart . '-' . $seekEnd . '/' . $byteSize);
			}
			
	        if (XXX_HTTPServer_Client::$browser == 'internetExplorer')
	        {
	        	// encode dots in filenames with an extra dot in it... e.g. some.file.ext 
	        	$file = preg_replace('/\./', '%2e', $file, substr_count($file, '.') - 1);
	        }
        	
			self::setMIMETypeAndCharacterSet($mimeType);	
			self::sendHeader('Content-Disposition: attachment; filename="' . $file . '"');
			self::sendHeader('Content-Transfer-Encoding: binary');
			self::sendHeader('Content-Length: ' . $byteSize);
			self::sendHeader('Connection: close');
			
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
		
	// Direct, Multipart or Resume
	
		public static function forceAbsoluteFileDownload ($absoluteFile = '', $file = '', $chunkSize = 8192)
		{
			$result = false;
			
			if (XXX_FileSystem_Local::doesFileExist($absoluteFile))
			{
				if ($file == '')
				{
					$file = XXX_Path_Local::getIdentifier($absoluteFile);
				}
				
				$mimeType = self::determineAppropriateMIMEType($absoluteFile);
				$byteSize = XXX_FileSystem_Local::getFileSize($absoluteFile);
				$fileModifiedTimestamp = XXX_FileSystem_Local::getFileModifiedTimestamp($absoluteFile);
				
				$temp = self::processDownloadHeaders($file, $byteSize, $mimeType, $fileModifiedTimestamp);
				
				if ($temp)
				{
					$seekEnd = $temp['seekEnd'];
					
					$fileStream = XXX_FileSystem_Local::fileStream_openForReading($absoluteFile, false);
					
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
		
		public static function forceAbsoluteFileServing ($absoluteFile = '', $chunkSize = 8192)
		{
			$result = false;
			
			if (!XXX_FileSystem_Local::doesFileExist($absoluteFile))
			{
				self::sendHeader('HTTP/1.0 404 Not Found');
			}
			else
			{
				$file = XXX_Path_Local::getIdentifier($absoluteFile);
				
				$fileModifiedTimestamp = XXX_FileSystem_Local::getFileModifiedTimestamp($absoluteFile);
				
				if(XXX_HTTPServer_Client_Input::$onlyIfModifiedSinceTimestamp == $fileModifiedTimestamp)
				{
					self::sendNotModifiedHeader();
				}
				else
				{
					$mimeType = self::determineAppropriateMIMEType($absoluteFile);
					$byteSize = XXX_FileSystem_Local::getFileSize($absoluteFile);
					
					self::prepareForFileServingOrDownload(self::$compressOutput);
					
					self::sendHeader('Last-Modified: '. gmdate('D, d M Y H:i:s', $fileModifiedTimestamp) . ' GMT');
					self::setMIMETypeAndCharacterSet($mimeType);
					self::sendHeader('Content-Length: ' . $byteSize);
					
					if (class_exists('XXX_HTTP_Cookie_Session'))
					{
						XXX::dispatchEventToListeners('beforeSaveSession');
						XXX_HTTP_Cookie_Session::save();
					}
					
					self::sendHeader('Connection: close');					
						   				
	   				$fileStream = XXX_FileSystem_Local::fileStream_openForReading($absoluteFile, false);
				
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
			}
			
			return $result;
		}
		
		public static function mimicStaticFileServing ($subPath)
		{
			$basePath = XXX_Static_Publisher::$destinationPathPrefix;
			
			return self::serveFileFromBasePath($basePath, $subPath);
		}
		
		public static function serveFileFromBasePath ($basePath, $subPath)
		{
			$tempFile = XXX_Path_Local::extendPath($basePath, $subPath);
		
			return self::forceAbsoluteFileServing($tempFile);
		}
		
		public static function forceStringAsFileDownload ($string = '', $file = '', $mimeType = 'text/plain')
		{
			$result = false;
			
			$chunkSize = 8192;
			
			//$string = XXX_Type::makeBinaryString($string);		
			$byteSize = XXX_String::getByteSize($string);
			
			$temp = self::processDownloadHeaders($file, $byteSize, $mimeType);
						
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
			
			return $result;
		}
	
	// TODO after successful download, be sure no blank lines are omitted which might cause problems.
	
	public static function determineAppropriateMIMEType ($absoluteFile)
	{
		$extension = XXX_String::convertToLowerCase(XXX_FileSystem_Local::getFileExtension($absoluteFile));
					
		switch ($extension)
		{
			case 'css':
				$mimeType = 'text/css';
				break;
			case 'js':
				$mimeType = 'text/javascript';
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
				$mimeType = XXX_FileSystem_Local::getFileMIMEType($absoluteFile);
				break;
		}
		
		return $mimeType;
	}
	
	
	public static function sendNotCacheableHeaders ()
	{
		self::sendHeader('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		self::sendHeader('Pragma: no-cache');
		self::sendHeader('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
	}
	
	public static function areHeadersSent ()
	{
		return headers_sent();
	}
	
	
	public static function addHeader ($header)
	{
		self::$headers[] = $header;
	}
	
	public static function flushHeaders ()
	{
		foreach (self::$headers as $header)
		{
			self::sendHeader($header);
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
			case 'text/css':
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
		
		self::sendHeader('Content-type: ' . $contentType . $suffix);
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
		return self::sendHeader('x-frame-options: SAMEORIGIN');
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
				$newComment .= XXX_String::$lineSeparator;
				$newComment .= '/*' . XXX_String::$lineSeparator;
				$newComment .= $comment . XXX_String::$lineSeparator;
				$newComment .= '*/' . XXX_String::$lineSeparator;
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
					
					XXX_HTTPServer_Client_Output::sendHeader('Content-Encoding: gzip');				
					XXX_HTTPServer_Client_Output::sendHeader('Vary: Accept-Encoding');				
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
					
					XXX_HTTPServer_Client_Output::sendHeader('Content-Encoding: deflate');			
					XXX_HTTPServer_Client_Output::sendHeader('Vary: Accept-Encoding');
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