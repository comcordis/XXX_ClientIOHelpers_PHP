<?php

// TODO http://teddy.fr/blog/how-serve-big-files-through-php

abstract class XXX_HTTPServer_Client_Output
{
	public static $compressOutput = false;
	
	public static $headers = array();

	public static function processDownloadHeaders ($file = '', $byteSize = 0, $mimeType = 'application/octet-stream', $fileModifiedTimestamp = 0)
	{
		$result = false;
		
		// TODO If you want to do something on download abort/finish, use register_shutdown_function('function_name');
		
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
        	
        	// encode dots in filenames with an extra dot in it... e.g. some.file.ext 
        	$file = preg_replace('/\./', '%2e', $file, substr_count($file, '.') - 1);
        }
		
		// Turn off output buffering to decrease cpu usage
		ob_end_clean();
				
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
			
			self::sendHeader('Content-Type: ' . $mimeType);		
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
					$file = XXX_Path_Local::getIdentifierFromPath('AbsoluteLocal', $absoluteFile);
				}
				
				$mimeType = XXX_FileSystem_Local::getFileMIMEType($absoluteFile);
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
	
	public static function setOutputContentTypeAndCharacterSet ($contentType = 'text/html', $characterSet = 'utf-8')
	{
		self::sendHeader('Content-type: ' . $contentType . '; charset=' . $characterSet);
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
		if (self::$compressOutput)
		{
			$output = self::compressOutput($output);
		}
		
		return $output;
	}
	
	public static function compressOutput ($output)
	{		
		$result = '';
		
		$compressed = false;
		
		if (XXX_HTTPServer_Client::$outputEncoding['gzip'])
		{
			$compressionLevel = 5; // Range 0 - 9, default = 5
			
			$result = gzencode($output, $compressionLevel, FORCE_GZIP);
			
			if ($result)
			{
				if (XXX_PHP::$debug)
				{
					$originalLength = XXX_String::getByteSize($output);
					$compressedLength = XXX_String::getByteSize($result);
					$compressionPercentage = XXX_Number::round(($compressedLength / $originalLength) * 100);
					$compressionRatio = XXX_Number::round(($originalLength / $compressedLength), 2);
					
					$output .= XXX_String::$lineSeparator;
					$output .= '<!--' . XXX_String::$lineSeparator;
					$output .= 'Compressed with: gzip' . XXX_String::$lineSeparator;
					$output .= 'Original length: ' .$originalLength . XXX_String::$lineSeparator;
					$output .= 'Compressed length: ' . $compressedLength . ' (' . $compressionPercentage . '%)' . XXX_String::$lineSeparator;
					$output .= 'Compression ratio: 1 / ' . $compressionRatio . XXX_String::$lineSeparator;
					$output .= 'Compression level: ' . $compressionLevel . XXX_String::$lineSeparator;
					$output .= '-->' . XXX_String::$lineSeparator;
					
					$result = gzencode($output, $compressionLevel, FORCE_GZIP);
				}
				
				XXX_HTTPServer_Client_Output::sendHeader('Content-Encoding: gzip');				
				$compressed = true;
			}
		}
		else if (XXX_HTTPServer_Client::$outputEncoding['deflate'])
		{
			$result = gzencode($output, FORCE_DEFLATE);
			
			if ($result)
			{
				if (XXX_PHP::$debug)
				{
					$originalLength = XXX_String::getByteSize($output);
					$compressedLength = XXX_String::getByteSize($result);
					$compressionPercentage = XXX_Number::round(($compressedLength / $originalLength) * 100);
					$compressionRatio = XXX_Number::round(($originalLength / $compressedLength), 2);
					
					$output .= XXX_String::$lineSeparator;
					$output .= '<!--' . XXX_String::$lineSeparator;
					$output .= 'Compressed with: deflate' . XXX_String::$lineSeparator;
					$output .= 'Original length:  ' .$originalLength . XXX_String::$lineSeparator;
					$output .= 'Compressed length: ' . $compressedLength . ' (' . $compressionPercentage . '%)' . XXX_String::$lineSeparator;
					$output .= 'Compression ratio: 1 / ' . $compressionRatio . XXX_String::$lineSeparator;
					$output .= '-->' . XXX_String::$lineSeparator;
					
					$result = gzencode($output, FORCE_DEFLATE);
				}
				
				XXX_HTTPServer_Client_Output::sendHeader('Content-Encoding: deflate');
				$compressed = true;
			}
		}
		
		if (!$compressed)
		{
			$result = $output;
			
			if (XXX_PHP::$debug)
			{
				$result .= XXX_String::$lineSeparator;
				$result .= '<!--' . XXX_String::$lineSeparator;
				$result .= 'No compression applied... (Browser didn\'t state (gzip) or (deflate) compression support in the request)';
				$result .= '-->' . XXX_String::$lineSeparator;
			}
		}
		
		return $result;
	}
}

?>