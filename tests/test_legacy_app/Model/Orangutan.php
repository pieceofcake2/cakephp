<?php
/**
 * Test App Orangutan Model
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
 * @package       Cake.Test.TestApp.Model
 * @since         1.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Monkey', 'Model');

/**
 * Orangutan Model
 */
class Orangutan extends Monkey
{
    public $called = [];
}
