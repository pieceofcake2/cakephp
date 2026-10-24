<?php
/**
 * Prints the code of a missing class, for the error pages in debug mode.
 *
 * When `App.namespace` is set, the class is in the namespace of the application, or of the plugin,
 * followed by the package (e.g. `App\Controller\Component`), and imports its parent class.
 * A fully qualified class name keeps its own namespace.
 *
 * - `class` The class name (e.g. `PostsController`).
 * - `package` The App::path() package of the class (e.g. `Controller/Component`).
 * - `plugin` The plugin name, if the class belongs to a plugin.
 * - `parent` The fully qualified name of the parent class (e.g. `Cake\Controller\Component`).
 *   `{namespace}` stands for the namespace of the application or plugin (e.g. `{namespace}\Controller\AppController`),
 *   `{app}` for the namespace of the application (e.g. `{app}\View\Helper\AppHelper`).
 *   Without a namespace, the class extends the global class of the same short name.
 * - `body` The body of the class, indented (optional).
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
 * @var string $class
 * @var string $package
 * @var string $parent
 */

$plugin = empty($plugin) ? null : $plugin;
$body = empty($body) ? '' : $body;
$class = ltrim($class, '\\');
$packageNamespace = str_replace('/', '\\', $package);
$appNamespace = Configure::read('App.namespace');

$baseNamespace = null;
$namespace = null;
if (str_contains($class, '\\')) {
	$namespace = substr($class, 0, strrpos($class, '\\'));
	$class = substr($class, strrpos($class, '\\') + 1);
	$baseNamespace = str_ends_with($namespace, '\\' . $packageNamespace) ? substr($namespace, 0, -strlen('\\' . $packageNamespace)) : $namespace;
} elseif (is_string($appNamespace) && $appNamespace !== '') {
	$baseNamespace = $plugin === null ? trim($appNamespace, '\\') : str_replace('/', '\\', $plugin);
	$namespace = $baseNamespace . '\\' . $packageNamespace;
}

$parentName = ltrim((string)strrchr('\\' . $parent, '\\'), '\\');
$header = '';
if ($namespace !== null) {
	$appNamespace = is_string($appNamespace) && $appNamespace !== '' ? trim($appNamespace, '\\') : $baseNamespace;
	$parent = str_replace(array('{namespace}', '{app}'), array($baseNamespace, $appNamespace), $parent);
	$header = "namespace {$namespace};\n\nuse {$parent};\n\n";
}

$code = "<?php\n\n{$header}class {$class} extends {$parentName}\n{\n{$body}}";
?>
<div class="code-dump"><?php highlight_string($code); ?></div>
