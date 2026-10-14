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

$this->assign('title', __d('cake_dev', 'Missing Database Connection'));
$this->assign('templateName', 'missing_connection.ctp');

$this->start('subheading');
?>
<strong><?php echo __d('cake_dev', 'Error'); ?></strong>
<?php echo __d('cake_dev', 'A Database connection using "%s" was missing or unable to connect.', h($class)); ?>
<?php if (isset($message)) : ?>
	<br>
	<?php echo __d('cake_dev', 'The database server returned this error: %s', h($message)); ?>
<?php endif; ?>
<?php $this->end(); ?>

<?php $this->start('file'); ?>
<?php if (isset($enabled) && !$enabled) : ?>
<p class="error">
	<strong><?php echo __d('cake_dev', 'Error'); ?></strong>
	<?php echo __d('cake_dev', '%s driver is NOT enabled', h($class)); ?>
</p>
<?php endif; ?>
<?php $this->end(); ?>
<?php
if (Configure::read('debug') > 0 && !$this->request->is('ajax')) :
	$this->layout = 'dev_error';
else :
	echo $this->element('dev_error_content');
endif;
