<?php

require_once 'XXX_Client_Input.php';
require_once 'XXX_Client_Output.php';
require_once 'XXX_OperatingSystem_Client_Input.php';
require_once 'XXX_CommandLine_Client_Input.php';
require_once 'XXX_CommandLine_Client_Output.php';
require_once 'XXX_HTTPServer_Client_Input.php';
require_once 'XXX_HTTPServer_Client_Output.php';
require_once 'XXX_HTML_Filter.php';

XXX_Path_Local::addDefaultIncludePathsForProjectSource('XXX_ClientIOHelpers_PHP');
XXX_I18n_Translation::loadTranslation();

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
				/*
				Google Page Speed Insight
				
				use any other extension, just not '.gz' ('.jgz', '.foo' or any other one)
				set the gzip-encoded header for your chosen extensions ('Content-encoding: gzip')
				set the appropriate MIME type: text/javascript or text/css
				
				After some digging around I learned that you cannot send compressed javascripts to Safari with the extension of “gz”. It must be “jgz”
				
				Mobile: Mozilla/5.0 (iPhone; CPU iPhone OS 6_0_1 like Mac OS X) AppleWebKit/537.36 (KHTML, like Gecko; Google Page Speed Insights) Version/6.0 Mobile/10A525 Safari/8536.25
				
				Desktop: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko; Google Page Speed Insights) Chrome/27.0.1453 Safari/537.36
				*/
				
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