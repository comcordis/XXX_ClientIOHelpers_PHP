<?php

abstract class XXX_OperatingSystem_Client_Input
{
	public static function getEnvironmentVariable ($inputName, $filter = 'string', $parameters = array())
	{
		$value = '';
		
		if (getenv($inputName) !== false)
		{
			$value = getenv($inputName);
		}
		
		$result = XXX_Client_Input::sanitizeVariable($inputName, $value, $filter, $parameters, true);
		
		return $result;	
	}
}

?>