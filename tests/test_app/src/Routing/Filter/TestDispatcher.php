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

use Cake\Controller\Controller;
use Cake\Event\CakeEvent;
use Cake\Network\CakeRequest;
use Cake\Network\CakeResponse;
use Cake\Routing\Dispatcher;

/**
 * TestDispatcher class
 *
 * @package       Cake.Test.Case.Routing
 */
class TestDispatcher extends Dispatcher
{
    /**
     * Controller instance, made publicly available for testing
     *
     * @var Controller
     */
    public $controller;

    /**
     * invoke method
     *
     * @param Controller $controller
     * @param CakeRequest $request
     * @return CakeResponse
     */
    protected function _invoke(Controller $controller, CakeRequest $request): mixed
    {
        $this->controller = $controller;

        return parent::_invoke($controller, $request);
    }

    /**
     * Helper function to test single method attaching for dispatcher filters
     *
     * @param CakeEvent $event
     * @return void
     */
    public function filterTest($event): void
    {
        $event->data['request']->params['eventName'] = $event->name();
    }

    /**
     * Helper function to test single method attaching for dispatcher filters
     *
     * @param CakeEvent $event
     * @return CakeResponse
     */
    public function filterTest2($event): mixed
    {
        $event->stopPropagation();

        return $event->data['response'];
    }
}
