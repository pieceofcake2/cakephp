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

namespace TestApp\Controller\Component\Acl;

use Cake\Controller\Component;
use Cake\Controller\Component\Acl\AclInterface;

/**
 * ACL implementation in a namespace
 */
class NamespacedAcl implements AclInterface
{
    /**
     * @param string $aro ARO
     * @param string $aco ACO
     * @param string $action Action
     * @return bool
     */
    public function check($aro, $aco, $action = '*')
    {
        return $aro === 'namespaced';
    }

    /**
     * @param string $aro ARO
     * @param string $aco ACO
     * @param string $action Action
     * @return bool
     */
    public function allow($aro, $aco, $action = '*')
    {
        return true;
    }

    /**
     * @param string $aro ARO
     * @param string $aco ACO
     * @param string $action Action
     * @return bool
     */
    public function deny($aro, $aco, $action = '*')
    {
        return true;
    }

    /**
     * @param string $aro ARO
     * @param string $aco ACO
     * @param string $action Action
     * @return bool
     */
    public function inherit($aro, $aco, $action = '*')
    {
        return true;
    }

    /**
     * @param \Component $component Component
     * @return void
     */
    public function initialize(Component $component)
    {
    }
}
