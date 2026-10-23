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

namespace TestApp\Routing\Filter;

use Cake\Event\CakeEvent;
use Cake\Routing\DispatcherFilter;

/**
 * Dispatcher filter in a namespace
 */
class NamespacedFilter extends DispatcherFilter
{
    /**
     * @var bool
     */
    public static $called = false;

    /**
     * @var array
     */
    public static $calledSettings = [];

    /**
     * @param CakeEvent $event The event
     * @return void
     */
    public function beforeDispatch(CakeEvent $event)
    {
        static::$called = true;
        static::$calledSettings = $this->settings;
    }
}
