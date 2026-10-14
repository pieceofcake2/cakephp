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

$pluginDot = empty($plugin) ? null : $plugin . '.';
$path = $this->element('dev_error_file_path', array('package' => 'Model/Datasource', 'plugin' => $plugin ?? null, 'file' => $class . '.php'));

$this->assign('title', __d('cake_dev', 'Missing Datasource'));
$this->assign('templateName', 'missing_datasource.ctp');

$this->start('subheading');
?>
<strong><?php echo __d('cake_dev', 'Error'); ?></strong>
<?php echo __d('cake_dev', 'Datasource class %s could not be found.', '<em>' . h($pluginDot . $class) . '</em>'); ?>
<?php if (isset($message)) : ?>
	<?php echo h($message); ?>
<?php endif; ?>
<?php echo $this->element('dev_error_plugin_class'); ?>
<?php $this->end(); ?>

<?php $this->start('file'); ?>
<p>
	<?php echo __d('cake_dev', 'Check the %s setting of the datasource configuration, or create the class in file: %s', '<em>datasource</em>', '<em>' . $path . '</em>'); ?>
</p>
<?php $this->end(); ?>
<?php
if (Configure::read('debug') > 0 && !$this->request->is('ajax')) :
	$this->layout = 'dev_error';
else :
	echo $this->element('dev_error_content');
endif;
