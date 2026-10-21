<?php
/**
 * Test App CacheTest Controller
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
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * CacheTestController class
 *
 * @package       Cake.Test.TestApp.Controller
 */
class CacheTestController extends Controller
{
    /**
     * helpers property
     *
     * @var array
     */
    public $helpers = ['Html', 'Cache'];

    /**
     * cache_parsing method
     *
     * @return void
     */
    public function cache_parsing(): void
    {
        $this->viewPath = 'Posts';
        $this->layout = 'cache_layout';
        $this->set('variable', 'variableValue');
        $this->set('superman', 'clark kent');
        $this->set('batman', 'bruce wayne');
        $this->set('spiderman', 'peter parker');
    }
}
