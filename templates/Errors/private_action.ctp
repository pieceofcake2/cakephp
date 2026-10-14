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

$this->assign('title', __d('cake_dev', 'Private Method in %s', h($controller)));
$this->assign('templateName', 'private_action.ctp');

$this->start('subheading');
?>
<strong><?php echo __d('cake_dev', 'Error'); ?></strong>
<?php echo __d('cake_dev', '%s%s cannot be accessed directly.', '<em>' . h($controller) . '::</em>', '<em>' . h($action) . '()</em>'); ?>
<?php $this->end(); ?>

<?php $this->start('file'); ?>
<p>
	<?php echo __d('cake_dev', 'Only public methods of %s whose names do not start with an underscore or a routing prefix can be requested as actions. Methods inherited from %s cannot be requested either.', '<em>' . h($controller) . '</em>', '<em>Controller</em>'); ?>
</p>
<?php $this->end(); ?>
<?php
if (Configure::read('debug') > 0 && !$this->request->is('ajax')) :
	$this->layout = 'dev_error';
else :
	echo $this->element('dev_error_content');
endif;
