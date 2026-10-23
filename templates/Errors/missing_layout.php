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
$this->assign('title', __d('cake_dev', 'Missing Layout'));
$this->assign('templateName', 'missing_layout.ctp');

$this->start('subheading');
?>
<strong><?php echo __d('cake_dev', 'Error'); ?></strong>
<?php echo __d('cake_dev', 'The layout file %s can not be found or does not exist.', '<em>' . h($file) . '</em>'); ?>
<?php $this->end(); ?>

<?php $this->start('file'); ?>
<?php echo $this->element('dev_error_template_paths', array('file' => $file)); ?>
<?php $this->end(); ?>
