<?php
/**
 * Test App DbAclNodeTestBase Model
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
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * DB ACL wrapper test class
 *
 * @package       Cake.Test.TestApp.Model
 */
class DbAclNodeTestBase extends AclNode
{
    /**
     * useDbConfig property
     *
     * @var string
     */
    public $useDbConfig = 'test';

    /**
     * cacheSources property
     *
     * @var bool
     */
    public $cacheSources = false;
}
