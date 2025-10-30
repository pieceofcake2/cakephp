<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Controller.Component.Acl
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Controller\Component\Acl;

use Cake\Controller\Component;
use Cake\Model\Model;

/**
 * Access Control List interface.
 * Implementing classes are used by AclComponent to perform ACL checks in Cake.
 *
 * @package       Cake.Controller.Component.Acl
 */
interface AclInterface
{
    /**
     * Empty method to be overridden in subclasses
     *
     * @param Model|array|string $aro ARO The requesting object identifier.
     * @param Model|array|string $aco ACO The controlled object identifier.
     * @param string $action Action (defaults to *)
     * @return bool Success
     */
    public function check(
        Model|array|string|null $aro,
        Model|array|string|null $aco,
        string $action = '*',
    ): bool;

    /**
     * Allow methods are used to grant an ARO access to an ACO.
     *
     * @param Model|array|string|null $aro ARO The requesting object identifier.
     * @param Model|array|string|null $aco ACO The controlled object identifier.
     * @param array|string $action Action (defaults to *)
     * @return bool Success
     */
    public function allow(
        Model|array|string|null $aro,
        Model|array|string|null $aco,
        array|string $action = '*',
    ): bool;

    /**
     * Deny methods are used to remove permission from an ARO to access an ACO.
     *
     * @param Model|array|string $aro ARO The requesting object identifier.
     * @param Model|array|string $aco ACO The controlled object identifier.
     * @param string $action Action (defaults to *)
     * @return bool Success
     */
    public function deny(
        Model|array|string|null $aro,
        Model|array|string|null $aco,
        string $action = '*',
    ): bool;

    /**
     * Inherit methods modify the permission for an ARO to be that of its parent object.
     *
     * @param Model|array|string $aro ARO The requesting object identifier.
     * @param Model|array|string $aco ACO The controlled object identifier.
     * @param string $action Action (defaults to *)
     * @return bool Success
     */
    public function inherit(
        Model|array|string|null $aro,
        Model|array|string|null $aco,
        string $action = '*',
    ): bool;

    /**
     * Initialization method for the Acl implementation
     *
     * @param Component $component The AclComponent instance.
     * @return void
     */
    public function initialize(Component $component): void;
}
