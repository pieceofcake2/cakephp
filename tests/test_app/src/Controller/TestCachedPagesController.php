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
use TestApp\Network\DispatcherMockCakeResponse;

/**
 * TestCachedPagesController class
 *
 * @package       Cake.Test.Case.Routing
 */
class TestCachedPagesController extends Controller
{
    /**
     * uses property
     *
     * @var array|bool|null
     */
    public $uses = [];

    /**
     * helpers property
     *
     * @var array
     */
    public $helpers = ['Cache', 'Html'];

    /**
     * cacheAction property
     *
     * @var array|string|int|false
     */
    public $cacheAction = [
        'index' => '+2 sec',
        'test_nocache_tags' => '+2 sec',
        'view' => '+2 sec',
    ];

    /**
     * Mock out the response object so it doesn't send headers.
     *
     * @var string
     */
    protected $_responseClass = DispatcherMockCakeResponse::class;

    /**
     * viewPath property
     *
     * @var string|null
     */
    public $viewPath = 'Posts';

    /**
     * index method
     *
     * @return void
     */
    public function index(): void
    {
        $this->render();
    }

    /**
     * test_nocache_tags method
     *
     * @return void
     */
    public function test_nocache_tags(): void
    {
        $this->render();
    }

    /**
     * view method
     *
     * @return void
     */
    public function view($id = null): void
    {
        $this->render('index');
    }

    /**
     * test cached forms / tests view object being registered
     *
     * @return void
     */
    public function cache_form(): void
    {
        $this->cacheAction = 10;
        $this->helpers[] = 'Form';
    }

    /**
     * Test cached views with themes.
     *
     * @return void
     */
    public function themed(): void
    {
        $this->cacheAction = 10;
        $this->viewClass = 'Theme';
        $this->theme = 'TestTheme';
    }
}
