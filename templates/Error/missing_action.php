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

$plugin = empty($this->request->params['plugin']) ? null : Inflector::camelize($this->request->params['plugin']);
$path = $this->element('dev_error_file_path', array('package' => 'Controller', 'plugin' => $plugin, 'file' => $controller . '.php'));

$this->layout = 'dev_error';
$this->assign('title', __d('cake_dev', 'Missing Method in %s', h($controller)));
$this->assign('templateName', 'missing_action.ctp');

$this->start('subheading');
?>
<strong><?php echo __d('cake_dev', 'Error'); ?></strong>
<?php echo __d('cake_dev', 'The action %1$s is not defined in controller %2$s', '<em>' . h($action) . '</em>', '<em>' . h($controller) . '</em>'); ?>
<?php $this->end(); ?>

<?php $this->start('file'); ?>
<p class="error">
	<strong><?php echo __d('cake_dev', 'Suggestion'); ?></strong>
	<?php echo __d('cake_dev', 'Create %1$s%2$s in file: %3$s.', '<em>' . h($controller) . '::</em>', '<em>' . h($action) . '()</em>', $path); ?>
</p>
<?php echo $this->element('dev_error_class_code', array(
	'class' => $controller,
	'package' => 'Controller',
	'plugin' => $plugin,
	'parent' => '{namespace}\\Controller\\' . $plugin . 'AppController',
	'body' => "    public function {$action}()\n    {\n    }\n",
)); ?>
<?php $this->end(); ?>
