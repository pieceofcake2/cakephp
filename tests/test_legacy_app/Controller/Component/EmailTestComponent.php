<?php
/**
 * Test App EmailTestComponent
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
 * @since         CakePHP(tm) v 1.2.0.5347
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * EmailTestComponent class
 *
 * @package       Cake.Test.TestApp.Controller.Component
 */
class EmailTestComponent extends EmailComponent
{
    /**
     * Convenience method for testing.
     *
     * @return string
     */
    public function strip($content, $message = false): mixed
    {
        return parent::_strip($content, $message);
    }
}
