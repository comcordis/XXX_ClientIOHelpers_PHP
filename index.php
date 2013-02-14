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
				// For cookies, redirects etc.
				XXX_Client_Output::startRootBuffer();
				break;
		}
		
		XXX::addEventListener('beforeExecutionExit', 'XXX_HTTPServer_Client_Output::flushHeaders');
		break;
}

?>