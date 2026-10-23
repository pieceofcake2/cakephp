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

$configFile = defined('CONFIG') ? CONFIG . 'database.php' : APP . 'Config' . DS . 'database.php';
if (defined('ROOT') && strpos($configFile, ROOT . DS) === 0) {
	$configFile = substr($configFile, strlen(ROOT . DS));
}

$this->assign('title', __d('cake_dev', 'Missing Database Connection'));
$this->assign('templateName', 'missing_database.ctp');

$this->start('subheading');
?>
<strong><?php echo __d('cake_dev', 'Error'); ?></strong>
<?php echo __d('cake_dev', 'Scaffold requires a database connection'); ?>
<?php $this->end(); ?>

<?php $this->start('file'); ?>
<p class="error">
	<strong><?php echo __d('cake_dev', 'Suggestion'); ?></strong>
	<?php echo __d('cake_dev', 'Confirm you have created the file: %s', h($configFile)); ?>
</p>
<?php $this->end(); ?>
<?php
if (Configure::read('debug') > 0 && !$this->request->is('ajax')) :
	$this->layout = 'dev_error';
else :
	echo $this->element('dev_error_content');
endif;
