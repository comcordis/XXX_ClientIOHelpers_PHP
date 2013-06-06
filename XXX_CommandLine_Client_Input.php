<?php

class XXX_CommandLine_Client_Input
{
	public static $parsedArgumentVariables = array();
	
	public static function initialize ()
	{
		self::$parsedArgumentVariables = self::parseArgumentVariables();
	}
	
	public static function getArgumentVariable ($inputName, $filter = 'string', $parameters = array())
	{
		$value = '';
		
		if (array_key_exists($inputName, self::$parsedArgumentVariables))
		{
			$value = self::$parsedArgumentVariables[$inputName];
		}
		
		$result = XXX_Client_Input::sanitizeVariable($inputName, $value, $filter, $parameters, true);
		
		return $result;	
	}
	
	public static function setArgumentVariable ($inputName, $value = '')
	{
		self::$parsedArgumentVariables[$inputName] = $value;
	}
	
	public static function getRawArgumentVariables ()
	{
		return self::$parsedArgumentVariables;
	}
	
	/*
		
	 Parses $argv for parameters and assigns them to an array.
	 
	  Supports:
	  -e
	  -e <value>
	  --long-param
	  --long-param=<value>
	  --long-param <value>
	  <value>
	 
	 array $noValueArguments List of parameters without values
	
	*/
	
	public static function parseArgumentVariables ($noValueArguments = array('f'))
	{
		global $argc, $argv;
		
		$result = array();
		$tempArguments = array();
		
		if ($argc > 0)
		{
			$tempArguments = $argv;
		}
		
		for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($tempArguments); $i < $iEnd; ++$i)
		{
			$name = $tempArguments[$i];
						
			if (!($i == 1 && ($name == '--' || $name == '-')) && $i > 0)
			{
				$value = true;
				
				$longDescriptor = XXX_String::beginsWith($name, '--');
				$shortDescriptor = XXX_String::beginsWith($name, '-');
				
				// Short or Long Descriptor
				if ($longDescriptor || $shortDescriptor)
				{
					if ($longDescriptor)
					{					
						$name = XXX_String::getPart($name, 2);
					}
					else if ($shortDescriptor)
					{
						$name = XXX_String::getPart($name, 1);
					}
					
					if (XXX_String::findFirstPosition($name, '=') !== false)
					{
						$items = XXX_String::splitToArray($name, '=');
						
						$splitOffFirstItem = XXX_Array::splitOffFirstItem($items);
						
						$name = $splitOffFirstItem['firstItem'];
						
						$parts = $splitOffFirstItem['array'];
						
						$value = XXX_Array::joinValuesToString($parts, '=');
					}
				
					// If the next argument is the value for this one
					if (!XXX_Array::hasValue($noValueArguments, $name) && $value === true && $i < $iEnd - 1)
					{
						$nextArgument = $tempArguments[$i + 1];
						
						$longDescriptor = XXX_String::beginsWith($nextArgument, '--');
						$shortDescriptor = XXX_String::beginsWith($nextArgument, '-');
						
						// Short or Long Descriptor
						if (!($longDescriptor || $shortDescriptor))
						{
							$value = $nextArgument;
							
							++$i;
						}
					}
					
					$result[$name] = $value;
				}
				// Value
				else
				{
					$value = $name;
					
					$result[] = $value;
				}
			}
		}
		
		return $result;
	}
	
	public static function readInput ($parser = false)
	{
		$result = false;
		
		$data = '';
		
		while(!feof(STDIN))
		{
			$chunk = fread(STDIN, 4096);
			
			if ($chunk === false)
			{
				if(feof(STDIN))	
				{
					break;
				}
				
				continue;
			}
			
			$data .= $chunk;
		}
		
		$result = $data;
		
		if ($parser && $result != '')
		{
			switch ($parser)
			{
				case 'phpon':
					$result = XXX_String_PHPON::decode($result);
					break;
				case 'csv':
					$result = XXX_String_CSV::parse($result);
					break;
			}
		}
		
		return $result;
	}
}

?>