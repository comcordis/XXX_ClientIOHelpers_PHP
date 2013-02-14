<?php

// When using the output buffer, make sure you don't already have some compressions mechanism trough apache in action!
// Also check if the buffer is large enough! So it doesn't automatically preflush....
// Will make setting cookies etc. available troughout script.

abstract class XXX_Client_Output
{
	public static function startRootBuffer ()
	{
		switch (XXX_PHP::$executionEnvironment)
		{
			case 'httpServer':
				$bufferedOutputCallback = 'XXX_HTTPServer_Client_Output::bufferedOutputCallback';
				break;
			case 'commandLine':
				$bufferedOutputCallback = 'XXX_CommandLine_Client_Output::bufferedOutputCallback';
				break;
		}
		
		return self::startBuffer($bufferedOutputCallback);
	}
	
	public static function startBuffer ($bufferedOutputCallback = false)
	{
		if ($bufferedOutputCallback)
		{
			$result = ob_start($bufferedOutputCallback);
		}
		else
		{
			$result = ob_start();
		}
		
		return $result;
	}
	
	public static function flushBuffer ()
	{
		return ob_flush();
	}
	
	public static function cleanBuffer ()
	{
		return ob_clean();
	}
	
	public static function flushRootBuffer ()
	{
		return self::flushAllBuffers();
	}
	
	public static function cleanRootBuffer ()
	{
		return self::cleanAllBuffers();
	}
	
	public static function flushAllBuffers ()
	{
		while (ob_flush())
		{
		}
	}
	
	public static function cleanAllBuffers ()
	{
		while (ob_clean())
		{
		}
	}
	
	public static function getBufferContent ($clean = true)
	{
		$result = ob_get_contents();
		
		if ($clean)
		{
			self::cleanBuffer();
		}
		
		return $result;
	}
	
	public static function enable ($compressed = false)
	{
		self::startBuffer($compressed);
	}
	
	public static function disable ()
	{
		self::cleanAllBuffers();
	}
}

?>