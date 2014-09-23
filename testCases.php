<?php

// File uploads

XXX_Type::peakAtVariable(XXX_HTTPServer::$inputLimits);
		
XXX_Type::peakAtVariable($_FILES);
XXX_Type::peakAtVariable(XXX_HTTPServer_Client_Input::getFileUploads());
		
?>
<html>
	<head>
	
	</head>
	<body>
		<form action="" method="POST" enctype="multipart/form-data" autocomplete="off">
			<input type="file" name="Filedata" id="Filedata">
			<button type="submit" name="testFileUpload" value="true">Upload</button>
		</form>
	</body>
</html>
<?php

// TODO test strings from: http://ha.ckers.org/xss.html
		
		$XXX_HTML_Filter = new XXX_HTML_Filter();
				
		// basics
		$XXX_HTML_Filter->filterTest("","");
		$XXX_HTML_Filter->filterTest("hello","hello");
	
		// balancing tags
		$XXX_HTML_Filter->filterTest("<b>hello","<b>hello</b>");
		$XXX_HTML_Filter->filterTest("hello<b>","hello");
		$XXX_HTML_Filter->filterTest("hello<b>world","hello<b>world</b>");
		$XXX_HTML_Filter->filterTest("hello</b>","hello");
		$XXX_HTML_Filter->filterTest("hello<b/>","hello");
		$XXX_HTML_Filter->filterTest("hello<b/>world","hello<b>world</b>");
		$XXX_HTML_Filter->filterTest("<b><b><b>hello","<b><b><b>hello</b></b></b>");
		$XXX_HTML_Filter->filterTest("</b><b>","");
	
		// end slashes
		$XXX_HTML_Filter->filterTest('<img>','<img />');
		$XXX_HTML_Filter->filterTest('<img/>','<img />');
		$XXX_HTML_Filter->filterTest('<b/></b>','');
	
		// balancing angle brakets
		$XXX_HTML_Filter->alwaysMakeTags = true;
		$XXX_HTML_Filter->filterTest('<img src="foo"','<img src="foo" />');
		$XXX_HTML_Filter->filterTest('b>','');
		$XXX_HTML_Filter->filterTest('b>hello','<b>hello</b>');
		$XXX_HTML_Filter->filterTest('<img src="foo"/','<img src="foo" />');
		$XXX_HTML_Filter->filterTest('>','');
		$XXX_HTML_Filter->filterTest('hello<b','hello');
		$XXX_HTML_Filter->filterTest('b>foo','<b>foo</b>');
		$XXX_HTML_Filter->filterTest('><b','');
		$XXX_HTML_Filter->filterTest('b><','');
		$XXX_HTML_Filter->filterTest('><b>','');
		$XXX_HTML_Filter->filterTest('foo bar>','');
		$XXX_HTML_Filter->filterTest('foo>bar>baz','baz');
		$XXX_HTML_Filter->filterTest('foo>bar','bar');
		$XXX_HTML_Filter->filterTest('foo>bar>','');
		$XXX_HTML_Filter->filterTest('>foo>bar','bar');
		$XXX_HTML_Filter->filterTest('>foo>bar>','');
	
		$XXX_HTML_Filter->alwaysMakeTags = false;
		$XXX_HTML_Filter->filterTest('<img src="foo"','&lt;img src=&quot;foo&quot;');
		$XXX_HTML_Filter->filterTest('b>','b&gt;');
		$XXX_HTML_Filter->filterTest('b>hello','b&gt;hello');
		$XXX_HTML_Filter->filterTest('<img src="foo"/','&lt;img src=&quot;foo&quot;/');
		$XXX_HTML_Filter->filterTest('>','&gt;');
		$XXX_HTML_Filter->filterTest('hello<b','hello&lt;b');
		$XXX_HTML_Filter->filterTest('b>foo','b&gt;foo');
		$XXX_HTML_Filter->filterTest('><b','&gt;&lt;b');
		$XXX_HTML_Filter->filterTest('b><','b&gt;&lt;');
		$XXX_HTML_Filter->filterTest('><b>','&gt;');
		$XXX_HTML_Filter->filterTest('foo bar>','foo bar&gt;');
		$XXX_HTML_Filter->filterTest('foo>bar>baz','foo&gt;bar&gt;baz');
		$XXX_HTML_Filter->filterTest('foo>bar','foo&gt;bar');
		$XXX_HTML_Filter->filterTest('foo>bar>','foo&gt;bar&gt;');
		$XXX_HTML_Filter->filterTest('>foo>bar','&gt;foo&gt;bar');
		$XXX_HTML_Filter->filterTest('>foo>bar>','&gt;foo&gt;bar&gt;');
			
		// attributes
		$XXX_HTML_Filter->filterTest('<img src=foo>','<img src="foo" />');
		$XXX_HTML_Filter->filterTest('<img asrc=foo>','<img />');
		$XXX_HTML_Filter->filterTest('<img src=test test>','<img src="test" />');
	
		// non-allowed tags
		$XXX_HTML_Filter->filterTest('<script>','');
		$XXX_HTML_Filter->filterTest('<script/>','');
		$XXX_HTML_Filter->filterTest('</script>','');
		$XXX_HTML_Filter->filterTest('<script woo=yay>','');
		$XXX_HTML_Filter->filterTest('<script woo="yay">','');
		$XXX_HTML_Filter->filterTest('<script woo="yay>','');
	
		$XXX_HTML_Filter->alwaysMakeTags = true;
		$XXX_HTML_Filter->filterTest('<script','');
		$XXX_HTML_Filter->filterTest('<script woo="yay<b>','');
		$XXX_HTML_Filter->filterTest('<script woo="yay<b>hello','<b>hello</b>');
		$XXX_HTML_Filter->filterTest('<script<script>>','');
		$XXX_HTML_Filter->filterTest('<<script>script<script>>','script');
		$XXX_HTML_Filter->filterTest('<<script><script>>','');
		$XXX_HTML_Filter->filterTest('<<script>script>>','');
		$XXX_HTML_Filter->filterTest('<<script<script>>','');
	
		$XXX_HTML_Filter->alwaysMakeTags = false;
		$XXX_HTML_Filter->filterTest('<script','&lt;script');
		$XXX_HTML_Filter->filterTest('<script woo="yay<b>','&lt;script woo=&quot;yay');
		$XXX_HTML_Filter->filterTest('<script woo="yay<b>hello','&lt;script woo=&quot;yay<b>hello</b>');
		$XXX_HTML_Filter->filterTest('<script<script>>','&lt;script&gt;');
		$XXX_HTML_Filter->filterTest('<<script>script<script>>','&lt;script&gt;');
		$XXX_HTML_Filter->filterTest('<<script><script>>','&lt;&gt;');
		$XXX_HTML_Filter->filterTest('<<script>script>>','&lt;script&gt;&gt;');
		$XXX_HTML_Filter->filterTest('<<script<script>>','&lt;&lt;script&gt;');
			
		// bad protocols
		$XXX_HTML_Filter->filterTest('<a href="http://foo">bar</a>', '<a href="http://foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="ftp://foo">bar</a>', '<a href="ftp://foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="mailto:foo">bar</a>', '<a href="mailto:foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="javascript:foo">bar</a>', '<a href="#foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="java script:foo">bar</a>', '<a href="#foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="java'."\t".'script:foo">bar</a>', '<a href="#foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="java'."\n".'script:foo">bar</a>', '<a href="#foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="java'."\r".'script:foo">bar</a>', '<a href="#foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="java'.chr(1).'script:foo">bar</a>', '<a href="#foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="java'.chr(0).'script:foo">bar</a>', '<a href="#foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="jscript:foo">bar</a>', '<a href="#foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="vbscript:foo">bar</a>', '<a href="#foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="view-source:foo">bar</a>', '<a href="#foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="  javascript:foo">bar</a>', '<a href="#foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="jAvAsCrIpT:foo">bar</a>', '<a href="#foo">bar</a>');
	
		// bad protocols with entities (semicolons)
		$XXX_HTML_Filter->filterTest('<a href="javascript:foo">bar</a>', '<a href="#foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="javascript:foo">bar</a>', '<a href="#foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="javascript:foo">bar</a>', '<a href="#foo">bar</a>');
	
		// bad protocols with entities (no semicolons)
		$XXX_HTML_Filter->filterTest('<a href="&#106&#97&#118&#97&#115&#99&#114&#105&#112&#116:foo">bar</a>', '<a href="#foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058foo">bar</a>', '<a href="#foo">bar</a>');
		$XXX_HTML_Filter->filterTest('<a href="&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74:foo">bar</a>', '<a href="#foo">bar</a>');
	
		// self-closing tags
		$XXX_HTML_Filter->filterTest('<img src="a">', '<img src="a" />');
		$XXX_HTML_Filter->filterTest('<img src="a">foo</img>', '<img src="a" />foo');
		$XXX_HTML_Filter->filterTest('</img>', '');
	
		// typos
		$XXX_HTML_Filter->filterTest('<b>test<b/>', '<b>test</b>');
		$XXX_HTML_Filter->filterTest('<b/>test<b/>', '<b>test</b>');
		$XXX_HTML_Filter->filterTest('<b/>test', '<b>test</b>');
	
		// case conversion
		$XXX_HTML_Filter->caseTest('hello world', 'hello world');
		$XXX_HTML_Filter->caseTest('Hello world', 'Hello world');
		$XXX_HTML_Filter->caseTest('Hello World', 'Hello World');
		$XXX_HTML_Filter->caseTest('HELLO World', 'HELLO World');
		$XXX_HTML_Filter->caseTest('HELLO WORLD', 'Hello world');
		$XXX_HTML_Filter->caseTest('<b>HELLO WORLD', '<b>Hello world');
		$XXX_HTML_Filter->caseTest('<B>HELLO WORLD', '<B>Hello world');
		$XXX_HTML_Filter->caseTest('HELLO. WORLD', 'Hello. World');
		$XXX_HTML_Filter->caseTest('HELLO<b> WORLD', 'Hello<b> World');
		$XXX_HTML_Filter->caseTest("DOESN'T", "Doesn't");
		$XXX_HTML_Filter->caseTest("COMMA, TEST", 'Comma, test');
		$XXX_HTML_Filter->caseTest("SEMICOLON; TEST", 'Semicolon; test');
		$XXX_HTML_Filter->caseTest("DASH - TEST", 'Dash - test');
	
		// comments
		$XXX_HTML_Filter->stripComments = false;
		$XXX_HTML_Filter->filterTest('hello <!-- foo --> world', 'hello <!-- foo --> world');
		$XXX_HTML_Filter->filterTest('hello <!-- <foo --> world', 'hello <!-- &lt;foo --> world');
		$XXX_HTML_Filter->filterTest('hello <!-- foo> --> world', 'hello <!-- foo&gt; --> world');
		$XXX_HTML_Filter->filterTest('hello <!-- <foo> --> world', 'hello <!-- &lt;foo&gt; --> world');
	
		$XXX_HTML_Filter->stripComments = true;
		$XXX_HTML_Filter->filterTest('hello <!-- foo --> world', 'hello  world');
		$XXX_HTML_Filter->filterTest('hello <!-- <foo --> world', 'hello  world');
		$XXX_HTML_Filter->filterTest('hello <!-- foo> --> world', 'hello  world');
		$XXX_HTML_Filter->filterTest('hello <!-- <foo> --> world', 'hello  world');
	
		// br - shouldn't get caught by the empty 'b' tag remover
		$XXX_HTML_Filter->allowedTagsAndAttributes['br'] = array();
		$XXX_HTML_Filter->selfClosingTags[] = 'br';
		$XXX_HTML_Filter->filterTest('foo<br>bar', 'foo<br />bar');
		$XXX_HTML_Filter->filterTest('foo<br />bar', 'foo<br />bar');
	
		// stray quotes
		$XXX_HTML_Filter->filterTest('foo"bar', 'foo&quot;bar');
		$XXX_HTML_Filter->filterTest('foo"', 'foo&quot;');
		$XXX_HTML_Filter->filterTest('"bar', '&quot;bar');
		$XXX_HTML_Filter->filterTest('<a href="foo"bar">baz</a>', '<a href="foo">baz</a>');
		$XXX_HTML_Filter->filterTest('<a href=foo"bar>baz</a>', '<a href="foo">baz</a>');
	
		// correct entities should not be touched
		$XXX_HTML_Filter->filterTest('foo&amp;bar', 'foo&amp;bar');
		$XXX_HTML_Filter->filterTest('foo&quot;bar', 'foo&quot;bar');
		$XXX_HTML_Filter->filterTest('foo&lt;bar', 'foo&lt;bar');
		$XXX_HTML_Filter->filterTest('foo&gt;bar', 'foo&gt;bar');
	
		// bare ampersands should be fixed up
		$XXX_HTML_Filter->filterTest('foo&bar', 'foo&amp;bar');
		$XXX_HTML_Filter->filterTest('foo&', 'foo&amp;');
	
		// numbered entities
		$XXX_HTML_Filter->allowNumberedEntities = true;
		$XXX_HTML_Filter->filterTest('foo{bar', 'foo{bar');
		$XXX_HTML_Filter->filterTest('{bar', '{bar');
		$XXX_HTML_Filter->filterTest('foo{', 'foo{');
	
		$XXX_HTML_Filter->allowNumberedEntities = false;
		$XXX_HTML_Filter->filterTest('foo{bar', 'foo&amp;#123;bar');
		$XXX_HTML_Filter->filterTest('{bar', '&amp;#123;bar');
		$XXX_HTML_Filter->filterTest('foo{', 'foo&amp;#123;');
	
		// other entities
		$XXX_HTML_Filter->filterTest('foo&bar;baz', 'foo&amp;bar;baz');	
		$XXX_HTML_Filter->allowedEntities[] = 'bar';
		$XXX_HTML_Filter->filterTest('foo&bar;baz', 'foo&bar;baz');
	
		// entity decoder - '<'
		$entities = explode(' ', "%3c %3C &#60 &#0000060 < < &#x3c &#x000003c < < &#X3c &#X000003c < < &#x3C &#x000003C < < &#X3C &#X000003C < <");
		
		foreach ($entities as $entity)
		{
			 $XXX_HTML_Filter->entityTest($entity, '&lt;');
		}
	
		$XXX_HTML_Filter->entityTest('%3cĀĀ', '&lt;ĀĀ');
		$XXX_HTML_Filter->entityTest('%3cúú', '&lt;úú');
		$XXX_HTML_Filter->entityTest('%3c%40%aa;', '&lt;@%aa');
	
		// character checks
		$XXX_HTML_Filter->filterTest('\\', '\\');
		$XXX_HTML_Filter->filterTest('/', '/');
		$XXX_HTML_Filter->filterTest("'", "'");
		$XXX_HTML_Filter->filterTest('a'.chr(0).'b', 'a'.chr(0).'b');
		$XXX_HTML_Filter->filterTest('\\/\'!@#', '\\/\'!@#');
		$XXX_HTML_Filter->filterTest('$foo', '$foo');
	
		// this test doesn't contain &"<> since they get changed
		$allCharacters = ' !#$%\'()*+,-./0123456789:;=?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
		$XXX_HTML_Filter->filterTest($allCharacters, $allCharacters);
	
		// single quoted entities
		$XXX_HTML_Filter->filterTest("<img src=foo.jpg />", '<img src="foo.jpg" />');
		$XXX_HTML_Filter->filterTest("<img src='foo.jpg' />", '<img src="foo.jpg" />');
		$XXX_HTML_Filter->filterTest("<img src=\"foo.jpg\" />", '<img src="foo.jpg" />');
	
		// unbalanced quoted entities
		$XXX_HTML_Filter->filterTest("<img src=\"foo.jpg />", '<img src="foo.jpg" />');
		$XXX_HTML_Filter->filterTest("<img src='foo.jpg />", '<img src="foo.jpg" />');
		$XXX_HTML_Filter->filterTest("<img src=foo.jpg\" />", '<img src="foo.jpg" />');
		$XXX_HTML_Filter->filterTest("<img src=foo.jpg' />", '<img src="foo.jpg" />');
	
		// url escape sequences
		$XXX_HTML_Filter->filterTest('<a href="woo.htm%22%20bar=%22#">foo</a>', '<a href="woo.htm&quot; bar=&quot;#">foo</a>');
		$XXX_HTML_Filter->filterTest('<a href="woo.htm%22%3E%3C/a%3E%3Cscript%3E%3C/script%3E%3Ca%20href=%22#">foo</a>', '<a href="woo.htm&quot;&gt;&lt;/a&gt;&lt;script&gt;&lt;/script&gt;&lt;a href=&quot;#">foo</a>');
		$XXX_HTML_Filter->filterTest('<a href="woo.htm%aa">foo</a>', '<a href="woo.htm%aa">foo</a>');
	
	
		// this set of tests shows the differences between the different combinations of entity options	
		$XXX_HTML_Filter->allowNumberedEntities = false;
		$XXX_HTML_Filter->normalizeASCIIEntities = false;
	
		$XXX_HTML_Filter->filterTest(';', '&amp;#x3b;');
		$XXX_HTML_Filter->filterTest(';', '&amp;#x3B;');
		$XXX_HTML_Filter->filterTest(';', '&amp;#59;');
		$XXX_HTML_Filter->filterTest('%3B', '%3B');
		$XXX_HTML_Filter->filterTest('&', '&amp;#x26;');
		$XXX_HTML_Filter->filterTest('&', '&amp;#38;');
		$XXX_HTML_Filter->filterTest('Ì', 'Ì');
		$XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
		$XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
		$XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
			
		$XXX_HTML_Filter->allowNumberedEntities = true;
		$XXX_HTML_Filter->normalizeASCIIEntities = false;
	
		$XXX_HTML_Filter->filterTest(';', ';');
		$XXX_HTML_Filter->filterTest(';', ';');
		$XXX_HTML_Filter->filterTest(';', ';');
		$XXX_HTML_Filter->filterTest('%3B', '%3B');
		$XXX_HTML_Filter->filterTest('&', '&');
		$XXX_HTML_Filter->filterTest('&', '&');
		$XXX_HTML_Filter->filterTest('Ì', 'Ì');
		$XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
		$XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
		$XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
		
		for ($i = 0; $i <= 1; ++$i)
		{
			$XXX_HTML_Filter->allowNumberedEntities = $i ? true : false;
			$XXX_HTML_Filter->normalizeASCIIEntities = true;
			
			$XXX_HTML_Filter->filterTest(';', ';');
			$XXX_HTML_Filter->filterTest(';', ';');
			$XXX_HTML_Filter->filterTest(';', ';');
			$XXX_HTML_Filter->filterTest('%3B', '%3B');
			$XXX_HTML_Filter->filterTest('&', '&amp;');
			$XXX_HTML_Filter->filterTest('&', '&amp;');
			$XXX_HTML_Filter->filterTest('Ì', 'Ì');
			$XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
			$XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
			$XXX_HTML_Filter->filterTest('<a href="http://;>x</a>', '<a href="http://;">x</a>');
		}
		

?>