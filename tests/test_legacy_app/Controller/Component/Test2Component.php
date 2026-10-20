<?php
/**
 * Test App Test2Component
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
 * @package       Cake.Test.TestApp.Controller.Component
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('TestComponent', 'Controller/Component');

class Test2Component extends TestComponent
{
    public $model;

    public function __construct(ComponentCollection $collection, $settings)
    {
        $this->controller = $collection->getController();
        $this->model = $this->controller->modelClass;
    }

    public function beforeRender(Controller $controller): mixed
    {
        return false;
    }
}
