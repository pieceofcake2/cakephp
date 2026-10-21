<?php
/**
 * Test App TestFilterDispatcher dispatcher filter
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.TestApp.Lib.Routing.Filter
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestFilterDispatcher class
 *
 * @package       Cake.Test.TestApp.Lib.Routing.Filter
 */
class TestFilterDispatcher extends DispatcherFilter
{
    public $priority = 10;

    /**
     * TestFilterDispatcher::beforeDispatch()
     *
     * @param mixed $event
     * @return CakeResponse|bool
     */
    public function beforeDispatch(CakeEvent $event): mixed
    {
        $event->stopPropagation();
        $response = $event->data['request'];
        $response->addParams(['settings' => $this->settings]);

        return null;
    }

    /**
     * TestFilterDispatcher::afterDispatch()
     *
     * @param mixed $event
     * @return mixed boolean to stop the event dispatching or null to continue
     */
    public function afterDispatch(CakeEvent $event): void
    {
    }
}
