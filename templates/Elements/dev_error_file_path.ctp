<?php
/**
 * Prints where a file of the given package belongs in the application or a plugin,
 * relative to the project root (e.g. `src/Controller/PostsController.php` or
 * `app/Controller/PostsController.php`), as used by the error pages in debug mode.
 *
 * In the CakePHP 5 layout, templates belong in `templates/`, with the directory name and the
 * extension that the View looks for first (e.g. `templates/Error/error400.php` for
 * `Errors/error400.ctp`). When `App.namespace` is set, a plugin that is not loaded belongs in
 * `plugins/`, with its classes in `src/`.
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
$pluginsDir = null;
$appNamespace = Configure::read('App.namespace');
if (is_string($appNamespace) && $appNamespace !== '') {
	foreach (App::path('Plugin') as $pluginPath) {
		if (basename($pluginPath) === 'plugins') {
			$pluginsDir = $pluginPath;
			break;
		}
	}
}
$paths = array();
if ($plugin !== null && CakePlugin::loaded($plugin)) {
	$paths = App::path($package, $plugin);
} elseif ($plugin !== null && $pluginsDir !== null) {
	$paths = array($pluginsDir . $plugin . DS . ($package === 'View' ? 'templates' . DS : 'src' . DS . $packageDir));
} elseif ($plugin !== null) {
	$pluginPaths = App::path('Plugin');
	$paths = array((isset($pluginPaths[0]) ? $pluginPaths[0] : APP . 'Plugin' . DS) . $plugin . DS . $packageDir);
} elseif ($package === 'Plugin' && $pluginsDir !== null) {
	$paths = array($pluginsDir);
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
// Templates in `templates/` are found first by their CakePHP 5 directory name and extension.
if ($package === 'View' && $file !== '') {
	$templatePaths = array();
	foreach ($paths as $candidate) {
		if (is_dir($candidate) && $this->_isTemplatesPath($candidate)) {
			$templatePaths[] = $candidate;
		}
	}
	if (!empty($templatePaths)) {
		$paths = $templatePaths;
		$ext = (string)strrchr(basename($file), '.');
		$templateFiles = $this->_getTemplateFiles($paths[0], substr($file, 0, strlen($file) - strlen($ext)), $ext);
		$file = substr($templateFiles[0], strlen($paths[0]));
	}
}

// Prefer the path that already contains the directory of the file.
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
