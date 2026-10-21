<?php
/**
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace TestApp\Controller;

use Cake\Controller\Component\SessionComponent;
use Cake\Controller\Controller;
use TestApp\Controller\Component\EmailTestComponent;

/**
 * EmailTestController class
 *
 * @package       Cake.Test.Case.Controller.Component
 * @property EmailTestComponent $EmailTest
 * @property SessionComponent $Session
 */
class EmailTestController extends Controller
{
    /**
     * uses property
     *
     * @var array|bool|null
     */
    public $uses = [];

    /**
     * components property
     *
     * @var array
     */
    public $components = ['Session', 'EmailTest'];
}
