<?php
/**
 * Test App TestBeforeAfter Helper
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
 * @package       Cake.Test.TestApp.View.Helper
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestBeforeAfterHelper class
 *
 * @package       Cake.Test.TestApp.View.Helper
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
