<?php

/**
 * Statecapture
 * Serialize a complete PHP application's state.
 *
 * @version 1.0
 * @author Lukas Bestle <http://lu-x.me>
 * @link https://github.com/vis7mac/statecapture
 * @copyright Copyright 2013 Lukas Bestle
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @file statecapture.php
 */

class Statecapture {
	private $data;
	
	private $dataTypes = array(
		"functions" => 'function %name',
		"classes" => 'class %name',
		"consts" => '%name',
		"vars" => '$%name'
	);
	
	public function __construct($data = array(), $whitelist = false) {
		if(is_array($data)) {
			// Serialization
			
			$this->serialize($data, $whitelist);
		} else if(substr($data, -6) == ".state" && is_file($data)) {
			// Unserialization from file
			
			$this->data = unserialize(file_get_contents($data));
		} else if(is_string($data) && file_exists($data . ".state")) {
			// Unserialization from file
			
			$this->data = unserialize(file_get_contents($data . ".state"));
		} else if(is_string($data) && @unserialize($data) !== false) {
			// Unserialization from string
			
			$this->data = unserialize($data);
		} else {
			throw new InvalidArgumentException("Could not match your request.");
		}
		
		if(!is_array($this->data)) {
			throw new Exception("Could not get valid data from file.");
		}
	}
	
	// ================
	// Public functions
	public function export($filename) {
		if(substr($filename, -6) != ".state") $filename .= ".state";
		
		return (file_put_contents($filename, $this->__toString()) != false);
	}
	
	public function unserialize() {
		// Set variables
		$GLOBALS = array_merge($GLOBALS, $this->data["vars"]);
		
		// Set consts
		foreach($this->data["consts"] as $const => $value) {
			if(!defined($const)) {
				if(!define($const, $value)) return false;
			}
		}
		
		// Set working directory
		if(!chdir($this->data["cwd"])) return false;
		
		// Define functions and classes
		if(!$this->define("function", $this->data["functions"])) return false;
		if(!$this->define("class", $this->data["classes"])) return false;
		
		return true;
	}
	
	// ==========
	// OO methods
	public function __toString() {
		return serialize($this->data);
	}
	
	// ========================
	// Private helper functions
	private function serialize($ignore, $whitelist) {
		// Get constants
		$consts = get_defined_constants();
		
		// Get the working directory
		$working = realpath(".");
		
		// Get classes
		$classes = array();
		foreach(get_declared_classes() as $classname) {
			$class = new ReflectionClass($classname);
			
			$file = $class->getFileName();
			$start = $class->getStartLine();
			$end = $class->getEndLine();
			
			// Skip system classes
			if(!$file || !$start || !$end) continue;
			
			// Get the class as string
			$fileContents = file($file);
			$classArray = array_slice($fileContents, $start - 1, $end - $start);
			$classString = implode("\n", $classArray);
			
			// Fix paths
			$classString = $this->fixPaths($classString, $file);
			
			$classes[$classname] = $classString;
		}
		
		// Get functions
		$functions = array();
		$definedFunctions = get_defined_functions();
		foreach($definedFunctions["user"] as $funcname) {
			$func = new ReflectionFunction($funcname);
			
			$file = $func->getFileName();
			$start = $func->getStartLine();
			$end = $func->getEndLine();
			
			// Skip functions which are not serializable
			if(!$file || !$start || !$end) continue;
			
			// Get the function as string
			$fileContents = file($file);
			$funcArray = array_slice($fileContents, $start - 1, $end - $start);
			$funcString = implode("\n", $funcArray);
			
			// Fix paths
			$funcString = $this->fixPaths($funcString, $file);
			
			$functions[$funcname] = $funcString;
		}
		
		// Set the data object
		$data = array();
		$data["vars"] = $GLOBALS;
		$data["consts"] = $consts;
		$data["cwd"] = $working;
		$data["classes"] = $classes;
		$data["functions"] = $functions;
				
		// Filter using ignore-/whitelist
		$dataFiltered = array();
		foreach($data as $type => $items) {
			if(!isset($this->dataTypes[$type])) {
				$dataFiltered[$type] = $items;
				continue;
			}
			
			$dataFiltered[$type] = array();
			
			foreach($items as $name => $item) {
				$toMatch = str_replace('%name', $name, $this->dataTypes[$type]);
				
				if(($whitelist && in_array($toMatch, $ignore)) || (!$whitelist && !in_array($toMatch, $ignore))) $dataFiltered[$type][$name] = $item;
			}
		}
		
		// Set the data
		$this->data = $dataFiltered;
	}
	
	private function fixPaths($string, $path) {
		$string = str_replace("__FILE__", "'$path'", $string);
		$string = str_replace("__DIR__", "'" . dirname($path) . "'", $string);
		
		return $string;
	}
	
	private function define($type, $array) {
		foreach($array as $name => $item) {
			if($type == "function") {
				if(function_exists($name)) continue;
			} else {
				if(class_exists($name)) continue;
			}
			
			eval($item);
		}
	}
}