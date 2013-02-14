<?php

abstract class XXX_CommandLine_Client_Output
{
	public static function bufferedOutputCallback ($output)
	{
		// Maybe log to file or something
		
		return $output;
	}
		
	public static function outputLine ($line = '', $replace = false)
	{
		//echo XXX_Log::getTimestamp() . ' ' . $line . ($replace ? "\r" : "\r\n");
		echo $line . ($replace ? "\r" : "\r\n");
	}
	
	public static function outputRuler ()
	{
		echo '--------------------------------------------------------------------------' . XXX_String::$lineSeparator;
	}
}

?>