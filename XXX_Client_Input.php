<?php

abstract class XXX_Client_Input
{
	const CLASS_NAME = 'XXX_Client_Input';
	
	public static $HTML_Filter = false;
	
	public static $maliciousVariables = array();
	
	public static function initialize ()
	{
		// Strict, allow nothing!
		self::$HTML_Filter = new XXX_HTML_Filter();
			self::$HTML_Filter->allowNothing();
	}
	
	// variable
		
		public static function hasMaliciousVariables ()
		{
			return XXX_Array::getFirstLevelItemTotal(self::$maliciousVariables) > 0;
		}
		
		public static function isVariableMalicious ($variableName = '')
		{
			return XXX_Array::hasValue(self::$maliciousVariables, $variableName);
		}
		
		// Use it only to sanitize client input variables....
		public static function sanitizeVariable ($variableName = '', $value = '', $sanitation = 'string', $parameters = array(), $simplifyResult = false)
		{
			if (XXX_Type::isArray($value))
			{
				$result = array
				(
					'isSanit' => false,
					'name' => $variableName,
					'sanitation' => $sanitation,
					'originalValue' => $value,
					'sanitizedValue' => array(),
					'defaultValue' => $parameters['defaultValue']
				);
				
				for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($value); $i < $iEnd; ++$i)
				{
					if ($i == 0)
					{
						$result['isSanit'] = true;
					}
					
					$temp = self::sanitizeVariable($variableName, $value[$i], $sanitation, $defaultValue, $parameters, false);
					
					if (!$temp['isSanit'])
					{
						$result['isSanit'] = false;
					}
					
					$result['sanitizedValue'][$i] = $temp['sanitizedValue'];
					
					if ($i == 0)
					{					
						$result['defaultValue'] = $temp['defaultValue'];
					}
				}
			}
			else
			{
				$result = array
				(
					'isSanit' => false,
					'name' => $variableName,
					'sanitation' => $sanitation,
					'originalValue' => $value,
					'sanitizedValue' => '',
					'defaultValue' => $parameters['defaultValue']
				);
				
				switch ($sanitation)
				{
					case 'raw':
						if (XXX_Type::isNull($result['defaultValue']))
						{
							$result['defaultValue'] = 0;
						}
						$result['sanitizedValue'] = $result['defaultValue'];
						
						$result['sanitizedValue'] = $value;
						
						$result['isSanit'] = true;
						break;
					case 'base64String':
						$value = XXX_String_Base64::decode($value);
						
						$result = self::sanitizeVariable($variableName, $value, 'string', $defaultValue, $parameters, false);												
						break;
					case 'base64Integer':
						$value = XXX_String_Base64::decode($value);
						
						$result = self::sanitizeVariable($variableName, $value, 'integer', $defaultValue, $parameters, false);												
						break;
					case 'base64PositiveInteger':
						$value = XXX_String_Base64::decode($value);
						
						$result = self::sanitizeVariable($variableName, $value, 'positiveInteger', $defaultValue, $parameters, false);												
						break;
					case 'integer':
						if (XXX_Type::isNull($result['defaultValue']))
						{
							$result['defaultValue'] = 0;
						}
						$result['sanitizedValue'] = $result['defaultValue'];
						
						$sanitizedValue = XXX_Type::makeInteger($value);
						
						if ($sanitizedValue == $value)
						{
							$result['sanitizedValue'] = $sanitizedValue;
						}
						
						$result['isSanit'] = $result['sanitizedValue'] === $result['originalValue'];
						
						if (!$result['isSanit'])
						{
							trigger_error('Variable: "' . $variableName . '" is NOT an integer', E_USER_WARNING);
							
							XXX::dispatchEventToListeners('maliciousClientInputVariable', array('sanitation' => $sanitation, 'reason' => 'NOT an integer', 'variableName' => $variableName, 'value' => $value));
						}
						break;
					case 'positiveInteger':
						if (XXX_Type::isNull($result['defaultValue']))
						{
							$result['defaultValue'] = 0;
						}
						$result['sanitizedValue'] = $result['defaultValue'];
						
						$sanitizedValue = XXX_Type::makeInteger($value);
						$sanitizedValue = XXX_Default::toPositiveInteger($sanitizedValue, 0);
						
						if ($sanitizedValue == $value)
						{
							$result['sanitizedValue'] = $sanitizedValue;
						}
						
						$result['isSanit'] = $result['sanitizedValue'] === $result['originalValue'];
						
						if (!$result['isSanit'])
						{
							trigger_error('Variable: "' . $variableName . '" is NOT a positive integer', E_USER_WARNING);
							
							XXX::dispatchEventToListeners('maliciousClientInputVariable', array('sanitation' => $sanitation, 'reason' => 'NOT a positive integer', 'variableName' => $variableName, 'value' => $value));
						}
						break;
					case 'float':
						if (XXX_Type::isNull($result['defaultValue']))
						{
							$result['defaultValue'] = 0;
						}
						$result['sanitizedValue'] = $result['defaultValue'];
						
						$sanitizedValue = XXX_Type::makeFloat($value);
						
						if ($sanitizedValue == $value)
						{
							$result['sanitizedValue'] = $sanitizedValue;
						}
						
						$result['isSanit'] = $result['sanitizedValue'] === $result['originalValue'];
						
						if (!$result['isSanit'])
						{
							trigger_error('Variable: "' . $variableName . '" is NOT a float', E_USER_WARNING);
							
							XXX::dispatchEventToListeners('maliciousClientInputVariable', array('sanitation' => $sanitation, 'reason' => 'NOT a float', 'variableName' => $variableName, 'value' => $value));
						}
						break;
					case 'hash':
						if (XXX_Type::isNull($result['defaultValue']))
						{
							$result['defaultValue'] = '';
						}
						$result['sanitizedValue'] = $result['defaultValue'];
						
						$valueCharacterLength = XXX_String::getCharacterLength($value);
						
						$validCharacterLength = XXX_Array::hasValue(array(8, 16, 32, 40, 64, 128), $valueCharacterLength);
						$validHashCharacters = XXX_String_Pattern::hasMatch($value, '^[a-zA-Z0-9]*$', '');
						
						if ($validHashCharacters && $validCharacterLength)
						{
							$result['sanitizedValue'] = $value;
							$result['isSanit'] = true;				
						}
						
						if (!$result['isSanit'])
						{
							trigger_error('Variable: "' . $variableName . '" is NOT a hash', E_USER_WARNING);
							
							XXX::dispatchEventToListeners('maliciousClientInputVariable', array('sanitation' => $sanitation, 'reason' => 'NOT a hash', 'variableName' => $variableName, 'value' => $value));
						}
						break;
					case 'rawJSON':
						if (XXX_Type::isNull($result['defaultValue']))
						{
							$result['defaultValue'] = false;
						}
						$result['sanitizedValue'] = $result['defaultValue'];
						
						$isEmptyOrFalse = $value == '' || XXX_Type::makeBoolean($value) == false;
						
						$jsonDecoded = XXX_String_JSON::decode($value);
						
						if ($jsonDecoded)
						{
							$result['sanitizedValue'] = $jsonDecoded;
							$result['isSanit'] = true;	
						}
						else
						{
							if ($isEmptyOrFalse)
							{
								$result['sanitizedValue'] = false;
								$result['isSanit'] = true;
							}
						}
						
						if (!$result['isSanit'])
						{
							trigger_error('Variable: "' . $variableName . '" is NOT json encoded', E_USER_WARNING);
							
							XXX::dispatchEventToListeners('maliciousClientInputVariable', array('sanitation' => $sanitation, 'reason' => 'NOT json encoded', 'variableName' => $variableName, 'value' => $value));
						}
						break;
					case 'string':
						if (XXX_Type::isNull($result['defaultValue']))
						{
							$result['defaultValue'] = '';
						}
						$result['sanitizedValue'] = $result['defaultValue'];
						
						$sanitizedValue = $value;
						
						if ($parameters['filterUTF8'])
						{
							// Format UTF8 / Character encoding
							if (!XXX_String_Unicode_Filter::isValid($sanitizedValue))
							{
								$sanitizedValue = XXX_String_Unicode_Filter::filter($sanitizedValue);
								
								trigger_error('Variable: "' . $variableName . '" string is invalid UTF-8', E_USER_WARNING);
								
								XXX::dispatchEventToListeners('maliciousClientInputVariable', array('sanitation' => $sanitation, 'reason' => 'Invalid UTF-8', 'variableName' => $variableName, 'value' => $value));
							}
						}
						
						if ($parameters['filterControlCharacters'])
						{						
							// Filted out unwanted control characters
							if (!XXX_String_ControlCharacters_Filter::isValid($sanitizedValue))
							{
								$sanitizedValue = XXX_String_ControlCharacters_Filter::filter($sanitizedValue);
								
								trigger_error('Variable: "' . $variableName . '" string had control characters in it.', E_USER_WARNING);
								
								XXX::dispatchEventToListeners('maliciousClientInputVariable', array('sanitation' => $sanitation, 'reason' => 'Has unwanted control characters', 'variableName' => $variableName, 'value' => $value));
							}
						}
					
						// Filter out unwanted HTML / JavaScript (XSS)
						if ($parameters['filterHTML'])
						{
							$tempValue = self::$HTML_Filter->filter($sanitizedValue);
							
							if ($tempValue != $sanitizedValue)
							{
								$sanitizedValue = $tempValue;
								
								trigger_error('Variable: "' . $variableName . '" string had unwanted HTML / JavaScript in it.', E_USER_WARNING);
								
								XXX::dispatchEventToListeners('maliciousClientInputVariable', array('sanitation' => $sanitation, 'reason' => 'Has unwanted HTML / JavaScript', 'variableName' => $variableName, 'value' => $value));
							}
						}
						
						// Escape HTML / JavaScript output
						if ($parameters['encodeHTML'])
						{
							$sanitizedValue = XXX_String_HTMLEntities::encode($sanitizedValue);
						}
						
						$result['sanitizedValue'] = $sanitizedValue;
						
						$result['isSanit'] = $result['sanitizedValue'] === $result['original'];
						break;
					case 'boolean':
						if (XXX_Type::isNull($result['defaultValue']))
						{
							$result['defaultValue'] = false;
						}
						
						$result['sanitizedValue'] = $result['defaultValue'];
						
						$validTrue = $value === true || $value === 1 || $value === '1' || XXX_String::convertToLowerCase($value) === 'true';
						$validFalse = $value === false || $value === 0 || $value === '0' || XXX_String::convertToLowerCase($value) === 'false' || $value === '';
						
						if ($validTrue)
						{
							$value = true;
						}
						else if ($validFalse)
						{
							$value = false;
						}
						if ($validTrue || $validFalse)
						{
							$result['sanitizedValue'] = $value;						
						}
						
						$result['isSanit'] = $result['sanitizedValue'] === $result['originalValue'];
											
						if (!$result['isSanit'])
						{
							trigger_error('Variable: "' . $variableName . '" is NOT boolean', E_USER_WARNING);
							
							XXX::dispatchEventToListeners('maliciousClientInputVariable', array('sanitation' => $sanitation, 'reason' => 'NOT boolean', 'variableName' => $variableName, 'value' => $value));
						}
						break;
				}
			}
			
			if (!$result['isSanit'])
			{
				self::$maliciousVariables[] = $result['name'];
				
			}
			
			if ($simplifyResult)
			{
				$result = $result['sanitizedValue'];
			}
			
			return $result;
		}
	
	// file

		public static function validateFileMIMEType (array $validFileMIMETypes = array(), $fileMIMEType = '')
		{
			$result = false;
			
			$fileMIMEType = XXX_String::trim($fileMIMEType);
			
			$fileMIMETypeParts = XXX_String::splitToArray($fileMIMEType, '/');
			
			if (XXX_Array::getFirstLevelItemTotal($validFileMIMETypes) > 0)
			{
				for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($validFileMIMETypes); $i < $iEnd; ++$i)
				{
					$validFileMIMEType = $validFileMIMETypes[$i];
					
					if ($validFileMIMEType == '*/*')
					{
						$result = true;
					}
					else
					{
						$validFileMIMETypeParts = XXX_String::splitToArray($validFileMIMEType, '/');
						
						if ($fileMIMETypeParts[0] == $validFileMIMETypeParts[0])
						{
							if ($validFileMIMETypeParts[1] == '*')
							{
								$result = true;
							}
							else
							{
								if ($fileMIMETypeParts[1] == $validFileMIMETypeParts[1])
								{
									$result = true;
								}
							}
						}
					}
					
					// No need to look further
					if ($result)
					{
						break;
					}
				}
			}
			else
			{
				// No restriction in place, don't allow anything
				$result = false;
			}
			
			return $result;
		}
		
		public static function validateFileExtension (array $validFileExtensions = array(), $fileExtension = '')
		{
			$result = false;
			
			$fileExtension = XXX_String::trim($fileExtension);
			
			if (XXX_Array::getFirstLevelItemTotal($validFileExtensions) > 0)
			{
				for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($validFileExtensions); $i < $iEnd; ++$i)
				{
					$validFileExtension = $validFileExtensions[$i];
					
					if ($validFileExtension == '*')
					{
						$result = true;
					}
					else
					{
						if ($fileExtension == $validFileExtension)
						{
							$result = true;
						}
					}
					
					// No need to look further
					if ($result)
					{
						break;
					}
				}
			}
			else
			{
				// No restriction in place, don't allow anything
				$result = false;
			}
			
			return $result;
		}
		
		public static function validateMinimumFileSize ($minimumFileSize = 0, $fileSize = 0)
		{
			$result = false;
			
			if ($minimumFileSize)
			{
				if ($fileSize >= $minimumFileSize)
				{
					$result = true;
				}
			}
			else
			{
				$result = true;
			}
			
			return $result;
		}
		
		public static function validateMaximumFileSize ($maximumFileSize = 0, $fileSize = 0)
		{
			$result = false;
			
			if ($maximumFileSize)
			{
				if ($fileSize <= $maximumFileSize)
				{
					$result = true;
				}
			}
			else
			{
				$result = true;
			}
			
			return $result;
		}
		
		public static function validateMaximumFileTotal ($maximumFileTotal = 0, $fileTotal = 0)
		{
			$result = false;
			
			if ($maximumFileTotal)
			{
				if ($fileTotal <= $maximumFileTotal)
				{
					$result = true;
				}
			}
			else
			{
				$result = true;
			}
			
			return $result;
		}
		
		public static function validateMaximumFileSizeTotal ($maximumFileSizeTotal = 0, $fileSizeTotal = 0)
		{
			$result = false;
			
			if ($maximumFileSizeTotal)
			{
				if ($fileSizeTotal <= $maximumFileSizeTotal)
				{
					$result = true;
				}
			}
			else
			{
				$result = true;
			}
			
			return $result;
		}
		
		public static function validateFreeAccountStorageSpace ($freeAccountStorageSpace = 0, $fileSizeTotal = 0)
		{
			$result = false;
			
			if ($freeAccountStorageSpace >= 0)
			{
				if ($fileSizeTotal <= $freeAccountStorageSpace)
				{
					$result = true;
				}
			}
			else
			{
				$result = true;
			}
			
			return $result;
		}
			
		public static function isAllowedEventTrigger ($eventTrigger, $matchEventTrigger, $doNotMatchEventTrigger)
		{
			$result = false;
			
			if (!$eventTrigger)
			{
				$result = true;
			}
			else
			{
				$matchEventTriggerState = false;
				
				if (!$matchEventTrigger)
				{
					$matchEventTriggerState = true;
				}			
				else if (XXX_Type::isArray($matchEventTrigger))
				{
					if (XXX_Array::hasValue($matchEventTrigger, $eventTrigger))
					{
						$matchEventTriggerState = true;
					}
				}
				else if ($eventTrigger == $matchEventTrigger)
				{
					$matchEventTriggerState = true;
				}
				
				$doNotMatchEventTriggerState = false;
				
				if (!$doNotMatchEventTrigger)
				{
					$doNotMatchEventTriggerState = true;
				}			
				else if (XXX_Type::isArray($doNotMatchEventTrigger))
				{
					if (!XXX_Array::hasValue($doNotMatchEventTrigger, $eventTrigger))
					{
						$doNotMatchEventTriggerState = true;
					}
				}
				else if ($eventTrigger != $doNotMatchEventTrigger)
				{
					$doNotMatchEventTriggerState = true;
				}
				
				if ($matchEventTriggerState && $doNotMatchEventTriggerState)
				{
					$result = true;
				}
			}
			
			return $result;
		}
	
	// default actions
		
		public static function applyDefaultRecordRepresentationActions (&$callbackObject, $key = '', $type = '')
		{
			switch ($type)
			{
				case 'ID':
					$callbackObject->addPropertyAction($key, 'validation', 'minimumInteger', '', 0);
					$callbackObject->addPropertyAction($key, 'validation', 'maximumInteger', '', 9223372036854775807);
					break;
				case 'timestamp':
					$callbackObject->addPropertyAction($key, 'validation', 'minimumInteger', '', -9223372036854775808);
					$callbackObject->addPropertyAction($key, 'validation', 'maximumInteger', '', 9223372036854775807);
					break;		
				case 'ammount':
					$callbackObject->addPropertyAction($key, 'validation', 'minimumInteger', '', 0);
					$callbackObject->addPropertyAction($key, 'validation', 'maximumInteger', '', 4294967295);
					break;
				case 'pseudonym':
					$callbackObject->addPropertyAction($key, 'validation', 'minimumCharacterLength', '', 3);
					$callbackObject->addPropertyAction($key, 'validation', 'maximumCharacterLength', '', 64);
					
					$callbackObject->addPropertyAction($key, 'validation', 'doNotMatchPattern', 'Can only contain alphabetic characters (a-zA-Z), digits (0-9), and underscore (_).', array('pattern' => '[^a-zA-Z0-9_]', 'patternModifiers' => ''));
					break;						
				case 'tag':
					$callbackObject->addPropertyAction($key, 'validation', 'minimumCharacterLength', '', 3);
					$callbackObject->addPropertyAction($key, 'validation', 'maximumCharacterLength', '', 64);
					
					$callbackObject->addPropertyAction($key, 'validation', 'doNotMatchPattern', 'Can\'t contain a comma (,).', array('pattern' => '[,]', 'patternModifiers' => ''));
					break;
				case 'emailAddress':
					$callbackObject->addPropertyAction($key, 'validation', 'minimumCharacterLength', '', 6);
					$callbackObject->addPropertyAction($key, 'validation', 'maximumCharacterLength', '', 128);
					
					$callbackObject->addPropertyAction($key, 'validation', 'matchPattern', 'Is not a valid email address.', array('pattern' => '^[a-zA-Z0-9._%+-]{1,}@(?:[a-zA-Z0-9_%+-]{1,}\.){1,}[a-zA-Z0-9_%+-]{1,}$', 'patternModifiers' => 'i'));
					break;
				case 'hash':
					$callbackObject->addPropertyAction($key, 'validation', 'minimumCharacterLength', '', 32);
					$callbackObject->addPropertyAction($key, 'validation', 'maximumCharacterLength', '', 32);
					$callbackObject->addPropertyAction($key, 'validation', 'doNotMatchPattern', 'Can only contain alphabetic characters (a-zA-Z) and digits (0-9).', array('pattern' => '[^a-zA-Z0-9]', 'patternModifiers' => ''));
					break;
			}
			
		}
		
	// value
	
		public static function operateOnValue ($value = '', $action = '', $texts = '', $parameters = array(), $eventTrigger = '')
		{
			$result = array
			(
				'operated' => false,
				'value' => $value,
				'feedbackMessage' => ''
			);
			
			$variables = array();
			$feedbackMessageGrammaticalNumberFormQuantity = 0;
			$noTexts = $texts == '' || XXX_Type::isEmptyArray($texts);
			$useDefaultTexts = $noTexts && $texts !== false;
						
			switch ($action)
			{
				case 'removePattern':				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'pattern' => $parameters,
							'patternModifiers' => ''
						);
					}
				
					if ($parameters['pattern'])
					{
						$temp = XXX_String_Pattern::replaceReturnInformation($value, $parameters['pattern'], $parameters['patternModifiers'], '');
						
						if ($temp && $temp['replaced'])
						{
							$result['operated'] = true;
							$result['value'] = $temp['newValue'];
							
							if ($useDefaultTexts)
							{
								$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'operation', 'removePattern');
							}
						}
					}
					break;
				case 'replacePattern':				
					if ($parameters['pattern'] && $parameters['replacement'])
					{
						$temp = XXX_String_Pattern::replaceReturnInformation($value, $parameters['pattern'], $parameters['patternModifiers'], $parameters['replacement']);
						
						if ($temp && $temp['replaced'])
						{
							$result['operated'] = true;
							$result['value'] = $temp['newValue'];
							
							if ($useDefaultTexts)
							{
								$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'operation', 'replacePattern');
							}
						}
					}					
					break;
				case 'string':
					$valueString = XXX_Type::makeString($value);
					
					if ($value !== $valueString)
					{
						$result['operated'] = true;
						$result['value'] = $valueString;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'operation', 'string');
						}
					}
					break;
				case 'maximumCharacterLength':				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'maximumCharacterLength' => $parameters
						);
					}
					
					$valueCharacterLength = XXX_String::getCharacterLength($value);
					
					if ($parameters['maximumCharacterLength'] && $valueCharacterLength > $parameters['maximumCharacterLength'])
					{
						$difference = $valueCharacterLength - $parameters['maximumCharacterLength'];
						
						$newValue = XXX_String::getPart($value, 0, $parameters['maximumCharacterLength']);
						
						$result['operated'] = true;
						$result['value'] = $newValue;
						
						$variables['difference'] = $difference;
						
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'operation', 'maximumCharacterLength');
						}
					}
					break;
				case 'number':
					$valueNumber = XXX_Type::makeNumber($value);
					
					if ($value != $valueNumber)
					{
						$result['operated'] = true;
						$result['value'] = $valueNumber;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'operation', 'number');
						}
					}
					break;
				case 'minimumNumber':				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'minimumNumber' => $parameters
						);
					}
					
					$valueNumber = XXX_Type::makeNumber($value);
					
					if ($parameters['minimumNumber'] && $valueNumber < $parameters['minimumNumber'])
					{
						$difference = $parameters['minimumNumber'] - $valueNumber;
						
						$newValue = $parameters['minimumNumber'];
						
						$result['operated'] = true;
						$result['value'] = $newValue;
						
						$variables['difference'] = $difference;
						$variables['minimumNumber'] = $parameters['minimumNumber'];
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'operation', 'minimumNumber');
						}
					}
					break;
				case 'maximumNumber':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'maximumNumber' => $parameters
						);
					}
					
					$valueNumber = XXX_Type::makeNumber(value);
					
					if ($parameters['maximumNumber'] && $valueNumber > $parameters['maximumNumber'])
					{
						$difference = $value - $parameters['maximumNumber'];
						
						$newValue = $parameters['maximumNumber'];
						
						$result['operated'] = true;
						$result['value'] = $newValue;
						
						$variables['difference'] = $difference;
						$variables['maximumNumber'] = $parameters['maximumNumber'];
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'operation', 'maximumNumber');
						}
					}
					break;
				case 'float':
					$valueFloat = XXX_Type::makeFloat($value);
					
					if ($value != $valueFloat)
					{
						$result['operated'] = true;
						$result['value'] = $valueFloat;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'operation', 'float');
						}
					}
					break;
				case 'minimumFloat':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'minimumFloat' => $parameters
						);
					}
					
					$valueFloat = XXX_Type::makeFloat($value);
					
					if ($parameters['minimumFloat'] && $valueFloat < $parameters['minimumFloat'])
					{
						$difference = $parameters['minimumFloat'] - $valueFloat;
						
						$newValue = $parameters['minimumFloat'];
						
						$result['operated'] = true;
						$result['value'] = $newValue;
						
						$variables['difference'] = $difference;
						$variables['minimumFloat'] = $parameters['minimumFloat'];
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'operation', 'minimumFloat');
						}
					}
					break;
				case 'maximumFloat':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'maximumFloat' => $parameters
						);
					}
					
					$valueFloat = XXX_Type::makeFloat($value);
					
					if ($parameters['maximumFloat'] && $valueFloat > $parameters['maximumFloat'])
					{
						$difference = $value - $parameters['maximumFloat'];
						
						$newValue = $parameters['maximumFloat'];
						
						$result['operated'] = true;
						$result['value'] = $newValue;
						
						$variables['difference'] = $difference;
						$variables['maximumFloat'] = $parameters['maximumFloat'];
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'operation', 'maximumFloat');
						}
					}
					break;
				case 'integer':
					$valueInteger = XXX_Type::makeInteger($value);
					
					if ($value != $valueInteger)
					{
						$result['operated'] = true;
						$result['value'] = $valueInteger;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'operation', 'integer');
						}
					}
					break;
				case 'minimumInteger':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'minimumInteger' => $parameters
						);
					}
					
					$valueInteger = XXX_Type::makeInteger($value);
					
					if ($parameters['minimumInteger'] && $valueInteger < $parameters['minimumInteger'])
					{
						$difference = $parameters['minimumInteger'] - $valueInteger;
						
						$newValue = $parameters['minimumInteger'];
						
						$result['operated'] = true;
						$result['value'] = $newValue;
						
						$variables['difference'] = $difference;
						$variables['minimumInteger'] = $parameters['minimumInteger'];
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'operation', 'minimumInteger');
						}
					}
					break;
				case 'maximumInteger':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'maximumInteger' => $parameters
						);
					}
					
					$valueInteger = XXX_Type::makeInteger($value);
					
					if ($parameters['maximumInteger'] && $valueInteger > $parameters['maximumInteger'])
					{
						$difference = $value - $parameters['maximumInteger'];
						
						$newValue = $parameters['maximumInteger'];
						
						$result['operated'] = true;
						$result['value'] = $newValue;
						
						$variables['difference'] = $difference;
						$variables['maximumInteger'] = $parameters['maximumInteger'];
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'operation', 'maximumInteger');
						}
					}
					break;
				case 'round':
					
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'decimals' => $parameters
						);
					}
									
					$valueNumber = XXX_Type::makeNumber($value);
					
					$decimals = 0;
					
					if ($parameters['decimals'])
					{
						$decimals = $parameters['decimals'];
					}
					
					$valueNumberRound = XXX_Number::round($valueNumber, $decimals);
					
					if ($value != $valueNumberRound)
					{
						$difference = $valueNumber - $valueNumberRound;
						
						if ($valueNumber < $valueNumberRound)
						{
							$difference = $valueNumberRound - $valueNumber;
						}
						
						$result['operated'] = true;
						$result['value'] = $valueNumberRound;
						
						$variables['difference'] = $difference;
						$feedbackMessageGrammaticalNumberFormQuantity = XXX_Number::round($difference);
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'operation', 'round');
						}
					}
					break;
				case 'floor':
					$valueNumber = XXX_Type::makeNumber($value);
					
					$valueNumberFloor = XXX_Number::floor($valueNumber);
					
					if ($value != $valueNumberFloor)
					{
						$difference = $valueNumber - $valueNumberFloor;
						
						if ($valueNumber < $valueNumberFloor)
						{
							$difference = $valueNumberFloor - $valueNumber;
						}
						
						$result['operated'] = true;
						$result['value'] = $valueNumberFloor;
						
						$variables['difference'] = $difference;
						$feedbackMessageGrammaticalNumberFormQuantity = XXX_Number::round($difference);
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'operation', 'floor');
						}
					}
					break;
				case 'ceil':
					$valueNumber = XXX_Type::makeNumber($value);
					
					$valueNumberCeil = XXX_Number::ceil($valueNumber);
					
					if ($value != $valueNumberCeil)
					{
						$difference = $valueNumber - $valueNumberCeil;
						
						if ($valueNumber < $valueNumberCeil)
						{
							$difference = $valueNumberCeil - $valueNumber;
						}
						
						$result['operated'] = true;
						$result['value'] = $valueNumberCeil;
						
						$variables['difference'] = $difference;
						$feedbackMessageGrammaticalNumberFormQuantity = XXX_Number::round($difference);
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'operation', 'ceil');
						}
					}
					break;
			}
			
			if (!($texts == false || $texts == '' || XXX_Type::isEmptyArray($texts)))
			{
				$result['feedbackMessage'] = XXX_I18n_Translation::composeVariableText($texts, $variables, $feedbackMessageGrammaticalNumberFormQuantity);
			}
			
			return $result;
		}
		
		public static function validateValue ($value = '', $action = '', $texts = '', $parameters = array(), $eventTrigger = '')
		{
			$result = array
			(
				'validated' => true,
				'feedbackMessage' => ''
			);
			
			$variables = array();
			$feedbackMessageGrammaticalNumberFormQuantity = 0;
			$noTexts = $texts == '' || XXX_Type::isEmptyArray($texts);
			$useDefaultTexts = $noTexts && $texts !== false;
			
			switch ($action)
			{
				case 'required':
					if (XXX_Type::isEmpty($value))
					{
						$result['validated'] = false;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'required');
						}
					}
					break;
				case 'string':
					$valueString = XXX_Type::makeString($value);
					
					if ($valueString != $value)
					{
						$result['validated'] = false;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'string');
						}
					}
					break;
				case 'minimumByteSize':
					
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'minimumByteSize' => $parameters
						);
					}
					
					$valueByteSize = XXX_String::getByteSize($value);
					
					if ($parameters['minimumByteSize'] && $valueByteSize < $parameters['minimumByteSize'])
					{
						$difference = $parameters['minimumByteSize'] - $valueByteSize;
						
						$result['validated'] = false;					
						$variables['difference'] = $difference;	
						$variables['minimumByteSize'] = $parameters['minimumByteSize'];			
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'minimumByteSize');
						}
					}	
					break;
				case 'maximumByteSize':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'maximumByteSize' => $parameters
						);
					}
					
					$valueByteSize = XXX_String::getByteSize($value);
					 
					if ($parameters['maximumByteSize'] && $valueByteSize > $parameters['maximumByteSize'])
					{
						$difference =  $valueByteSize - $parameters['maximumByteSize'];
						
						$result['validated'] = false;					
						$variables['difference'] = $difference;
						$variables['maximumByteSize'] = $parameters['maximumByteSize'];		
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'maximumByteSize');
						}
					}
					break;
				case 'minimumCharacterLength':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'minimumCharacterLength' => $parameters
						);
					}
					
					$valueCharacterLength = XXX_String::getCharacterLength($value);
					
					if ($parameters['minimumCharacterLength'] && $valueCharacterLength < $parameters['minimumCharacterLength'])
					{
						$difference = $parameters['minimumCharacterLength'] - $valueCharacterLength;
						
						$result['validated'] = false;
						$variables['difference'] = $difference;			
						$variables['minimumCharacterLength'] = $parameters['minimumCharacterLength'];				
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'minimumCharacterLength');
						}
					}
					
					break;
				case 'maximumCharacterLength':
					$valueCharacterLength = XXX_String::getCharacterLength($value);
					
					if ($parameters['maximumCharacterLength'] && $valueCharacterLength > $parameters['maximumCharacterLength'])
					{
						$difference = $valueCharacterLength - $parameters['maximumCharacterLength'];
						
						$result['validated'] = false;					
						$variables['difference'] = $difference;	
						$variables['maximumCharacterLength'] = $parameters['maximumCharacterLength'];					
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'maximumCharacterLength');
						}
					}
					break;
				case 'minimumWordCount':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'minimumWordCount' => $parameters
						);
					}
					
					$valueWordCount = XXX_String::getWordCount($value);
					
					if ($parameters['minimumWordCount'] && $valueWordCount < $parameters['minimumWordCount'])
					{
						$difference = $parameters['minimumWordCount'] - $valueWordCount;
						
						$result['validated'] = false;					
						$variables['difference'] = $difference;			
						$variables['minimumWordCount'] = $parameters['minimumWordCount'];			
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'minimumWordCount');
						}
					}
					break;
				case 'maximumWordCount':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'maximumWordCount' => $parameters
						);
					}
					
					$valueWordCount = XXX_String::getWordCount($value);
					
					if ($parameters['maximumWordCount'] && $valueWordCount > $parameters['maximumWordCount'])
					{
						$difference = $valueWordCount - $parameters['maximumWordCount'];
						
						$result['validated'] = false;					
						$variables['difference'] = $difference;
						$variables['maximumWordCount'] = $parameters['maximumWordCount'];
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'maximumWordCount');
						}
					}
					break;
				case 'number':
					$valueNumber = XXX_Type::makeNumber($value);
					
					if ($valueNumber != $value)
					{
						$result['validated'] = false;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'number');
						}
					}
					break;
				case 'minimumNumber':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'minimumNumber' => $parameters
						);
					}
					
					$valueNumber = XXX_Type::makeNumber($value);
					
					if ($parameters['minimumNumber'] && $valueNumber < $parameters['minimumNumber'])
					{
						$difference = $parameters['minimumNumber'] - $valueNumber;
						
						$result['validated'] = false;					
						$variables['difference'] = $difference;
						$variables['minimumNumber'] = $parameters['minimumNumber'];	
						$feedbackMessageGrammaticalNumberFormQuantity = XXX_Number::round($difference);
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'minimumNumber');
						}
					}
					break;
				case 'maximumNumber':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'maximumNumber' => $parameters
						);
					}
					
					$valueNumber = XXX_Type::makeNumber($value);
					
					if ($parameters['maximumNumber'] && $valueNumber > $parameters['maximumNumber'])
					{
						$difference = $valueNumber - $parameters['maximumNumber'];
						
						$result['validated'] = false;					
						$variables['difference'] = $difference;
						$variables['maximumNumber'] = $parameters['maximumNumber'];	
						$feedbackMessageGrammaticalNumberFormQuantity = XXX_Number::round($difference);
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'maximumNumber');
						}
					}
					break;
				case 'float':
					$valueFloat = XXX_Type::makeFloat($value);
					
					if ($valueFloat != $value)
					{
						$result['validated'] = false;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'float');
						}
					}
					break;
				case 'minimumFloat':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'minimumFloat' => $parameters
						);
					}
					
					$valueFloat = XXX_Type::makeFloat($value);
					
					if ($parameters['minimumFloat'] && $valueFloat < $parameters['minimumFloat'])
					{
						$difference = $parameters['minimumFloat'] - $valueFloat;
						
						$result['validated'] = false;					
						$variables['difference'] = $difference;
						$variables['minimumFloat'] = $parameters['minimumFloat'];	
						$feedbackMessageGrammaticalNumberFormQuantity = XXX_Number::round($difference);
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'minimumFloat');
						}
					}
					break;
				case 'maximumFloat':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'maximumFloat' => $parameters
						);
					}
					
					$valueFloat = XXX_Type::makeFloat($value);
					
					if ($parameters['maximumFloat'] && $valueFloat > $parameters['maximumFloat'])
					{
						$difference = $valueFloat - $parameters['maximumFloat'];
						
						$result['validated'] = false;					
						$variables['difference'] = $difference;
						$variables['maximumFloat'] = $parameters['maximumFloat'];	
						$feedbackMessageGrammaticalNumberFormQuantity = XXX_Number::round($difference);
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'maximumFloat');
						}
					}
					break;
				case 'integer':
					$valueInteger = XXX_Type::makeInteger($value);
					
					if ($valueInteger != $value)
					{
						$result['validated'] = false;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'integer');
						}
					}
					break;
				case 'minimumInteger':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'minimumInteger' => $parameters
						);
					}
					
					$valueInteger = XXX_Type::makeInteger($value);
					
					if ($parameters['minimumInteger'] && $valueInteger < $parameters['minimumInteger'])
					{
						$difference = $parameters['minimumInteger'] - $valueInteger;
						
						$result['validated'] = false;					
						$variables['difference'] = $difference;
						$variables['minimumInteger'] = $parameters['minimumInteger'];	
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'minimumInteger');
						}
					}
					break;
				case 'maximumInteger':
					
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'maximumInteger' => $parameters
						);
					}
					
					$valueInteger = XXX_Type::makeInteger($value);
					
					if ($valueInteger > $parameters['maximumInteger'])
					{
						$difference = $valueInteger - $parameters['maximumInteger'];
						
						$result['validated'] = false;					
						$variables['difference'] = $difference;
						$variables['maximumInteger'] = $parameters['maximumInteger'];
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'maximumInteger');
						}
					}
					break;
				case 'minimumPassSecurityRating':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'minimumPassSecurityRating' => $parameters
						);
					}
					
					if ($parameters['minimumPassSecurityRating'] && $parameters['minimumPassSecurityRating'] > 0)
					{
						$passSecurityRating = XXX_String::getPassSecurityRating($value);
						
						if ($passSecurityRating < $parameters['minimumPassSecurityRating'])
						{
							$difference = $parameters['minimumPassSecurityRating'] - $passSecurityRating;
							
							$result['validated'] = false;
							$variables['difference'] = $difference;
							$variables['passSecurityRating'] = $passSecurityRating;
							$variables['minimumPassSecurityRating'] = $parameters['minimumPassSecurityRating'];
							$feedbackMessageGrammaticalNumberFormQuantity = $difference;
							
							if ($useDefaultTexts)
							{
								$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'minimumPassSecurityRating');
							}
						}
					}
					break;
				case 'matchValue':	
				case 'matchValues':					
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'value' => $parameters
						);
					}
					
					if (XXX_Array::hasKey($parameters, 'values'))
					{
						$parameters['value'] = $parameters['values'];
					}
								
					if (XXX_Array::hasKey($parameters, 'value'))
					{
						if (XXX_Type::isArray($parameters['value']))
						{
							if (!XXX_Array::hasValue($parameters['value'], $value))
							{
								$result['validated'] = false;
								
								$variables['value'] = XXX_Array::joinValuesToString($parameters['value'], ', ');
								
								if ($useDefaultTexts)
								{
									$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'matchValue');
								}
							}
						}
						else
						{
							if ($value != $parameters['value'])
							{
								$result['validated'] = false;
								
								$variables['value'] = $parameters['value'];
								
								if ($useDefaultTexts)
								{
									$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'matchValue');
								}
							}
						}
					}
					break;
				case 'doNotMatchValue':
				case 'doNotMatchValues':
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'value' => $parameters
						);
					}
					
					if (XXX_Array::hasKey($parameters, 'values'))
					{
						$parameters['value'] = $parameters['values'];
					}
								
					if (XXX_Array::hasKey($parameters, 'value'))
					{
						if (XXX_Type::isArray($parameters['value']))
						{
							if (XXX_Array::hasValue($parameters['value'], $value))
							{
								$result['validated'] = false;
								
								$variables['value'] = XXX_Array::joinValuesToString($parameters['value'], ', ');
								
								if ($useDefaultTexts)
								{
									$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'doNotMatchValue');
								}
							}
						}
						else
						{
							if ($value == $parameters['value'])
							{
								$result['validated'] = false;
								
								$variables['value'] = $parameters['value'];
								
								if ($useDefaultTexts)
								{
									$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'doNotMatchValue');
								}
							}
						}
					}
					break;
				case 'matchPattern':
					
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'pattern' => $parameters,
							'patternModifiers' => ''
						);
					}
					
					if (!XXX_String_Pattern::hasMatch($value, $parameters['pattern'], $parameters['patternModifiers']))
					{
						$result['validated'] = false;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'matchPattern');
						}
					}
					break;
				case 'doNotMatchPattern':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'pattern' => $parameters,
							'patternModifiers' => ''
						);
					}
				
					if ($parameters['pattern'] && XXX_String_Pattern::hasMatch($value, $parameters['pattern'], $parameters['patternModifiers']))
					{
						$result['validated'] = false;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'doNotMatchPattern');
						}
					}
					break;
				case 'synchronousCallback':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'functionName' => $parameters
						);
					}
					
					if ($parameters['context'] && $parameters['functionName'])
					{
						$temp = call_user_func_array(array($parameters['context'], $parameters['functionName']), $value);
						
						if (!$temp)
						{
							$result['validated'] = false;
							
							if ($useDefaultTexts)
							{
								$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'synchronousCallback');
							}
						}
					}
					else if ($parameters['functionName'])
					{
						$temp = call_user_func($parameters['functionName'], $value);
						
						if (!$temp)
						{
							$result['validated'] = false;
							
							if ($useDefaultTexts)
							{
								$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'validation', 'synchronousCallback');
							}
						}
					}
					break;
			}
						
			if (!($texts == false || $texts == '' || XXX_Type::isEmptyArray($texts)))
			{
				$result['feedbackMessage'] = XXX_I18n_Translation::composeVariableText($texts, $variables, $feedbackMessageGrammaticalNumberFormQuantity);
			}
			
			return $result;
		}
		
		public static function informAboutValue ($value = '', $action = '', $texts = '', $parameters = array(), $eventTrigger = '')
		{
			$result = array
			(
				'informed' => false,
				'feedbackMessage' => ''
			);
			
			$variables = array();
			$feedbackMessageGrammaticalNumberFormQuantity = 0;
			$noTexts = $texts == '' || XXX_Type::isEmptyArray($texts);
			$useDefaultTexts = $noTexts && $texts !== false;
			
			switch ($action)
			{
				case 'byteSize':
					$valueByteSize = XXX_String::getByteSize($value);
					
					if ($valueByteSize > 0)
					{
						$result['informed'] = true;
						
						$variables['byteSize'] = $valueByteSize;					
						$feedbackMessageGrammaticalNumberFormQuantity = $valueByteSize;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'information', 'byteSize');
						}
					}
					break;
				case 'characterLength':
					$valueCharacterLength = XXX_String::getCharacterLength($value);
					
					if ($valueCharacterLength > 0)
					{
						$result['informed'] = true;
						
						$variables['characterLength'] = $valueCharacterLength;					
						$feedbackMessageGrammaticalNumberFormQuantity = $valueCharacterLength;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'information', 'characterLength');
						}
					}
					break;
				case 'wordCount':
					$valueWordCount = XXX_String::getWordCount($value);
					
					if ($valueWordCount > 0)
					{
						$result['informed'] = true;
						
						$variables['wordCount'] = $valueWordCount;					
						$feedbackMessageGrammaticalNumberFormQuantity = $valueWordCount;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'information', 'wordCount');
						}
					}
					break;
				case 'suffixCharacterPeek':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'peekCharacterLength' => $parameters,
							'eventTrigger' => 'valueChange'
						);
					}
					
					if (self::isAllowedEventTrigger($eventTrigger, $parameters['matchEventTrigger'], $parameters['doNotMatchEventTrigger']))
					{
						$valueCharacterLength = XXX_String::getCharacterLength($value);
					
						if (!$parameters['minimumCharacterLength'] || $valueCharacterLength >= $parameters['minimumCharacterLength'])
						{
							$peekCharacterLength = XXX_Number::lowest($valueCharacterLength, $parameters['peekCharacterLength']);
							
							if ($peekCharacterLength > 0)
							{
								$result['informed'] = true;
								
								$maskedPartCharacterLength = $valueCharacterLength - $peekCharacterLength;
								
								$previewPart = XXX_String::getPart($value, -$peekCharacterLength);
								
								$temp = '';
								
								for ($i = 0, $iEnd = $maskedPartCharacterLength; $i< $iEnd; ++$i)
								{
									$temp .= '*';
								}
								
								$temp .= $previewPart;
								
								$variables['suffixCharacterPeek'] = $temp;
								$variables['characterLength'] = $valueCharacterLength;
								
								if ($useDefaultTexts)
								{
									$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'information', 'suffixCharacterPeek');
								}
							}
						}
					}
					break;
				case 'passSecurityAdvice':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'minimumPassSecurityRating' => $parameters,
							'eventTrigger' => 'valueChange'
						);
					}
					
					$passSecurityRating = XXX_String::getPassSecurityRating($value);
					
					if (self::isAllowedEventTrigger($eventTrigger, $parameters['matchEventTrigger'], $parameters['doNotMatchEventTrigger']))
					{
						if (!$parameters['minimumPassSecurityRating'] || $passSecurityRating < $parameters['minimumPassSecurityRating'])
						{
							if (!$result['informed'])
							{
								$hasDigit = XXX_String_Pattern::hasMatch($value, '[0-9]', '');
								
								if (!$hasDigit)
								{
									$result['informed'] = true;
									
									$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'information', 'passSecurityAdvice', 'digit');
								}
							}
							
							if (!$result['informed'])
							{
								$hasLowerCaseLetter = XXX_String_Pattern::hasMatch($value, '[a-z]', '');
								
								if (!$hasLowerCaseLetter)
								{
									$result['informed'] = true;
									
									$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'information', 'passSecurityAdvice', 'lowerCaseLetter');
								}
							}
							
							if (!$result['informed'])
							{
								$hasUpperCaseLetter = XXX_String_Pattern::hasMatch($value, '[A-Z]', '');
								
								if (!$hasUpperCaseLetter)
								{
									$result['informed'] = true;
									
									$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'information', 'passSecurityAdvice', 'upperCaseLetter');
								}
							}
							
							if (!$result['informed'])
							{
								$hasSpecialCharacter = XXX_String_Pattern::hasMatch($value, '\\W', '');
								
								if (!$hasSpecialCharacter)
								{
									$result['informed'] = true;
									
									$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'information', 'passSecurityAdvice', 'specialCharacter');
								}
							}
						}
					}
					break;
				case 'passSecurityRating':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'minimumPassSecurityRating' => $parameters,
							'eventTrigger' => 'valueChange'
						);
					}
					
					$valueCharacterLength = XXX_String::getCharacterLength($value);
					
					if (self::isAllowedEventTrigger($eventTrigger, $parameters['matchEventTrigger'], $parameters['doNotMatchEventTrigger']))
					{
						if (!$parameters['minimumCharacterLength'] || $valueCharacterLength >= $parameters['minimumCharacterLength'])
						{
							$passSecurityRating = XXX_String::getPassSecurityRating($value);
							
							$result['informed'] = true;
							
							$variables['passSecurityRating'] = $passSecurityRating;					
							$feedbackMessageGrammaticalNumberFormQuantity = $passSecurityRating;
							
							if ($useDefaultTexts)
							{
								$texts = XXX_I18n_Translation::get('input', 'actions', 'value', 'information', 'passSecurityRating');
							}
						}
					}
					
					break;
			}
						
			if (!($texts == false || $texts == '' || XXX_Type::isEmptyArray($texts)))
			{	
				$result['feedbackMessage'] = XXX_I18n_Translation::composeVariableText($texts, $variables, $feedbackMessageGrammaticalNumberFormQuantity);
			}
			
			return $result;
		}
		
	// (single) option
		
		public static function operateOnOption ($option = array(), $action = '', $texts = '', $parameters = array(), $eventTrigger = '')
		{
			$result = array
			(
				'operated' => false,
				'option' => $option,
				'feedbackMessage' => ''
			);
			
			$variables = array();
			$feedbackMessageGrammaticalNumberFormQuantity = 0;
			$noTexts = $texts == '' || XXX_Type::isEmptyArray($texts);
			$useDefaultTexts = $noTexts && $texts !== false;
						
			switch ($action)
			{
			}
			
			if (!($texts == false || $texts == '' || XXX_Type::isEmptyArray($texts)))
			{	
				$result['feedbackMessage'] = XXX_I18n_Translation::composeVariableText($texts, $variables, $feedbackMessageGrammaticalNumberFormQuantity);
			}
			
			return $result;
		}
		
		public static function validateOption ($option = array(), $action = '', $texts = '', $parameters = array(), $eventTrigger = '')
		{
			$result = array
			(
				'validated' => true,
				'feedbackMessage' => ''
			);
			
			$variables = array();
			$feedbackMessageGrammaticalNumberFormQuantity = 0;
			$noTexts = $texts == '' || XXX_Type::isEmptyArray($texts);
			$useDefaultTexts = $noTexts && $texts !== false;
			
			switch ($action)
			{
				case 'required':
					if (!$option['selected'])
					{
						$result['validated'] = false;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'option', 'validation', 'required');
						}
					}
					break;
				case 'matchValue':	
				case 'matchValues':					
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'value' => $parameters
						);
					}
					
					if (XXX_Array::hasKey($parameters, 'values'))
					{
						$parameters['value'] = $parameters['values'];
					}
								
					if (XXX_Array::hasKey($parameters, 'value'))
					{
						if (XXX_Type::isArray($parameters['value']))
						{
							if (!$option['selected'] || !XXX_Array::hasValue($parameters['value'], $option['value']))
							{
								$result['validated'] = false;
								
								$variables['value'] = XXX_Array::joinValuesToString($parameters['value'], ', ');
								
								if ($useDefaultTexts)
								{
									$texts = XXX_I18n_Translation::get('input', 'actions', 'option', 'validation', 'matchValue');
								}
							}
						}
						else
						{
							if (!$option['selected'] || $option['value'] != $parameters['value'])
							{
								$result['validated'] = false;
								
								$variables['value'] = $parameters['value'];
								
								if ($useDefaultTexts)
								{
									$texts = XXX_I18n_Translation::get('input', 'actions', 'option', 'validation', 'matchValue');
								}
							}
						}
					}
					break;
				case 'doNotMatchValue':
				case 'doNotMatchValues':
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'value' => $parameters
						);
					}
					
					if (XXX_Array::hasKey($parameters, 'values'))
					{
						$parameters['value'] = $parameters['values'];
					}
								
					if (XXX_Array::hasKey($parameters, 'value'))
					{
						if (XXX_Type::isArray($parameters['value']))
						{
							if ($option['selected'] && XXX_Array::hasValue($parameters['value'], $option['value']))
							{
								$result['validated'] = false;
								
								$variables['value'] = XXX_Array::joinValuesToString($parameters['value'], ', ');
								
								if ($useDefaultTexts)
								{
									$texts = XXX_I18n_Translation::get('input', 'actions', 'option', 'validation', 'doNotMatchValue');
								}
							}
						}
						else
						{
							if ($option['selected'] && $option['value'] == $parameters['value'])
							{
								$result['validated'] = false;
								
								$variables['value'] = $parameters['value'];
								
								if ($useDefaultTexts)
								{
									$texts = XXX_I18n_Translation::get('input', 'actions', 'option', 'validation', 'doNotMatchValue');
								}
							}
						}
					}
					break;
				case 'synchronousCallback':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'functionName' => $parameters
						);
					}
					
					if ($parameters['context'] && $parameters['functionName'])
					{
						$temp = call_user_func_array(array($parameters['context'], $parameters['functionName']), $option);
						
						if (!$temp)
						{
							$result['validated'] = false;
							
							if ($useDefaultTexts)
							{
								$texts = XXX_I18n_Translation::get('input', 'actions', 'option', 'validation', 'synchronousCallback');
							}
						}
					}
					else if ($parameters['functionName'])
					{
						$temp = call_user_func($parameters['functionName'], $option);
						
						if (!$temp)
						{
							$result['validated'] = false;
							
							if ($useDefaultTexts)
							{
								$texts = XXX_I18n_Translation::get('input', 'actions', 'option', 'validation', 'synchronousCallback');
							}
						}
					}
					break;
			}
						
			if (!($texts == false || $texts == '' || XXX_Type::isEmptyArray($texts)))
			{
				$result['feedbackMessage'] = XXX_I18n_Translation::composeVariableText($texts, $variables, $feedbackMessageGrammaticalNumberFormQuantity);
			}
			
			return $result;
		}
		
		public static function informAboutOption ($option = array(), $action = '', $texts = '', $parameters = array(), $eventTrigger = '')
		{
			$result = array
			(
				'informed' => false,
				'feedbackMessage' => ''
			);
			
			$variables = array();
			$feedbackMessageGrammaticalNumberFormQuantity = 0;
			$noTexts = $texts == '' || XXX_Type::isEmptyArray($texts);
			$useDefaultTexts = $noTexts && $texts !== false;
			
			switch ($action)
			{
				case 'selected':
					if ($option['selected'])
					{
						$result['informed'] = true;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'option', 'information', 'selected');
						}
					}
					break;
				case 'notSelected':
					if (!$option['selected'])
					{
						$result['informed'] = true;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'option', 'information', 'notSelected');
						}
					}
					break;
				case 'selectedTotal':
					$variables['selectedTotal'] = 0;
					
					if ($option['selected'])
					{
						$variables['selectedTotal'] = 1;
					}
					
					$result['informed'] = true;
					
					if ($useDefaultTexts)
					{
						$texts = XXX_I18n_Translation::get('input', 'actions', 'option', 'information', 'selectedTotal');
					}
					break;
			}
						
			if (!($texts == false || $texts == '' || XXX_Type::isEmptyArray($texts)))
			{
				$result['feedbackMessage'] = XXX_I18n_Translation::composeVariableText($texts, $variables, $feedbackMessageGrammaticalNumberFormQuantity);
			}
			
			return $result;
		}
		
	// (multiple) options
		
		public static function operateOnOptions ($selectedOptionValues = array(), $action = '', $texts = '', $parameters = array(), $eventTrigger = '')
		{
			$result = array
			(
				'operated' => false,
				'selectedOptionValues' => $selectedOptionValues,
				'deselectedOptionValues' => array(),
				'feedbackMessage' => ''
			);
			
			$variables = array();
			$feedbackMessageGrammaticalNumberFormQuantity = 0;
			$noTexts = $texts == '' || XXX_Type::isEmptyArray($texts);
			$useDefaultTexts = $noTexts && $texts !== false;
						
			switch ($action)
			{
				case 'maximumSelected':
					
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'maximumSelected' => $parameters
						);
					}
					
					$selectedTotal = XXX_Array::getFirstLevelItemTotal($selectedOptionValues);
					
					if ($parameters['maximumSelected'] && $selectedTotal > $parameters['maximumSelected'])
					{
						$difference = $selectedTotal - $parameters['maximumSelected'];
					
						$deselectedOptionValues = array();
						
						for ($i = $parameters['maximumSelected'], $iEnd = $selectedTotal; $i < $iEnd; ++$i)
						{
							$deselectedOptionValues[] = $selectedOptionValues[$i];
						}
						
						$result['operated'] = true;
						$result['deselectedOptionValues'] = $deselectedOptionValues;
						
						$variables['difference'] = $difference;
						$variables['maximumSelected'] = $parameters['maximumSelected'];
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'options', 'operation', 'maximumSelected');
						}
					}
					break;
			}
						
			if (!($texts == false || $texts == '' || XXX_Type::isEmptyArray($texts)))
			{			
				$result['feedbackMessage'] = XXX_I18n_Translation::composeVariableText($texts, $variables, $feedbackMessageGrammaticalNumberFormQuantity);
			}
			
			return $result;
		}
		
		public static function validateOptions ($selectedOptionValues = array(), $action = '', $texts = '', $parameters = array(), $eventTrigger = '')
		{
			$result = array
			(
				'validated' => true,
				'feedbackMessage' => ''
			);
			
			$variables = array();
			$feedbackMessageGrammaticalNumberFormQuantity = 0;
			$noTexts = $texts == '' || XXX_Type::isEmptyArray($texts);
			$useDefaultTexts = $noTexts && $texts !== false;
			
			switch ($action)
			{
				case 'required':
					$selectedTotal = XXX_Array::getFirstLevelItemTotal($selectedOptionValues);
					
					if ($selectedTotal == 0)
					{
						$result['validated'] = false;
						
						$variables['difference'] = 1;		
						$feedbackMessageGrammaticalNumberFormQuantity = 1;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'options', 'validation', 'required');
						}
					}
					break;
				case 'minimumSelected':
					
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'minimumSelected' => $parameters
						);
					}
					
					$selectedTotal = XXX_Array::getFirstLevelItemTotal($selectedOptionValues);
					
					if ($parameters['minimumSelected'] && $selectedTotal < $parameters['minimumSelected'])
					{
						$difference = $parameters['minimumSelected'] - $selectedTotal;
						
						$result['validated'] = false;
						
						$variables['difference'] = $difference;
						$variables['minimumSelected'] = $parameters['minimumSelected'];				
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'options', 'validation', 'minimumSelected');
						}
					}
					break;
				case 'maximumSelected':
					
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'maximumSelected' => $parameters
						);
					}
					
					$selectedTotal = XXX_Array::getFirstLevelItemTotal($selectedOptionValues);
					
					if ($parameters['maximumSelected'] && $selectedTotal > $parameters['maximumSelected'])
					{
						$difference = $selectedTotal - $parameters['maximumSelected'];
						
						$result['validated'] = false;
						
						$variables['difference'] = $difference;
						$variables['maximumSelected'] = $parameters['maximumSelected'];					
						$feedbackMessageGrammaticalNumberFormQuantity = $difference;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'options', 'validation', 'maximumSelected');
						}
					}
					break;
				case 'matchValue':
				case 'matchValues':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'value' => $parameters
						);
					}
					
					if (XXX_Array::hasKey($parameters, 'values'))
					{
						$parameters['value'] = $parameters['values'];
					}
								
					if (XXX_Array::hasKey($parameters, 'value'))
					{
						$selectedOptionValueTotal = XXX_Array::getFirstLevelItemTotal($selectedOptionValues);
					
						if ($selectedOptionValueTotal == 0)
						{
							$result['validated'] = false;
						}
						else
						{						
							if (XXX_Type::isArray($parameters['value']))
							{
								for ($i = 0, $iEnd = $selectedOptionValueTotal; $i < $iEnd; ++$i)
								{
									if (!XXX_Array::hasValue($parameters['value'], $selectedOptionValues[$i]))
									{
										$result['validated'] = false;
										
										$variables['value'] = XXX_Array::joinValuesToString($parameters['value'], ', ');
								
										if ($useDefaultTexts)
										{
											$texts = XXX_I18n_Translation::get('input', 'actions', 'options', 'validation', 'matchValue');
										}
										
										break;
									}
								}
							}
							else
							{
								for ($i = 0, $iEnd = $selectedOptionValueTotal; $i < $iEnd; ++$i)
								{
									if ($selectedOptionValues[$i] != $parameters['value'])
									{
										$result['validated'] = false;
										
										$variables['value'] = $parameters['value'];
								
										if ($useDefaultTexts)
										{
											$texts = XXX_I18n_Translation::get('input', 'actions', 'options', 'validation', 'matchValue');
										}
										
										break;
									}
								}
							}
						}
					}
					break;
				case 'doNotMatchValue':
				case 'doNotMatchValues':					
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'value' => $parameters
						);
					}
					
					if (XXX_Array::hasKey($parameters, 'values'))
					{
						$parameters['value'] = $parameters['values'];
					}
								
					if (XXX_Array::hasKey($parameters, 'value'))
					{
						$selectedOptionValueTotal = XXX_Array::getFirstLevelItemTotal($selectedOptionValues);
					
						if ($selectedOptionValueTotal == 0)
						{
							$result['validated'] = false;
						}
						else
						{						
							if (XXX_Type::isArray($parameters['value']))
							{
								for ($i = 0, $iEnd = $selectedOptionValueTotal; $i < $iEnd; ++$i)
								{
									if (XXX_Array::hasValue($parameters['value'], $selectedOptionValues[$i]))
									{
										$result['validated'] = false;
										
										$variables['value'] = XXX_Array::joinValuesToString($parameters['value'], ', ');
								
										if ($useDefaultTexts)
										{
											$texts = XXX_I18n_Translation::get('input', 'actions', 'options', 'validation', 'doNotMatchValue');
										}
										
										break;
									}
								}
							}
							else
							{
								for ($i = 0, $iEnd = $selectedOptionValueTotal; $i < $iEnd; ++$i)
								{
									if ($selectedOptionValues[$i] == $parameters['value'])
									{
										$result['validated'] = false;
										
										$variables['value'] = $parameters['value'];
								
										if ($useDefaultTexts)
										{
											$texts = XXX_I18n_Translation::get('input', 'actions', 'options', 'validation', 'doNotMatchValue');
										}
										
										break;
									}
								}
							}
						}
					}
					break;
				case 'synchronousCallback':
				
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array
						(
							'functionName' => $parameters
						);
					}
					
					if ($parameters['context'] && $parameters['functionName'])
					{
						$temp = call_user_func_array(array($parameters['context'], $parameters['functionName']), $selectedOptionValues);
						
						if (!$temp)
						{
							$result['validated'] = false;
							
							if ($useDefaultTexts)
							{
								$texts = XXX_I18n_Translation::get('input', 'actions', 'options', 'validation', 'synchronousCallback');
							}
						}
					}
					else if ($parameters['functionName'])
					{
						$temp = call_user_func($parameters['functionName'], $selectedOptionValues);
						
						if (!$temp)
						{
							$result['validated'] = false;
							
							if ($useDefaultTexts)
							{
								$texts = XXX_I18n_Translation::get('input', 'actions', 'options', 'validation', 'synchronousCallback');
							}
						}
					}
					break;
			}
						
			if (!($texts == false || $texts == '' || XXX_Type::isEmptyArray($texts)))
			{
				$result['feedbackMessage'] = XXX_I18n_Translation::composeVariableText($texts, $variables, $feedbackMessageGrammaticalNumberFormQuantity);
			}
						
			return $result;
		}
		
		public static function informAboutOptions ($selectedOptionValues = array(), $action = '', $texts = '', $parameters = array(), $eventTrigger = '')
		{
			$result = array
			(
				'informed' => false,
				'feedbackMessage' => ''
			);
			
			$variables = array();
			$feedbackMessageGrammaticalNumberFormQuantity = 0;
			$noTexts = $texts == '' || XXX_Type::isEmptyArray($texts);
			$useDefaultTexts = $noTexts && $texts !== false;
			
			switch ($action)
			{
				case 'selectedTotal':
					$selectedTotal = XXX_Array::getFirstLevelItemTotal($selectedOptionValues);
										
					$variables['selectedTotal'] = $selectedTotal;
					$result['informed'] = true;		
					
					if ($useDefaultTexts)
					{
						$texts = XXX_I18n_Translation::get('input', 'actions', 'options', 'information', 'selectedTotal');
					}			
					break;
			}
						
			if (!($texts == false || $texts == '' || XXX_Type::isEmptyArray($texts)))
			{		
				$result['feedbackMessage'] = XXX_I18n_Translation::composeVariableText($texts, $variables, $feedbackMessageGrammaticalNumberFormQuantity);
			}
			
			return $result;
		}
	
	// date
		
		public static function validateDate ($date = array(), $action = '', $texts = '', $parameters = array(), $eventTrigger = '')
		{
			$result = array
			(
				'validated' => true,
				'feedbackMessage' => ''
			);
			
			$variables = array();
			$feedbackMessageGrammaticalNumberFormQuantity = 0;
			$noTexts = $texts == '' || XXX_Type::isEmptyArray($texts);
			$useDefaultTexts = $noTexts && $texts !== false;
			
			$existingDate = XXX_TimestampHelpers::isExistingDate($date['year'], $date['month'], $date['date']);
			
			switch ($action)
			{
				case 'exists':
					if (!$existingDate)
					{
						$result['validated'] = false;
						
						$variables['daysInMonth'] = XXX_TimestampHelpers::getDayTotalInMonth($date['year'], $date['month']);
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'date', 'validation', 'exists');
						}
					}
					break;
				case 'past':
					$timestamp = 0;
					$now = XXX_TimestampHelpers::getCurrentSecondTimestamp();
					
					if ($existingDate)
					{
						$timestamp = new XXX_Timestamp(array('year' => $date['year'], 'month' => $date['month'], 'date' => $date['date'], 'hour' => 0, 'minute' => 0, 'second' => 0));
						$timestamp = $timestamp->get();
					}
					
					if (!$existingDate || $timestamp >= $now)
					{
						$result['validated'] = false;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'date', 'validation', 'past');
						}
					}					
					break;
				case 'future':
					$timestamp = 0;
					$now = XXX_TimestampHelpers::getCurrentSecondTimestamp();
					
					if ($existingDate)
					{
						$timestamp = new XXX_Timestamp(array('year' => $date['year'], 'month' => $date['month'], 'date' => $date['date'], 'hour' => 0, 'minute' => 0, 'second' => 0));
						$timestamp = $timestamp->get();
					}
					
					if (!$existingDate || $timestamp <= $now)
					{
						$result['validated'] = false;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'date', 'validation', 'future');
						}
					}					
					break;
				case 'minimumDateOfBirthYearAge':
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array  
						(
							'minimumDateOfBirthYearAge' => $parameters
						);
					}
					
					$dateOfBirthYearAge = 0;
					
					if ($existingDate)
					{
						$dateOfBirthYearAge = XXX_TimestampHelpers::getDateOfBirthYearAge($date['year'], $date['month'], $date['date']);
					}
						
					if (!$existingDate || ($parameters['minimumDateOfBirthYearAge'] && $dateOfBirthYearAge < $parameters['minimumDateOfBirthYearAge']))
					{
						$variables['minimumDateOfBirthYearAge'] = $parameters['minimumDateOfBirthYearAge'];
						$variables['dateOfBirthYearAge'] = $dateOfBirthYearAge;
						$result['validated'] = false;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'date', 'validation', 'minimumDateOfBirthYearAge');
						}
					}
					break;
				case 'maximumDateOfBirthYearAge':
					if (!XXX_Type::isArray($parameters))
					{
						$parameters = array  
						(
							'maximumDateOfBirthYearAge' => $parameters
						);
					}
					
					$dateOfBirthYearAge = 0;
					
					if ($existingDate)
					{
						$dateOfBirthYearAge = XXX_TimestampHelpers::getDateOfBirthYearAge($date['year'], $date['month'], $date['date']);
					}
						
					if (!$existingDate || ($parameters['maximumDateOfBirthYearAge'] && $dateOfBirthYearAge > $parameters['maximumDateOfBirthYearAge']))
					{
						$variables['maximumDateOfBirthYearAge'] = $parameters['maximumDateOfBirthYearAge'];
						$variables['dateOfBirthYearAge'] = $dateOfBirthYearAge;
						$result['validated'] = false;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'date', 'validation', 'maximumDateOfBirthYearAge');
						}
					}
					
					break;
				case 'minimumDate':
					if (!$existingDate || $date['year'] < $parameters['minimumDate']['year'] || ($date['year'] == $parameters['minimumDate']['year'] && $date['month'] < $parameters['minimumDate']['month']) || ($date['year'] == $parameters['minimumDate']['year'] && $date['month'] == $parameters['minimumDate']['month']  && $date['date'] < $parameters['minimumDate']['date']))
					{
						$result['validated'] = false;
					}
					break;
				case 'maximumDate':
					if (!$existingDate || $date['year'] > $parameters['maximumDate']['year'] || ($date['year'] == $parameters['maximumDate']['year'] && $date['month'] > $parameters['maximumDate']['month']) || ($date['year'] == $parameters['maximumDate']['year'] && $date['month'] == $parameters['maximumDate']['month']  && $date['date'] > $parameters['maximumDate']['date']))
					{
						$result['validated'] = false;
					}
					break;
			}
			
			if (!($texts == false || $texts == '' || XXX_Type::isEmptyArray($texts)))
			{
				$result['feedbackMessage'] = XXX_I18n_Translation::composeVariableText($texts, $variables, $feedbackMessageGrammaticalNumberFormQuantity);
			}
						
			return $result;
		}
		
		public static function informAboutDate ($date = array(), $action = '', $texts = '', $parameters = array(), $eventTrigger = '')
		{
			$result = array
			(
				'informed' => false,
				'feedbackMessage' => ''
			);
			
			$variables = array();
			$feedbackMessageGrammaticalNumberFormQuantity = 0;
			$noTexts = $texts == '' || XXX_Type::isEmptyArray($texts);
			$useDefaultTexts = $noTexts && $texts !== false;
			
			$existingDate = XXX_TimestampHelpers::isExistingDate($date['year'], $date['month'], $date['date']);
			
			switch ($action)
			{
				case 'dateOfBirthYearAge':
					if ($existingDate)
					{
						$dateOfBirthYearAge = XXX_TimestampHelpers::getDateOfBirthYearAge($date['year'], $date['month'], $date['date']);
						
						if ($dateOfBirthYearAge > 0)
						{
							$variables['dateOfBirthYearAge'] = $dateOfBirthYearAge;
							$result['informed'] = true;
							
							if ($useDefaultTexts)
							{
								$texts = XXX_I18n_Translation::get('input', 'actions', 'date', 'information', 'dateOfBirthYearAge');
							}
						}
					}
					break;
				case 'dayOfTheWeek':
					if ($existingDate)
					{
						$timestamp = new XXX_Timestamp(array('year' => $date['year'], 'month' => $date['month'], 'date' => $date['date'], 'hour' => 0, 'minute' => 0, 'second' => 0));
						
						$timestampParts = $timestamp->parse();
						
						$dayOfTheWeek = XXX_I18n_Translation::get('dateTime', 'daysOfTheWeek', 'names');
						$dayOfTheWeek = $dayOfTheWeek[$timestampParts['dayOfTheWeek'] - 1];
						
						$variables['dayOfTheWeek'] = $dayOfTheWeek;
						$result['informed'] = true;
						
						if ($useDefaultTexts)
						{
							$texts = XXX_I18n_Translation::get('input', 'actions', 'date', 'information', 'dayOfTheWeek');
						}
					}
					break;
			}
			
			if (!($texts == false || $texts == '' || XXX_Type::isEmptyArray($texts)))
			{		
				$result['feedbackMessage'] = XXX_I18n_Translation::composeVariableText($texts, $variables, $feedbackMessageGrammaticalNumberFormQuantity);
			}
			
			return $result;
		}
	
}

?>