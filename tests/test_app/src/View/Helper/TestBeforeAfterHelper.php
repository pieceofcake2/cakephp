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

namespace TestApp\View\Helper;

use Cake\View\Helper;

/**
 * TestBeforeAfterHelper class
 *
 * @package       Cake.Test.Case.View
 */
class TestBeforeAfterHelper extends Helper
{
    /**
     * property property
     *
     * @var string
     */
    public $property = '';

    /**
     * beforeLayout method
     *
     * @param string $viewFile
     * @return void
     */
    public function beforeLayout($viewFile): void
    {
        $this->property = 'Valuation';
    }

    /**
     * afterLayout method
     *
     * @param string $layoutFile
     * @return void
     */
    public function afterLayout($layoutFile): void
    {
        $this->_View->output .= 'modified in the afterlife';
    }
}
