<?php
/**
 * Explains how to make a plugin class available, for the "missing class" error pages in debug mode.
 *
 * Ported from the CakePHP 5 `templates/element/plugin_class_error.php` element.
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
 * @var string|null $plugin
 */

if (empty($plugin)) {
	return;
}

echo '<br><br>';

if (!CakePlugin::loaded($plugin)) :
	echo __d(
		'cake_dev',
		'Make sure your plugin %s is in the %s directory and was loaded',
		'<em>' . h($plugin) . '</em>',
		$this->element('dev_error_file_path', array('package' => 'Plugin', 'plugin' => null, 'file' => ''))
	);
else :
	echo __d(
		'cake_dev',
		'The %s plugin is loaded. Make sure the class file is in the directory shown below.',
		'<em>' . h($plugin) . '</em>'
	);
endif;
