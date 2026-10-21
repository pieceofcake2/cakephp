<?php
/**
 * Test App TransSessionIdController
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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppController', 'Controller');

/**
 * TransSessionIdController class for testing the session ID in the URL.
 *
 * @package       Cake.Test.TestApp.Controller
 */
class TransSessionIdController extends AppController
{
    /**
     * autoRender property
     *
     * @var bool
     */
    public $autoRender = false;

    /**
     * Constructor.
     *
     * @param CakeRequest $request Request object for this controller.
     * @param CakeResponse $response Response object for this controller.
     */
    public function __construct($request = null, $response = null)
    {
        parent::__construct($request, $response);
        $ini = Configure::read('Session.ini');
        $ini['session.use_cookies'] = 0;
        Configure::write('Session.ini', $ini);
    }

    /**
     * For testing redirect URL with the session ID.
     *
     * @return CakeResponse|null
     */
    public function next(): mixed
    {
        $sessionName = session_name();
        $sessionId = $this->Session->id();

        return $this->redirect([
            'controller' => 'trans_session_id',
            'action' => 'next_step',
            '?' => [
                $sessionName => $sessionId,
            ],
        ]);
    }
}
