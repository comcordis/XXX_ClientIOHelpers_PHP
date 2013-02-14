<?php

// TODO test strings from: http://ha.ckers.org/xss.html
		
		$XXX_HTML_Filter = new XXX_HTML_Filter();

		$testResults = array();
				
		// basics
		$testResults[] = $XXX_HTML_Filter->filterTest("","");
		$testResults[] = $XXX_HTML_Filter->filterTest("hello","hello");
	
		// balancing tags
		$testResults[] = $XXX_HTML_Filter->filterTest("<b>hello","<b>hello</b>");
		$testResults[] = $XXX_HTML_Filter->filterTest("hello<b>","hello");
		$testResults[] = $XXX_HTML_Filter->filterTest("hello<b>world","hello<b>world</b>");
		$testResults[] = $XXX_HTML_Filter->filterTest("hello</b>","hello");
		$testResults[] = $XXX_HTML_Filter->filterTest("hello<b/>","hello");
		$testResults[] = $XXX_HTML_Filter->filterTest("hello<b/>world","hello<b>world</b>");
		$testResults[] = $XXX_HTML_Filter->filterTest("<b><b><b>hello","<b><b><b>hello</b></b></b>");
		$testResults[] = $XXX_HTML_Filter->filterTest("</b><b>","");
	
		// end slashes
		$testResults[] = $XXX_HTML_Filter->filterTest('<img>','<img />');
		$testResults[] = $XXX_HTML_Filter->filterTest('<img/>','<img />');
		$testResults[] = $XXX_HTML_Filter->filterTest('<b/></b>','');
	
		// balancing angle brakets
		$XXX_HTML_Filter->alwaysMakeTags = true;
		$testResults[] = $XXX_HTML_Filter->filterTest('<img src="foo"','<img src="foo" />');
		$testResults[] = $XXX_HTML_Filter->filterTest('b>','');
		$testResults[] = $XXX_HTML_Filter->filterTest('b>hello','<b>hello</b>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<img src="foo"/','<img src="foo" />');
		$testResults[] = $XXX_HTML_Filter->filterTest('>','');
		$testResults[] = $XXX_HTML_Filter->filterTest('hello<b','hello');
		$testResults[] = $XXX_HTML_Filter->filterTest('b>foo','<b>foo</b>');
		$testResults[] = $XXX_HTML_Filter->filterTest('><b','');
		$testResults[] = $XXX_HTML_Filter->filterTest('b><','');
		$testResults[] = $XXX_HTML_Filter->filterTest('><b>','');
		$testResults[] = $XXX_HTML_Filter->filterTest('foo bar>','');
		$testResults[] = $XXX_HTML_Filter->filterTest('foo>bar>baz','baz');
		$testResults[] = $XXX_HTML_Filter->filterTest('foo>bar','bar');
		$testResults[] = $XXX_HTML_Filter->filterTest('foo>bar>','');
		$testResults[] = $XXX_HTML_Filter->filterTest('>foo>bar','bar');
		$testResults[] = $XXX_HTML_Filter->filterTest('>foo>bar>','');
	
		$XXX_HTML_Filter->alwaysMakeTags = false;
		$testResults[] = $XXX_HTML_Filter->filterTest('<img src="foo"','&lt;img src=&quot;foo&quot;');
		$testResults[] = $XXX_HTML_Filter->filterTest('b>','b&gt;');
		$testResults[] = $XXX_HTML_Filter->filterTest('b>hello','b&gt;hello');
		$testResults[] = $XXX_HTML_Filter->filterTest('<img src="foo"/','&lt;img src=&quot;foo&quot;/');
		$testResults[] = $XXX_HTML_Filter->filterTest('>','&gt;');
		$testResults[] = $XXX_HTML_Filter->filterTest('hello<b','hello&lt;b');
		$testResults[] = $XXX_HTML_Filter->filterTest('b>foo','b&gt;foo');
		$testResults[] = $XXX_HTML_Filter->filterTest('><b','&gt;&lt;b');
		$testResults[] = $XXX_HTML_Filter->filterTest('b><','b&gt;&lt;');
		$testResults[] = $XXX_HTML_Filter->filterTest('><b>','&gt;');
		$testResults[] = $XXX_HTML_Filter->filterTest('foo bar>','foo bar&gt;');
		$testResults[] = $XXX_HTML_Filter->filterTest('foo>bar>baz','foo&gt;bar&gt;baz');
		$testResults[] = $XXX_HTML_Filter->filterTest('foo>bar','foo&gt;bar');
		$testResults[] = $XXX_HTML_Filter->filterTest('foo>bar>','foo&gt;bar&gt;');
		$testResults[] = $XXX_HTML_Filter->filterTest('>foo>bar','&gt;foo&gt;bar');
		$testResults[] = $XXX_HTML_Filter->filterTest('>foo>bar>','&gt;foo&gt;bar&gt;');
			
		// attributes
		$testResults[] = $XXX_HTML_Filter->filterTest('<img src=foo>','<img src="foo" />');
		$testResults[] = $XXX_HTML_Filter->filterTest('<img asrc=foo>','<img />');
		$testResults[] = $XXX_HTML_Filter->filterTest('<img src=test test>','<img src="test" />');
	
		// non-allowed tags
		$testResults[] = $XXX_HTML_Filter->filterTest('<script>','');
		$testResults[] = $XXX_HTML_Filter->filterTest('<script/>','');
		$testResults[] = $XXX_HTML_Filter->filterTest('</script>','');
		$testResults[] = $XXX_HTML_Filter->filterTest('<script woo=yay>','');
		$testResults[] = $XXX_HTML_Filter->filterTest('<script woo="yay">','');
		$testResults[] = $XXX_HTML_Filter->filterTest('<script woo="yay>','');
	
		$XXX_HTML_Filter->alwaysMakeTags = true;
		$testResults[] = $XXX_HTML_Filter->filterTest('<script','');
		$testResults[] = $XXX_HTML_Filter->filterTest('<script woo="yay<b>','');
		$testResults[] = $XXX_HTML_Filter->filterTest('<script woo="yay<b>hello','<b>hello</b>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<script<script>>','');
		$testResults[] = $XXX_HTML_Filter->filterTest('<<script>script<script>>','script');
		$testResults[] = $XXX_HTML_Filter->filterTest('<<script><script>>','');
		$testResults[] = $XXX_HTML_Filter->filterTest('<<script>script>>','');
		$testResults[] = $XXX_HTML_Filter->filterTest('<<script<script>>','');
	
		$XXX_HTML_Filter->alwaysMakeTags = false;
		$testResults[] = $XXX_HTML_Filter->filterTest('<script','&lt;script');
		$testResults[] = $XXX_HTML_Filter->filterTest('<script woo="yay<b>','&lt;script woo=&quot;yay');
		$testResults[] = $XXX_HTML_Filter->filterTest('<script woo="yay<b>hello','&lt;script woo=&quot;yay<b>hello</b>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<script<script>>','&lt;script&gt;');
		$testResults[] = $XXX_HTML_Filter->filterTest('<<script>script<script>>','&lt;script&gt;');
		$testResults[] = $XXX_HTML_Filter->filterTest('<<script><script>>','&lt;&gt;');
		$testResults[] = $XXX_HTML_Filter->filterTest('<<script>script>>','&lt;script&gt;&gt;');
		$testResults[] = $XXX_HTML_Filter->filterTest('<<script<script>>','&lt;&lt;script&gt;');
			
		// bad protocols
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="http://foo">bar</a>', '<a href="http://foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="ftp://foo">bar</a>', '<a href="ftp://foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="mailto:foo">bar</a>', '<a href="mailto:foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="javascript:foo">bar</a>', '<a href="#foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="java script:foo">bar</a>', '<a href="#foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="java'."\t".'script:foo">bar</a>', '<a href="#foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="java'."\n".'script:foo">bar</a>', '<a href="#foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="java'."\r".'script:foo">bar</a>', '<a href="#foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="java'.chr(1).'script:foo">bar</a>', '<a href="#foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="java'.chr(0).'script:foo">bar</a>', '<a href="#foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="jscript:foo">bar</a>', '<a href="#foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="vbscript:foo">bar</a>', '<a href="#foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="view-source:foo">bar</a>', '<a href="#foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="  javascript:foo">bar</a>', '<a href="#foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="jAvAsCrIpT:foo">bar</a>', '<a href="#foo">bar</a>');
	
		// bad protocols with entities (semicolons)
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="javascript:foo">bar</a>', '<a href="#foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="javascript:foo">bar</a>', '<a href="#foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="javascript:foo">bar</a>', '<a href="#foo">bar</a>');
	
		// bad protocols with entities (no semicolons)
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="&#106&#97&#118&#97&#115&#99&#114&#105&#112&#116:foo">bar</a>', '<a href="#foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058foo">bar</a>', '<a href="#foo">bar</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74:foo">bar</a>', '<a href="#foo">bar</a>');
	
		// self-closing tags
		$testResults[] = $XXX_HTML_Filter->filterTest('<img src="a">', '<img src="a" />');
		$testResults[] = $XXX_HTML_Filter->filterTest('<img src="a">foo</img>', '<img src="a" />foo');
		$testResults[] = $XXX_HTML_Filter->filterTest('</img>', '');
	
		// typos
		$testResults[] = $XXX_HTML_Filter->filterTest('<b>test<b/>', '<b>test</b>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<b/>test<b/>', '<b>test</b>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<b/>test', '<b>test</b>');
	
		// case conversion
		$testResults[] = $XXX_HTML_Filter->caseTest('hello world', 'hello world');
		$testResults[] = $XXX_HTML_Filter->caseTest('Hello world', 'Hello world');
		$testResults[] = $XXX_HTML_Filter->caseTest('Hello World', 'Hello World');
		$testResults[] = $XXX_HTML_Filter->caseTest('HELLO World', 'HELLO World');
		$testResults[] = $XXX_HTML_Filter->caseTest('HELLO WORLD', 'Hello world');
		$testResults[] = $XXX_HTML_Filter->caseTest('<b>HELLO WORLD', '<b>Hello world');
		$testResults[] = $XXX_HTML_Filter->caseTest('<B>HELLO WORLD', '<B>Hello world');
		$testResults[] = $XXX_HTML_Filter->caseTest('HELLO. WORLD', 'Hello. World');
		$testResults[] = $XXX_HTML_Filter->caseTest('HELLO<b> WORLD', 'Hello<b> World');
		$testResults[] = $XXX_HTML_Filter->caseTest("DOESN'T", "Doesn't");
		$testResults[] = $XXX_HTML_Filter->caseTest("COMMA, TEST", 'Comma, test');
		$testResults[] = $XXX_HTML_Filter->caseTest("SEMICOLON; TEST", 'Semicolon; test');
		$testResults[] = $XXX_HTML_Filter->caseTest("DASH - TEST", 'Dash - test');
	
		// comments
		$XXX_HTML_Filter->stripComments = false;
		$testResults[] = $XXX_HTML_Filter->filterTest('hello <!-- foo --> world', 'hello <!-- foo --> world');
		$testResults[] = $XXX_HTML_Filter->filterTest('hello <!-- <foo --> world', 'hello <!-- &lt;foo --> world');
		$testResults[] = $XXX_HTML_Filter->filterTest('hello <!-- foo> --> world', 'hello <!-- foo&gt; --> world');
		$testResults[] = $XXX_HTML_Filter->filterTest('hello <!-- <foo> --> world', 'hello <!-- &lt;foo&gt; --> world');
	
		$XXX_HTML_Filter->stripComments = true;
		$testResults[] = $XXX_HTML_Filter->filterTest('hello <!-- foo --> world', 'hello  world');
		$testResults[] = $XXX_HTML_Filter->filterTest('hello <!-- <foo --> world', 'hello  world');
		$testResults[] = $XXX_HTML_Filter->filterTest('hello <!-- foo> --> world', 'hello  world');
		$testResults[] = $XXX_HTML_Filter->filterTest('hello <!-- <foo> --> world', 'hello  world');
	
		// br - shouldn't get caught by the empty 'b' tag remover
		$XXX_HTML_Filter->allowedTagsAndAttributes['br'] = array();
		$XXX_HTML_Filter->selfClosingTags[] = 'br';
		$testResults[] = $XXX_HTML_Filter->filterTest('foo<br>bar', 'foo<br />bar');
		$testResults[] = $XXX_HTML_Filter->filterTest('foo<br />bar', 'foo<br />bar');
	
		// stray quotes
		$testResults[] = $XXX_HTML_Filter->filterTest('foo"bar', 'foo&quot;bar');
		$testResults[] = $XXX_HTML_Filter->filterTest('foo"', 'foo&quot;');
		$testResults[] = $XXX_HTML_Filter->filterTest('"bar', '&quot;bar');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="foo"bar">baz</a>', '<a href="foo">baz</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href=foo"bar>baz</a>', '<a href="foo">baz</a>');
	
		// correct entities should not be touched
		$testResults[] = $XXX_HTML_Filter->filterTest('foo&amp;bar', 'foo&amp;bar');
		$testResults[] = $XXX_HTML_Filter->filterTest('foo&quot;bar', 'foo&quot;bar');
		$testResults[] = $XXX_HTML_Filter->filterTest('foo&lt;bar', 'foo&lt;bar');
		$testResults[] = $XXX_HTML_Filter->filterTest('foo&gt;bar', 'foo&gt;bar');
	
		// bare ampersands should be fixed up
		$testResults[] = $XXX_HTML_Filter->filterTest('foo&bar', 'foo&amp;bar');
		$testResults[] = $XXX_HTML_Filter->filterTest('foo&', 'foo&amp;');
	
		// numbered entities
		$XXX_HTML_Filter->allowNumberedEntities = true;
		$testResults[] = $XXX_HTML_Filter->filterTest('foo{bar', 'foo{bar');
		$testResults[] = $XXX_HTML_Filter->filterTest('{bar', '{bar');
		$testResults[] = $XXX_HTML_Filter->filterTest('foo{', 'foo{');
	
		$XXX_HTML_Filter->allowNumberedEntities = false;
		$testResults[] = $XXX_HTML_Filter->filterTest('foo{bar', 'foo&amp;#123;bar');
		$testResults[] = $XXX_HTML_Filter->filterTest('{bar', '&amp;#123;bar');
		$testResults[] = $XXX_HTML_Filter->filterTest('foo{', 'foo&amp;#123;');
	
		// other entities
		$testResults[] = $XXX_HTML_Filter->filterTest('foo&bar;baz', 'foo&amp;bar;baz');	
		$XXX_HTML_Filter->allowedEntities[] = 'bar';
		$testResults[] = $XXX_HTML_Filter->filterTest('foo&bar;baz', 'foo&bar;baz');
	
		// entity decoder - '<'
		$entities = explode(' ', "%3c %3C &#60 &#0000060 < < &#x3c &#x000003c < < &#X3c &#X000003c < < &#x3C &#x000003C < < &#X3C &#X000003C < <");
		
		foreach ($entities as $entity)
		{
			 $testResults[] = $XXX_HTML_Filter->entityTest($entity, '&lt;');
		}
	
		$testResults[] = $XXX_HTML_Filter->entityTest('%3cĀĀ', '&lt;ĀĀ');
		$testResults[] = $XXX_HTML_Filter->entityTest('%3cúú', '&lt;úú');
		$testResults[] = $XXX_HTML_Filter->entityTest('%3c%40%aa;', '&lt;@%aa');
	
		// character checks
		$testResults[] = $XXX_HTML_Filter->filterTest('\\', '\\');
		$testResults[] = $XXX_HTML_Filter->filterTest('/', '/');
		$testResults[] = $XXX_HTML_Filter->filterTest("'", "'");
		$testResults[] = $XXX_HTML_Filter->filterTest('a'.chr(0).'b', 'a'.chr(0).'b');
		$testResults[] = $XXX_HTML_Filter->filterTest('\\/\'!@#', '\\/\'!@#');
		$testResults[] = $XXX_HTML_Filter->filterTest('$foo', '$foo');
	
		// this test doesn't contain &"<> since they get changed
		$allCharacters = ' !#$%\'()*+,-./0123456789:;=?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
		$testResults[] = $XXX_HTML_Filter->filterTest($allCharacters, $allCharacters);
	
		// single quoted entities
		$testResults[] = $XXX_HTML_Filter->filterTest("<img src=foo.jpg />", '<img src="foo.jpg" />');
		$testResults[] = $XXX_HTML_Filter->filterTest("<img src='foo.jpg' />", '<img src="foo.jpg" />');
		$testResults[] = $XXX_HTML_Filter->filterTest("<img src=\"foo.jpg\" />", '<img src="foo.jpg" />');
	
		// unbalanced quoted entities
		$testResults[] = $XXX_HTML_Filter->filterTest("<img src=\"foo.jpg />", '<img src="foo.jpg" />');
		$testResults[] = $XXX_HTML_Filter->filterTest("<img src='foo.jpg />", '<img src="foo.jpg" />');
		$testResults[] = $XXX_HTML_Filter->filterTest("<img src=foo.jpg\" />", '<img src="foo.jpg" />');
		$testResults[] = $XXX_HTML_Filter->filterTest("<img src=foo.jpg' />", '<img src="foo.jpg" />');
	
		// url escape sequences
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="woo.htm%22%20bar=%22#">foo</a>', '<a href="woo.htm&quot; bar=&quot;#">foo</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="woo.htm%22%3E%3C/a%3E%3Cscript%3E%3C/script%3E%3Ca%20href=%22#">foo</a>', '<a href="woo.htm&quot;&gt;&lt;/a&gt;&lt;script&gt;&lt;/script&gt;&lt;a href=&quot;#">foo</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="woo.htm%aa">foo</a>', '<a href="woo.htm%aa">foo</a>');
	
	
		// this set of tests shows the differences between the different combinations of entity options	
		$XXX_HTML_Filter->allowNumberedEntities = false;
		$XXX_HTML_Filter->normalizeASCIIEntities = false;
	
		$testResults[] = $XXX_HTML_Filter->filterTest(';', '&amp;#x3b;');
		$testResults[] = $XXX_HTML_Filter->filterTest(';', '&amp;#x3B;');
		$testResults[] = $XXX_HTML_Filter->filterTest(';', '&amp;#59;');
		$testResults[] = $XXX_HTML_Filter->filterTest('%3B', '%3B');
		$testResults[] = $XXX_HTML_Filter->filterTest('&', '&amp;#x26;');
		$testResults[] = $XXX_HTML_Filter->filterTest('&', '&amp;#38;');
		$testResults[] = $XXX_HTML_Filter->filterTest('Ì', 'Ì');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
			
		$XXX_HTML_Filter->allowNumberedEntities = true;
		$XXX_HTML_Filter->normalizeASCIIEntities = false;
	
		$testResults[] = $XXX_HTML_Filter->filterTest(';', ';');
		$testResults[] = $XXX_HTML_Filter->filterTest(';', ';');
		$testResults[] = $XXX_HTML_Filter->filterTest(';', ';');
		$testResults[] = $XXX_HTML_Filter->filterTest('%3B', '%3B');
		$testResults[] = $XXX_HTML_Filter->filterTest('&', '&');
		$testResults[] = $XXX_HTML_Filter->filterTest('&', '&');
		$testResults[] = $XXX_HTML_Filter->filterTest('Ì', 'Ì');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
		$testResults[] = $XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
		
		for ($i = 0; $i <= 1; ++$i)
		{
			$XXX_HTML_Filter->allowNumberedEntities = $i ? true : false;
			$XXX_HTML_Filter->normalizeASCIIEntities = true;
			
			$testResults[] = $XXX_HTML_Filter->filterTest(';', ';');
			$testResults[] = $XXX_HTML_Filter->filterTest(';', ';');
			$testResults[] = $XXX_HTML_Filter->filterTest(';', ';');
			$testResults[] = $XXX_HTML_Filter->filterTest('%3B', '%3B');
			$testResults[] = $XXX_HTML_Filter->filterTest('&', '&amp;');
			$testResults[] = $XXX_HTML_Filter->filterTest('&', '&amp;');
			$testResults[] = $XXX_HTML_Filter->filterTest('Ì', 'Ì');
			$testResults[] = $XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
			$testResults[] = $XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
			$testResults[] = $XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
		}
		
		// Determine results
		$failed = 0;
		$passed = 0;
		
		foreach ($testResults as $testResult)
		{
			if ($testResult['passed'])
			{
				++$passed;	
			}
			else
			{
				++$failed;
			}
		}
		
		$passedAll = $failed == 0;
		
		$result = array
		(
			'passedAll' => $passedAll,
			'testResults'  => $testResults
		);
		
		echo '<pre>';
		print_r($result);
		echo '</pre>';

?>