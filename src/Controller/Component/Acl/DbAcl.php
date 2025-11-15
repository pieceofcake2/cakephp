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
use Cake\Core\CakeObject;
use Cake\Model\Aco;
use Cake\Model\Aro;
use Cake\Model\Model;
use Cake\Model\Permission;
use Cake\Utility\ClassRegistry;

/**
 * DbAcl implements an ACL control system in the database. ARO's and ACO's are
 * structured into trees and a linking table is used to define permissions. You
 * can install the schema for DbAcl with the Schema Shell.
 *
 * `$aco` and `$aro` parameters can be slash delimited paths to tree nodes.
 *
 * eg. `controllers/Users/edit`
 *
 * Would point to a tree structure like
 *
 * ```
 *  controllers
 *      Users
 *          edit
 * ```
 *
 * @package       Cake.Controller.Component.Acl
 */
class DbAcl extends CakeObject implements AclInterface
{
    /**
     * @var Permission
     */
    public Permission $Permission;

    /**
     * @var Aro|Model
     */
    public Aro|Model $Aro;

    /**
     * @var Aco|Model
     */
    public Aco|Model $Aco;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        /** @var Permission $permission */
        $permission = ClassRegistry::init(['class' => Permission::class, 'alias' => 'Permission']);
        $this->Permission = $permission;
        $this->Aro = $this->Permission->Aro;
        $this->Aco = $this->Permission->Aco;
    }

    /**
     * Initializes the containing component and sets the Aro/Aco objects to it.
     *
     * @param Component $component The AclComponent instance.
     * @return void
     */
    public function initialize(Component $component): void
    {
        $component->Aro = $this->Aro;
        $component->Aco = $this->Aco;
    }

    /**
     * Checks if the given $aro has access to action $action in $aco
     *
     * @param Model|array|string|null $aro ARO The requesting object identifier.
     * @param Model|array|string|null $aco ACO The controlled object identifier.
     * @param string $action Action (defaults to *)
     * @return bool Success (true if ARO has access to action in ACO, false otherwise)
     * @link https://book.cakephp.org/2.0/en/core-libraries/components/access-control-lists.html#checking-permissions-the-acl-component
     */
    public function check(
        Model|array|string|null $aro,
        Model|array|string|null $aco,
        string $action = '*',
    ): bool {
        return $this->Permission->check($aro, $aco, $action);
    }

    /**
     * Allow $aro to have access to action $actions in $aco
     *
     * @param Model|array|string|null $aro ARO The requesting object identifier.
     * @param Model|array|string|null $aco ACO The controlled object identifier.
     * @param array|string $action Action (defaults to *)
     * @param int $value Value to indicate access type (1 to give access, -1 to deny, 0 to inherit)
     * @return bool Success
     * @link https://book.cakephp.org/2.0/en/core-libraries/components/access-control-lists.html#assigning-permissions
     */
    public function allow(
        Model|array|string|null $aro,
        Model|array|string|null $aco,
        array|string $action = '*',
        int $value = 1,
    ): bool {
        return $this->Permission->allow($aro, $aco, $action, $value);
    }

    /**
     * Deny access for $aro to action $action in $aco
     *
     * @param Model|array|string|null $aro ARO The requesting object identifier.
     * @param Model|array|string|null $aco ACO The controlled object identifier.
     * @param string $action Action (defaults to *)
     * @return bool Success
     * @link https://book.cakephp.org/2.0/en/core-libraries/components/access-control-lists.html#assigning-permissions
     */
    public function deny(
        Model|array|string|null $aro,
        Model|array|string|null $aco,
        string $action = '*',
    ): bool {
        return $this->allow($aro, $aco, $action, -1);
    }

    /**
     * Let access for $aro to action $action in $aco be inherited
     *
     * @param Model|array|string|null $aro ARO The requesting object identifier.
     * @param Model|array|string|null $aco ACO The controlled object identifier.
     * @param string $action Action (defaults to *)
     * @return bool Success
     */
    public function inherit(
        Model|array|string|null $aro,
        Model|array|string|null $aco,
        string $action = '*',
    ): bool {
        return $this->allow($aro, $aco, $action, 0);
    }

    /**
     * Allow $aro to have access to action $actions in $aco
     *
     * @param Model|array|string|null $aro ARO The requesting object identifier.
     * @param Model|array|string|null $aco ACO The controlled object identifier.
     * @param string $action Action (defaults to *)
     * @return bool Success
     * @see allow()
     */
    public function grant(
        Model|array|string|null $aro,
        Model|array|string|null $aco,
        string $action = '*',
    ): bool {
        return $this->allow($aro, $aco, $action);
    }

    /**
     * Deny access for $aro to action $action in $aco
     *
     * @param Model|array|string|null $aro ARO The requesting object identifier.
     * @param Model|array|string|null $aco ACO The controlled object identifier.
     * @param string $action Action (defaults to *)
     * @return bool Success
     * @see deny()
     */
    public function revoke(
        Model|array|string|null $aro,
        Model|array|string|null $aco,
        string $action = '*',
    ): bool {
        return $this->deny($aro, $aco, $action);
    }

    /**
     * Get an array of access-control links between the given Aro and Aco
     *
     * @param Model|array|string|null $aro ARO The requesting object identifier.
     * @param Model|array|string|null $aco ACO The controlled object identifier.
     * @return array|false Indexed array with: 'aro', 'aco' and 'link'
     */
    public function getAclLink(
        Model|array|string|null $aro,
        Model|array|string|null $aco,
    ): array|false {
        return $this->Permission->getAclLink($aro, $aco);
    }

    /**
     * Get the keys used in an ACO
     *
     * @param array $keys Permission model info
     * @return array ACO keys
     */
    protected function _getAcoKeys(array $keys): array
    {
        return $this->Permission->getAcoKeys($keys);
    }
}
