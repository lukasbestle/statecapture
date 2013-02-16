# Statecapture.php [![Build Status](https://travis-ci.org/vis7mac/statecapture.png?branch=master)](https://travis-ci.org/vis7mac/statecapture)

> Serialize a complete PHP application's state.

Sometimes, you need your app to remember it's current state - for example if you want to freeze your app and run it at a later time again.

## Warning

If your app is quite big and there's a lot to save, your app might crash because of the PHP memory limit.

This library is distributed "as is", see the `LICENSE.md` file!

Please also note that the testing for this library is not complete, because you can't delete declared classes, functions and constants without runkit. So please test it yourself for your application before going into production.

## What it serializes

The library currently serializes the following data types:

- Global variables (everything, `serialize()` can serialize)
	- Objects
	- Arrays
	- Strings
	- Numbers
	- Stuff like `$_SERVER` and `$_GET`
	- …
- Classes
- Functions
- Constants
- The application working directory

## How to use

First of all, you have to require the library file `statecapture.php`.

### Serialize a state

	$state = new Statecapture($ignore=array(), $whitelist=false);

`$ignore` is an array of everything you don't want to serialize (for example big objects):

- Variables: `$<varname>` (make sure to use single quotes not to parse it as the value!)
- Classes: `class <classname>`
- Functions: `function <function>`
- Constants: `<constname>`

If not given, everything will be serialized! Please read the warning above!

You can also use the white list mode where you can define all items you *want* to serialize. Simply set `$whitelist` to `true`.

#### Export the state

	$worked = $state->export($filename);

If `$filename`'s extension is not `.state`, this will be added automatically!

#### Get the state data

You can also manually get the state data as a string using one of these:

	$string = (string)$state;

### Unserialize a state

	$state = new Statecapture($statefile); [or]
	$state = new Statecapture($statestring);
	
	$worked = $state->unserialize();

This will include all classes and functions (if not defined yet), set constants and global variables and will set the working directory.

You can then use all that stuff and be happy! :)