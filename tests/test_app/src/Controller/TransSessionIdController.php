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

use Cake\Core\Configure;
use Cake\Network\CakeRequest;
use Cake\Network\CakeResponse;

/**
 * TransSessionIdController class for testing session.use_trans_sid=1.
 *
 * @package       Cake.Test.Case.Controller
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
        $ini['session.use_only_cookies'] = 0;
        $ini['session.use_trans_sid'] = 1;
        Configure::write('Session.ini', $ini);
    }

    /**
     * For testing redirect URL with session.use_trans_sid=1.
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
