<?php

// HTML and JavaScript filter to normalize / clean up markup (brackets, tags, attributes, attribute values, protocols, entities etc.) and prevent XSS (Cross Site Scripting). Based on the principle of white listing instead of black listing.

// TODO: tag renaming, like b > strong etc.

// Special thanks/credits to Cal Henderson (www.iamcal.com), Jang Kim, Dan Bogan
class XXX_HTML_Filter
{	
	protected $tagCounts = array();
	
	// tags and attributes that are allowed
	public $allowedTagsAndAttributes = array
	(
		'a' => array
		(
			'href',
			'target',
			'title'
		),
		'b' => array(),
		'i' => array(),
		'strong' => array(),
		'em' => array(),
		'ul' => array(),
		'ol' => array(),
		'li' => array(),
		'img' => array
		(
			'src',
			'width',
			'height',
			'alt'
		),
	);
	
	// tags which should always be self-closing (e.g. "<img />")
	public $selfClosingTags = array
	(
		'img',
		'br',
		'hr',
		'meta'
	);
	
	// Closing  with ">" or " />"
	public $selfClosingTagsStyle = 'xhtml';
	
	// tags which must always have seperate opening and closing tags (e.g. "<b></b>")
	public $openingAndClosingTags = array
	(
		'a',
		'b',
	);
	
	// attributes which should be checked for valid protocols
	public $attributesContainingProtocols = array
	(
		'src',
		'href',
	);
	
	// protocols which are allowed
	public $allowedProtocols = array
	(
		'http',
		'https',
		'ftp',
		'mailto',
	);
	
	// tags which should be removed if they contain no content (e.g. "<b></b>" or "<b />")
	public $removeTagsWithoutContent = array
	(
		'a',
		'b',
	);
	
	// should we remove comments?
	public $stripComments = true;
	
	// should we try and make a b tag out of "b>"
	public $alwaysMakeTags = true;
	
	// should we allow dec/hex entities within the input? if you set this to zero, '&#123;' will be converted to '&amp;#123;'
	public $allowNumberedEntities = true;
	
	// these non-numeric entities are allowed. non allowed entities will be converted from '&foo;' to '&amp;foo;'
	public $allowedEntities = array
	(
		'amp',
		'gt',
		'lt',
		'quot',
	);
	
	// should we convert dec/hex entities in the general doc (not inside protocol attribute) into raw characters? this is important if you're planning on running autolink on the output, to make it easier to filter out unwanted spam URLs. without it, an attacker could insert a working URL you'd otherwise be filtering (googl&#65;.com would avoid a string-matching spam filter, for instance). this only affects character codes below 128 (that is, the ASCII characters).
	
	// this setting overrides $allowNumberedEntities
	public $normalizeASCIIEntities = false;
		
	// Results of the regression testing
	public $regressionTestResults = array();
	
	
	public function allowNothing ()
	{
		$this->allowedTagsAndAttributes = array();
		$this->selfClosingTags = array();
		$this->selfClosingTagsStyle = 'html';
		$this->openingAndClosingTags = array();
		$this->attributesContainingProtocols = array();
		$this->allowedProtocols = array();
		$this->removeTagsWithoutContent = array();
		$this->stripComments = true;
		$this->alwaysMakeTags = true;
		$this->allowNumberedEntities = false;
		$this->allowedEntities = array();
		$this->normalizeASCIIEntities = false;
	}
	
	
	// this is the main entry point - pass your document to be filtered into here
	public function filter ($data)
	{
		$this->tagCounts = array();
		
		$data = $this->escapeComments($data);
		$data = $this->normalizeTagBrackets($data);
		$data = $this->normalizeTags($data);
		$data = $this->processRemoveBlanks($data);
		$data = $this->cleanUpNonTags($data);
		
		return $data;
	}
	
	// the first step is to make sure we don't have HTML inside the comments. comment are (optionally) stripped later on, but this ensures we don't waste time matching stuff inside them.
	public static function escapeComments ($data)
	{
		$data = XXX_String_Pattern::replace($data, '<!--(.*?)-->', 'se', '\'<!--\' . XXX_String_HTMLEntities::encode(XXX_HTML_Filter::stripSingle(\'$1\')) . \'-->\'');

		return $data;
	}
	
	// Standardize brackets, avoid double brackets, stray brackets etc	
	public function normalizeTagBrackets ($data)
	{
		if ($this->alwaysMakeTags)
		{
			// try and form html
			$data = XXX_String_Pattern::replace($data, '>>+', '', '>');
			$data = XXX_String_Pattern::replace($data, '<<+', '', '<');
			$data = XXX_String_Pattern::replace($data, '^>', '', '');
			$data = XXX_String_Pattern::replace($data, '<([^>]*?)(?=<|$)', '', '<$1>');
			$data = XXX_String_Pattern::replace($data, '(^|>)([^<]*?)(?=>)', '', '$1<$2');
		}
		else
		{
			// escape stray brackets
			$data = XXX_String_Pattern::replace($data, '<([^>]*?)(?=<|$)', '', '&lt;$1');
			$data = XXX_String_Pattern::replace($data, '(^|>)([^<]*?)(?=>)', '', '$1$2&gt;<');

			// the last regexp causes '<>' entities to appear (we need to do a lookahead assertion so that the last bracket can be used in the next pass of the regexp)
			$data = XXX_String_Pattern::replace($data, '<>', '', '');
		}
		
		return $data;
	}
		
	// Find all tags and pass the data inside the brackets to process_tag
	public function normalizeTags ($data)
	{
		$data = XXX_String_Pattern::replaceWithCallback($data, '<(.*?)>', 's', array($this, 'processTag'));

		foreach (array_keys($this->tagCounts) as $tag)
		{
			for ($i = 0; $i < $this->tagCounts[$tag]; ++$i)
			{
				$data .= '</' . $tag . '>';
			}
		}

		return $data;
	}
	
	// See if the tags are allowed and if so, if their attributes are as well
	public function processTag ($data)
	{
		$data = $this->stripSingle($data[1]);
		
		// ending tags
		$match = XXX_String_Pattern::getMatch($data, '^/([a-z0-9]+)', 'si');
		if ($match)
		{
			$name = XXX_String::convertToLowerCase($match[1]);
			
			if (XXX_Array::hasKey($this->allowedTagsAndAttributes, $name))
			{
				if (!XXX_Array::hasValue($this->selfClosingTags, $name))
				{
					if ($this->tagCounts[$name])
					{
						--$this->tagCounts[$name];
												
						return '</'.$name.'>';
					}
				}
			}
			else
			{
				return '';
			}
		}

		// starting tags
		$match = XXX_String_Pattern::getMatch($data, '^([a-z0-9]+)(.*?)(/?)$', 'si');
		if ($match)
		{
			$name = XXX_String::convertToLowerCase($match[1]);
			$body = $match[2];
			$ending = $match[3];
			
			if (XXX_Array::hasKey($this->allowedTagsAndAttributes, $name))
			{
				$attributes = '';
				$matches_2 = XXX_String_Pattern::getMatches($body, '([a-z0-9]+)=(["\'])(.*?)\2', 'si', false); // <foo a="b" />
				$matches_1 = XXX_String_Pattern::getMatches($body, '([a-z0-9]+)(=)([^"\s\']+)', 'si', false); // <foo a=b />
				$matches_3 = XXX_String_Pattern::getMatches($body, '([a-z0-9]+)=(["\'])([^"\']*?)\s*$', 'si', false); // <foo a="b />
				
				$matches = XXX_Array::merge($matches_1, XXX_Array::merge($matches_2, $matches_3));

				foreach ($matches as $match)
				{
					$attributeName = XXX_String::convertToLowerCase($match[1]);
					if (XXX_Array::hasValue($this->allowedTagsAndAttributes[$name], $attributeName))
					{
						$attributeValue = $match[3];
						if (XXX_Array::hasValue($this->attributesContainingProtocols, $attributeName))
						{
							$attributeValue = $this->processAttributeProtocol($attributeValue);
						}
						$attributes .= ' ' . $attributeName . '="' . $attributeValue . '"';
					
					}
				}
				
				if (XXX_Array::hasValue($this->selfClosingTags, $name))
				{
					$ending = ' /';
				}
				
				if (XXX_Array::hasValue($this->openingAndClosingTags, $name))
				{
					$ending = '';
				}
				
				if (!$ending)
				{
					if (XXX_Type::isValue($this->tagCounts[$name]))
					{
						++$this->tagCounts[$name];
					}
					else
					{
						$this->tagCounts[$name] = 1;
					}
				}
				
				if ($ending)
				{
					if ($this->selfClosingTagsStyle == 'xhtml')
					{
						$ending = ' /';
					}
					else
					{
						$ending = '';
					}
				}
								
				return '<' . $name . $attributes . $ending . '>';
			}
			else
			{
				return '';
			}
		}

		// comments
		if (XXX_String_Pattern::hasMatch($data, '^!--(.*)--$', 'si'))
		{
			if ($this->stripComments)
			{
				return '';
			}
			else
			{
				return '<' . $data . '>';
			}
		}


		// garbage, ignore it
		return '';
	}
	
	// See if the protocol is allowed
	public function processAttributeProtocol ($data)
	{
		$data = $this->validateEntities($data, 1);
		
		$match = XXX_String_Pattern::getMatch($data, '^([^:]+)\:', 'si');
		
		if ($match)
		{
			if (!XXX_Array::hasValue($this->allowedProtocols, $match[1]))
			{
				$data = '#' . XXX_String::getPart($data, XXX_String::getCharacterLength($match[1]) + 1);
			}
		}

		return $data;
	}
	
	// this function removes certain tag pairs if they have no content. for instance, 'foo<b></b>bar' is converted to 'foobar'.
	public function processRemoveBlanks ($data)
	{
		foreach ($this->removeTagsWithoutContent as $tag)
		{
			$data = XXX_String_Pattern::replace($data, '<' . $tag . '(\s[^>]*)?></' . $tag . '>', '', '');
			$data = XXX_String_Pattern::replace($data, '<' . $tag . '(\s[^>]*)?/>', '', '');
		}
		
		return $data;
	}
	
	// given some HTML input, find out if the non-HTML part is too shouty. that is, does it solely consist of capital letters. if so, make it less shouty.
	public function fixCase ($data)
	{
		// extract only the (latin) letters in the string
		$data_notags = XXX_String::stripHTMLTags($data);
		$data_notags = XXX_String_Pattern::replace($data_notags, '[^a-zA-Z]', '', '');

		// if there are less than 5, just allow it as-is
		if (XXX_String::getCharacterLength($data_notags) < 5)
		{
			return $data;
		}
		
		// if there are lowercase letters somewhere, allow it as-is
		if (XXX_String_Pattern::hasMatch($data_notags, '[a-z]', ''))
		{
			return $data;
		}

		// we have more than 5 letters and they're all capitals. we want to case-normalize.
		return XXX_String_Pattern::replaceWithCallback($data, '(>|^)([^<]+?)(<|$)', 's', array($this, 'fixCaseInner'));
	}
	
	// given a block of non-HTML, filter it for shoutyness by lowercasing the whole thing and then capitalizing the first letter of each 'sentance'.
	public static function fixCaseInner ($m)
	{
		$data = XXX_String::convertToLowerCase($m[2]);

		$data = XXX_String_Pattern::replaceWithCallback($data, '(^|[^\w\s\';,\\-])(\s*)([a-z])', '', create_function('$m', 'return $m[1] . $m[2] . XXX_String::convertToUpperCase($m[3]);'));

		return $m[1] . $data . $m[3];
	}
	
	// this function is called in two places - inside of each href-like attributes and then on the whole document. it's job is to make sure that anything that looks like an entity (starts with an ampersand) is allowed, else corrects it.
	public function validateEntities ($data, $inAttribute)
	{
		// turn ascii characters into their actual characters, if requested. we need to always do this inside URLs to avoid people using entities or URL escapes to insert 'javascript:' or something like that. outside of attributes, we optionally filter entities to stop people from inserting text that they shouldn't (since it might make it into a clickable URL via lib_autolink).
		if ($inAttribute || $this->normalizeASCIIEntities)
		{
			$data = $this->decodeEntities($data, $inAttribute);
		}

		// find every remaining ampersand in the string and check if it looks like it's an entity (then validate it) or if it's not (then escape it).
		$data = XXX_String_Pattern::replaceWithCallback($data, '&([^&;]*)(?=(;|&|$))', '', array($this, 'checkEntity'));

		return $data;
	}
	
	// this function comes last in processing, to clean up data outside of tags.
	public function cleanUpNonTags ($data)
	{
		return XXX_String_Pattern::replaceWithCallback($data, '(>|^)([^<]+?)(<|$)' , 's', array($this, 'cleanUpNonTagsInner'));			
	}

	public function cleanUpNonTagsInner ($m)
	{
		// first, deal with the entities
		$m[2] = $this->validateEntities($m[2], 0);

		// find any literal quotes outside of tags and replace them with &quot;. we call it last thing before returning.
		$m[2] = XXX_String::replace($m[2], '"', '&quot;');

		return $m[1] . $m[2] . $m[3];
	}
	
	// this function gets passed the 'inside' and 'end' of a suspected entity. the ampersand is not included, but must be part of the return value. $term is a look-ahead assertion, so don't return it.
	public function checkEntity ($data)
	{
		$preamble = $this->stripSingle($data[1]);
		$term = $this->stripSingle($data[2]);
		
		// if the terminating character is not a semi-colon, treat this as a non-entity
		if ($term != ';')
		{
			return '&amp;' . $preamble;
		}
		
		// if it's an allowed entity, go for it
		if ($this->isValidEntity($preamble))
		{
			return '&' . $preamble;
		}

		// not an allowed antity, so escape the ampersand
		return '&amp;' . $preamble;
	}
	
	// this function determines whether the body of an entity (the stuff between '&' and ';') is valid.
	public function isValidEntity ($entity)
	{
		// numeric entity. over 127 is always allowed, else it's a pref
		$patternMatch = XXX_String_Pattern::getMatch($entity, '^#([0-9]+)$', 'i');
		
		if ($patternMatch)
		{
			return ($patternMatch[1] > 127) ? true : $this->allowNumberedEntities;
		}

		// hex entity. over 127 is always allowed, else it's a pref
		$patternMatch = XXX_String_Pattern::getMatch($entity, '^#x([0-9a-f]+)$', 'i');
		
		if ($patternMatch)
		{
			return (XXX_Number::convertBase($patternMatch[1], 16, 10) > 127) ? true : $this->allowNumberedEntities;
		}

		if (XXX_Array::hasValue($this->allowedEntities, $entity))
		{
			return true;
		}

		return false;
	}
	
	// within attributes, we want to convert all hex/dec/url escape sequences into their raw characters so that we can check we don't get stray quotes/brackets inside strings. within general text, we decode hex/dec entities.
	public function decodeEntities ($data, $inAttribute = true)
	{
		$data = XXX_String_Pattern::replaceWithCallback($data, '(&)#(\d+);?', '', array($this, 'decodeDecimalEntity'));
		$data = XXX_String_Pattern::replaceWithCallback($data, '(&)#x([0-9a-f]+);?', 'i', array($this, 'decodeHexadecimalEntity'));

		if ($inAttribute)
		{
			$data = XXX_String_Pattern::replaceWithCallback($data, '(%)([0-9a-f]{2});?' , 'i', array($this, 'decodeHexadecimalEntity'));
		}

		return $data;
	}

	public function decodeHexadecimalEntity ($m)
	{
		return $this->decodeNumberedEntity($m[1], XXX_Number::convertBase($m[2], 16, 10));
	}

	public function decodeDecimalEntity ($m)
	{
		return $this->decodeNumberedEntity($m[1], XXX_Type::makeInteger($m[2]));
	}
	
	// given a character code and the starting escape character (either '%' or '&'), return either a hex entity (if the character code is non-ascii), or a raw character. remeber to escape XML characters!
	public static function decodeNumberedEntity ($originalType, $d)
	{
		// treat control characters as spaces
		if ($d < 0)
		{
			$d = 32;
		} 

		// don't mess with high characters - what to replace them with is character-set independant, so we leave them as entities. besides, you can't use them to pass 'javascript:' etc (at present)
		if ($d > 127)
		{
			if ($originalType == '%')
			{
				return '%' . XXX_Number::convertBase($d, 10, 16);
			}
			
			if ($originalType == '&')
			{
				return '&#' . $d . ';';
			}
		}

		// we want to convert this escape sequence into a real character. we call HtmlSpecialChars() incase it's one of [<>"&]
		return XXX_String_HTMLEntities::encode(XXX_String::asciiCodePointToCharacter($d));
	}
	
	public static function stripSingle ($data)
	{
		return XXX_String::replace($data, array('\\"', "\\0"), array('"', chr(0)));
	}
	
	public function filterTest ($input, $expectedOutput)
	{
		$output = $this->filter($input);
		
		XXX_Test::test('XXX_HTML_Filter', 'filter', $input, $output, $expectedOutput);
	}
	
	public function caseTest ($input, $expectedOutput)
	{
		$output = $this->fixCase($input);
		
		XXX_Test::test('XXX_HTML_Filter', 'case', $input, $output, $expectedOutput);
	}
	
	public function entityTest ($input, $expectedOutput)
	{
		$output = $this->decodeEntities($input);
		
		XXX_Test::test('XXX_HTML_Filter', 'entity', $input, $output, $expectedOutput);
	}
}

?>