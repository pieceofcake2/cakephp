<?php

/**
 * ControllerTestCase file
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
 * @package       Cake.TestSuite
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\TestSuite;

use Cake\View\Helper;

/**
 * InterceptContentHelper class
 *
 * @package       Cake.TestSuite
 */
class InterceptContentHelper extends Helper
{
    /**
     * Intercepts and stores the contents of the view before the layout is rendered
     *
     * @param string $viewFile The view file
     * @return void
     */
    public function afterRender(string $viewFile): void
    {
        $this->_View->assign('__view_no_layout__', $this->_View->fetch('content'));
        $this->_View->Helpers->unload('InterceptContent');
    }
}
