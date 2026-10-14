<?php
/**
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       app.View.Errors
 * @since         CakePHP(tm) v 0.10.0.1076
 */

if (Configure::read('debug') > 0 && !$this->request->is('ajax')) :
	$this->layout = 'dev_error';

	$this->assign('title', $message);
	$this->assign('templateName', 'error400.ctp');

	$this->start('subheading');
?>
	<strong><?php echo __d('cake', 'Error'); ?></strong>
	<?php printf(
		__d('cake', 'The requested address %s was not found on this server.'),
		"<strong>'{$url}'</strong>"
	); ?>
<?php
	$this->end();

	return;
endif;
?>
<h2><?php echo $message; ?></h2>
<p class="error">
	<strong><?php echo __d('cake', 'Error'); ?>: </strong>
	<?php printf(
		__d('cake', 'The requested address %s was not found on this server.'),
		"<strong>'{$url}'</strong>"
	); ?>
</p>
<?php
if (Configure::read('debug') > 0):
	echo $this->element('exception_stack_trace');
endif;
