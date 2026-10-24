<?php
/**
 * Lists the paths where a missing template or layout can be created, for the error pages in debug mode.
 *
 * When the view paths have `templates/` directories (the CakePHP 5 layout), only these are listed,
 * with the file that the View looks for first in them: the CakePHP 5 directory name and the `.php`
 * extension (e.g. `layout/default.php` for `Layouts/default.ctp`). Otherwise all the view paths of
 * the application are listed with the file.
 *
 * - `file` The file name relative to the view paths, with its extension (e.g. `Pages/home.ctp`).
 * - `quote` Whether to quote the file name.
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
 * @var string $file
 */

$paths = array();
$templatePaths = array();
foreach ($this->_paths($this->plugin) as $path) {
	if (strpos($path, CORE_PATH) !== false || strpos($path, CORE_ROOT . DS . 'templates' . DS) === 0) {
		continue;
	}
	$paths[] = $path;
	if (is_dir($path) && $this->_isTemplatesPath($path)) {
		$templatePaths[] = $path;
	}
}
if (!empty($templatePaths)) {
	$paths = $templatePaths;
	$ext = (string)strrchr(basename($file), '.');
	$templateFiles = $this->_getTemplateFiles($paths[0], substr($file, 0, strlen($file) - strlen($ext)), $ext);
	$file = substr($templateFiles[0], strlen($paths[0]));
}
?>
<p>
	<?php echo __d('cake_dev', 'Confirm you have created the file: %s', empty($quote) ? h($file) : '"' . h($file) . '"'); ?>
	<?php echo __d('cake_dev', 'in one of the following paths:'); ?>
</p>
<ul>
<?php
	foreach ($paths as $path) :
		echo sprintf('<li>%s%s</li>', h($path), h($file));
	endforeach;
?>
</ul>
