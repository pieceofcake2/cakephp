<?php
/**
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       app.View.Errors
 * @since         CakePHP(tm) v 0.10.0.1076
 */

$this->layout = 'error';

if (Configure::read('debug')) :
	$this->layout = 'dev_error';

	$this->assign('title', $message);
	$this->assign('templateName', 'error500.php');

	$this->start('file');
	if (isset($error) && $error instanceof Error) :
?>
	<p>
		<strong><?php echo __d('cake_dev', 'Error in'); ?>: </strong>
		<?php echo h(Debugger::trimPath($error->getFile())); ?>, <?php echo __d('cake_dev', 'line %s', h($error->getLine())); ?>
	</p>
<?php
	endif;
	$this->end();

	return;
endif;
?>
<h2><?php echo $message; ?></h2>
<p class="error">
	<strong><?php echo __d('cake', 'Error'); ?>: </strong>
	<?php echo __d('cake', 'An Internal Error Has Occurred.'); ?>
</p>
