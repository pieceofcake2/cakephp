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

$this->layout = 'dev_error';
$this->assign('title', __d('cake_dev', 'Missing View'));
$this->assign('templateName', 'missing_view.ctp');

$this->start('subheading');
?>
<strong><?php echo __d('cake_dev', 'Error'); ?></strong>
<?php echo __d('cake_dev', 'The view for %1$s%2$s was not found.', '<em>' . h(Inflector::camelize($this->request->controller)) . 'Controller::</em>', '<em>' . h($this->request->action) . '()</em>'); ?>
<?php $this->end(); ?>

<?php $this->start('file'); ?>
<?php echo $this->element('dev_error_template_paths', array('file' => $file, 'quote' => true)); ?>
<?php $this->end(); ?>
