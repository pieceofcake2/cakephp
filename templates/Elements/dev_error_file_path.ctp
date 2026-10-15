<?php
/**
 * Prints where a file of the given package belongs in the application or a plugin,
 * relative to the project root (e.g. `src/Controller/PostsController.php` or
 * `app/Controller/PostsController.php`), as used by the error pages in debug mode.
 *
 * - `package` The App::path() package (e.g. `Controller`, `Model/Behavior`, `View`).
 * - `file` The file name, relative to the package directory. An empty string prints the package directory.
 * - `plugin` The plugin name, if the file belongs to a plugin.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Elements
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var View $this
 * @var string $package
 * @var string $file
 */

$plugin = empty($plugin) ? null : $plugin;
$packageDir = str_replace('/', DS, $package) . DS;
$paths = array();
if ($plugin !== null && CakePlugin::loaded($plugin)) {
	$paths = App::path($package, $plugin);
} elseif ($plugin !== null) {
	$pluginPaths = App::path('Plugin');
	$paths = array((isset($pluginPaths[0]) ? $pluginPaths[0] : APP . 'Plugin' . DS) . $plugin . DS . $packageDir);
} else {
	$paths = App::path($package);
}

$paths = array_values(array_filter($paths, function ($path) {
	return strpos($path, CORE_PATH) !== 0 && strpos($path, CORE_ROOT . DS . 'templates' . DS) !== 0;
}));
if (empty($paths)) {
	$paths = array(($plugin === null ? APP : APP . 'Plugin' . DS . $plugin . DS) . $packageDir);
}

// View packages can have several paths (e.g. `src/View/` and `templates/`).
// Prefer the one that already contains the directory of the file.
$path = $paths[0];
$fileDir = $file === '' ? '' : dirname($file);
if ($fileDir !== '.') {
	foreach ($paths as $candidate) {
		if (is_dir($candidate . $fileDir)) {
			$path = $candidate;
			break;
		}
	}
}

$path .= $file;
if (defined('ROOT') && strpos($path, ROOT . DS) === 0) {
	$path = substr($path, strlen(ROOT . DS));
}
echo h($path);
