<?php
/**
 * Test App OrangeSessionTestController
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
 * @package       Cake.Test.TestApp.Controller
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * OrangeSessionTestController class
 *
 * @package       Cake.Test.TestApp.Controller
 */
class OrangeSessionTestController extends Controller
{
    /**
     * uses property
     *
     * @var array
     */
    public $uses = [];

    /**
     * sessionId method
     *
     * @return string
     */
    public function sessionId(): mixed
    {
        return $this->Session->id();
    }
}
