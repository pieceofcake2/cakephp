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

$modelFile = $this->element('dev_error_file_path', array('package' => 'Model', 'plugin' => null, 'file' => $class . '.php'));

$this->assign('title', __d('cake_dev', 'Missing Database Table'));
$this->assign('templateName', 'missing_table.ctp');

$this->start('subheading');
?>
<strong><?php echo __d('cake_dev', 'Error'); ?></strong>
<?php echo __d('cake_dev', 'Table %1$s for model %2$s was not found in datasource %3$s.', '<em>' . h($table) . '</em>', '<em>' . h($class) . '</em>', '<em>' . h($ds) . '</em>'); ?>
<?php $this->end(); ?>

<?php $this->start('file'); ?>
<p>
	<?php echo __d('cake_dev', 'Create the table, or set %1$s / %2$s in the model if it uses another table or datasource: %3$s', '<code>$useTable</code>', '<code>$useDbConfig</code>', $modelFile); ?>
</p>
<?php $this->end(); ?>
<?php
if (Configure::read('debug') > 0 && !$this->request->is('ajax')) :
	$this->layout = 'dev_error';
else :
	echo $this->element('dev_error_content');
endif;
