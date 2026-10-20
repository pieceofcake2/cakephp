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

use Cake\Controller\Controller;
use Cake\Network\CakeResponse;

/**
 * SecurityTestController
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class SecurityTestController extends Controller
{
    /**
     * components property
     *
     * @var array
     */
    public $components = ['Session', 'TestSecurity'];

    /**
     * failed property
     *
     * @var bool
     */
    public $failed = false;

    /**
     * Used for keeping track of headers in test
     *
     * @var array
     */
    public $testHeaders = [];

    /**
     * fail method
     *
     * @return void
     */
    public function fail(): void
    {
        $this->failed = true;
    }

    /**
     * redirect method
     *
     * @param array|string|null $url
     * @param array|string|int|null  $code
     * @param bool $exit
     * @return CakeResponse|null
     */
    public function redirect($url, $status = null, $exit = true): mixed
    {
        return $status;
    }

    /**
     * Convenience method for header()
     *
     * @param string $status
     * @return void
     */
    public function header($status): void
    {
        if (is_array($status)) {
            $this->testHeaders += $status;
        } elseif (is_string($status)) {
            $this->testHeaders[] = $status;
        }
    }
}
