<?php
/**
 * Test App SecondaryPost Model
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
 * @package       Cake.Test.TestApp.Model
 * @since         CakePHP v 1.2.0.4487
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Secondary Post stub class.
 */
class SecondaryPost extends Model
{
    /**
     * @var string
     */
    public $useTable = 'posts';

    /**
     * @var string
     */
    public $useDbConfig = 'secondary';
}
