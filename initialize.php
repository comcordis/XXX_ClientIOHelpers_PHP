<?php

require_once 'XXX_Client_Input.php';
require_once 'XXX_Client_Output.php';
require_once 'XXX_CommandLine_Client_Input.php';
require_once 'XXX_CommandLine_Client_Output.php';
require_once 'XXX_HTTPServer_Client_Input.php';
require_once 'XXX_HTTPServer_Client_Output.php';
require_once 'XXX_HTML_Filter.php';

XXX_Client_Input::initialize();
				
switch (XXX_PHP::$executionEnvironment)
{
	case 'commandLine':
		XXX_CommandLine_Client_Input::initialize();
		break;
	case 'httpServer':
		XXX_HTTPServer_Client_Input::initialize();
				
		switch (XXX_HTTPServer::$parsedHost['subExecutionEnvironment'])
		{
			case 'server':
			case 'www':
				XXX_HTTPServer_Client_Output::$compressOutput = function_exists('gzencode') && (XXX_HTTPServer_Client::$outputEncoding['gzip'] || XXX_HTTPServer_Client::$outputEncoding['deflate']);
				
				// http://www.beetlebrow.co.uk/what-do-you-need/help-and-documentation/unix-tricks-and-information/safari-gzip-deflation-and-blank-pages
				if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'safari') !== false && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'google page speed insights') === false)
				{
					XXX_HTTPServer_Client_Output::$compressOutput = false;
				}
				
				// For cookies, redirects etc.
				XXX_Client_Output::startRootBuffer();
				break;
		}
		
		XXX::addEventListener('beforeExecutionExit', 'XXX_HTTPServer_Client_Output::flushHeaders');
		break;
}

?>