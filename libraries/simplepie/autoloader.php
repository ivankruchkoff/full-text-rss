<?php

// SPDX-FileCopyrightText: 2004-2023 Ryan Parman, Sam Sneddon, Ryan McCue
// SPDX-License-Identifier: BSD-3-Clause

/**
 * PSR-4 implementation for SimplePie.
 *
 * After registering this autoload function with SPL, the following line
 * would cause the function to attempt to load the \SimplePie\SimplePie class
 * from /src/SimplePie.php:
 *
 *      new \SimplePie\SimplePie();
 *
 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function ($class) {

    // project-specific namespace prefix
    $prefix = 'SimplePie\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/src/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// autoloader
spl_autoload_register(array(new SimplePie_Autoloader(), 'autoload'));

if (!class_exists('SimplePie'))
{
	exit('Autoloader not registered properly');
}

/**
 * Autoloader class
 */
class SimplePie_Autoloader
{
	protected $path;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library';
	}

	/**
	 * Autoloader
	 *
	 * @param string $class The name of the class to attempt to load.
	 */
	public function autoload($class)
	{
		// Only load the class if it starts with "SimplePie"
		if (strpos($class, 'SimplePie') !== 0)
		{
			return;
		}

		$relative = str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, $class);
		$filename = $this->path . DIRECTORY_SEPARATOR . $relative . '.php';
		include $filename;

		if (!class_exists($class, false) && !interface_exists($class, false) && !trait_exists($class, false)) {
			if ($class === 'SimplePie') {
				$alias = 'SimplePie\\SimplePie';
			} elseif (strpos($class, 'SimplePie_') === 0) {
				$alias = 'SimplePie\\' . str_replace('_', '\\', substr($class, 10));
			} else {
				$alias = null;
			}
			if ($alias && (class_exists($alias, false) || interface_exists($alias, false) || trait_exists($alias, false))) {
				class_alias($alias, $class);
			}
		}
	}
}
