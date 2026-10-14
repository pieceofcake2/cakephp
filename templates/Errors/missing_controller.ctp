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
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

$pluginDot = empty($plugin) ? null : $plugin . '.';
$appController = (empty($plugin) ? '' : $plugin) . 'AppController';
$appControllerPackage = (empty($plugin) ? '' : $plugin . '.') . 'Controller';
$path = $this->element('dev_error_file_path', array('package' => 'Controller', 'plugin' => $plugin ?? null, 'file' => $class . '.php'));

$this->assign('title', __d('cake_dev', 'Missing Controller'));
$this->assign('templateName', 'missing_controller.ctp');

$this->start('subheading');
?>
<strong><?php echo __d('cake_dev', 'Error'); ?></strong>
<?php echo __d('cake_dev', '%s could not be found.', '<em>' . h($pluginDot . $class) . '</em>'); ?>
<?php echo $this->element('dev_error_plugin_class'); ?>
<?php $this->end(); ?>

<?php $this->start('file'); ?>
<p class="error">
	<strong><?php echo __d('cake_dev', 'Suggestion'); ?></strong>
	<?php echo __d('cake_dev', 'Create the class %s below in file: %s', '<em>' . h($class) . '</em>', $path); ?>
</p>
<?php
$code = <<<PHP
<?php
App::uses('{$appController}', '{$appControllerPackage}');

class {$class} extends {$appController}
{
}
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
