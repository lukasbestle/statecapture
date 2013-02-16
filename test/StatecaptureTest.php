<?php

require_once('statecapture.php');

class StatecaptureTest extends PHPUnit_Framework_TestCase {
	public function tearDown() {
		if(file_exists("test.state")) unlink("test.state");
		if(file_exists("test2.state")) unlink("test2.state");
	}
	
	public function testShouldSerializeVars() {
		$testVars["testVar"] = "a string";
		$testVars["testVar2"] = 123;
		$testVars["testVar3"] = array("dimension 1" => "a string", "another one" => array("multidimensional"));
		$testVars["testVar4"] = new DemoClass();
		
		$GLOBALS["testVars"] = $testVars;
		
		$state = new Statecapture();
		
		unset($GLOBALS["testVars"]);
		$this->assertFalse(isset($GLOBALS["testVars"]));
		
		$state->unserialize();
		
		$this->assertTrue(isset($GLOBALS["testVars"]));
		$this->assertEquals($testVars, $GLOBALS["testVars"]);
	}
	
	public function testShouldIgnoreVariablesFromIgnoreList() {
		$testVar = "bla";
		$testVar2 = "blubb";
		
		$GLOBALS["testVar"] = $testVar;
		$GLOBALS["testVar2"] = $testVar2;
		
		$state = new Statecapture(array('$testVar2'));
		
		unset($GLOBALS["testVar"]);
		unset($GLOBALS["testVar2"]);
		
		$state->unserialize();
		
		$this->assertTrue(isset($GLOBALS["testVar"]));
		$this->assertFalse(isset($GLOBALS["testVar2"]));
	}
	
	public function testShouldWhitelistVariablesFromWhitelist() {
		$testVar = "bla";
		$testVar2 = "blubb";
		
		$GLOBALS["testVar"] = $testVar;
		$GLOBALS["testVar2"] = $testVar2;
		
		$state = new Statecapture(array('$testVar2'), true);
		
		unset($GLOBALS["testVar"]);
		unset($GLOBALS["testVar2"]);
		
		$state->unserialize();
		
		$this->assertFalse(isset($GLOBALS["testVar"]));
		$this->assertTrue(isset($GLOBALS["testVar2"]));
	}
	
	public function testShouldExport() {
		$state = new Statecapture();
		
		$this->assertTrue($state->export("test"));
		$this->assertTrue($state->export("test2.state"));
		
		$this->assertEquals((string)$state, file_get_contents("test.state"));
		$this->assertEquals((string)$state, file_get_contents("test2.state"));
	}
	
	public function testShouldTakeState() {
		$state = new Statecapture();
		$this->assertTrue($state->export("test"));
		
		new Statecapture("test");
		new Statecapture("test.state");
		new Statecapture(file_get_contents("test.state"));
	}
}

class DemoClass {
	private $private = "bla";
	
	public $test = "a public value";
	
	public function __construct() {
		$this->test = "a new value!";
	}
	
	public function getPrivate() {
		return $private;
	}
}