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

$this->assign('title', __d('cake_dev', 'Database Error'));
$this->assign('templateName', 'pdo_error.ctp');

$this->start('subheading');
?>
<strong><?php echo __d('cake_dev', 'Error'); ?></strong>
<?php echo $message; ?>
<?php $this->end(); ?>

<?php $this->start('file'); ?>
<?php if (!empty($error->queryString)) : ?>
	<p class="notice">
		<strong><?php echo __d('cake_dev', 'SQL Query'); ?>: </strong>
	</p>
	<pre><?php echo h($error->queryString); ?></pre>
<?php endif; ?>
<?php if (!empty($error->params)) : ?>
	<p class="notice">
		<strong><?php echo __d('cake_dev', 'SQL Query Params'); ?>: </strong>
	</p>
	<pre><?php echo h(Debugger::exportVar($error->params)); ?></pre>
<?php endif; ?>
<?php $this->end(); ?>
<?php
if (Configure::read('debug') > 0 && !$this->request->is('ajax')) :
	$this->layout = 'dev_error';
else :
	echo $this->element('dev_error_content');
endif;
