<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Errors
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

$pluginDir = $this->element('dev_error_file_path', array('package' => 'Plugin', 'plugin' => null, 'file' => ''));
$bootstrapFile = defined('CONFIG') ? CONFIG . 'bootstrap.php' : APP . 'Config' . DS . 'bootstrap.php';
if (defined('ROOT') && strpos($bootstrapFile, ROOT . DS) === 0) {
	$bootstrapFile = substr($bootstrapFile, strlen(ROOT . DS));
}

$this->assign('title', __d('cake_dev', 'Missing Plugin'));
$this->assign('templateName', 'missing_plugin.ctp');

$this->start('subheading');
?>
<strong><?php echo __d('cake_dev', 'Error'); ?></strong>
<?php echo __d('cake_dev', 'The application is trying to load a file from the %s plugin', '<em>' . h($plugin) . '</em>'); ?>
<br>
<br>
<?php echo __d('cake_dev', 'Make sure your plugin %s is in the %s directory and was loaded', '<em>' . h($plugin) . '</em>', $pluginDir); ?>
<?php $this->end(); ?>

<?php $this->start('file'); ?>
<p class="error">
	<strong><?php echo __d('cake_dev', 'Suggestion'); ?></strong>
	<?php echo __d('cake_dev', 'Load the plugin in your %s file', h($bootstrapFile)); ?>
</p>
<?php
$code = <<<PHP
<?php
// {$bootstrapFile}
CakePlugin::load('{$plugin}');

// or, to load all plugins at once:
CakePlugin::loadAll();
PHP;
?>
<div class="code-dump"><button type="button" class="code-copy"><?php echo __d('cake_dev', 'Copy'); ?></button><?php highlight_string($code); ?></div>
<?php $this->end(); ?>
<?php
if (Configure::read('debug') > 0 && !$this->request->is('ajax')) :
	$this->layout = 'dev_error';
else :
	echo $this->element('dev_error_content');
endif;
