<?php
/**
 * Prints the blocks assigned by an error template (`title`, `subheading`, `file`, `templateName`)
 * as regular content.
 *
 * Error templates use this element when they are not rendered in the dev_error layout,
 * e.g. for AJAX requests, so the error is still shown inside the current layout.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Elements
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var View $this
 */
?>
<h2><?php echo $this->fetch('title'); ?></h2>
<?php if ($this->fetch('subheading')) : ?>
<p class="error">
	<?php echo $this->fetch('subheading'); ?>
</p>
<?php endif; ?>
<?php echo $this->fetch('file'); ?>
<?php if ($this->fetch('templateName')) : ?>
<p class="notice">
	<strong><?php echo __d('cake_dev', 'Notice'); ?>: </strong>
	<?php echo __d('cake_dev', 'If you want to customize this error message, create %s', $this->element('dev_error_file_path', array('package' => 'View', 'plugin' => null, 'file' => 'Errors' . DS . $this->fetch('templateName')))); ?>
</p>
<?php endif; ?>
<?php
if (Configure::read('debug') > 0 && isset($error) && $error instanceof Throwable) :
	echo $this->element('exception_stack_trace');
endif;
