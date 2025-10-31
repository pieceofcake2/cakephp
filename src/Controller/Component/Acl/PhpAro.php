<?php

namespace Cake\Controller\Component\Acl;

use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * Access Request Object
 */
class PhpAro
{
    /**
     * role to resolve to when a provided ARO is not listed in
     * the internal tree
     *
     * @var string
     */
    public const DEFAULT_ROLE = 'Role/default';

    /**
     * map external identifiers. E.g. if
     *
     * array('User' => array('username' => 'jeff', 'role' => 'editor'))
     *
     * is passed as an ARO to one of the methods of AclComponent, PhpAcl
     * will check if it can be resolved to an User or a Role defined in the
     * configuration file.
     *
     * @var array
     * @see app/Config/acl.php
     */
    public array $map = [
        'User' => 'User/username',
        'Role' => 'User/role',
    ];

    /**
     * aliases to map
     *
     * @var array
     */
    public array $aliases = [];

    /**
     * internal ARO representation
     *
     * @var array
     */
    protected array $_tree = [];

    /**
     * Constructor
     *
     * @param array $aro The aro data
     * @param array $map The identifier mappings
     * @param array $aliases The aliases to map.
     */
    public function __construct(array $aro = [], array $map = [], array $aliases = [])
    {
        if (!empty($map)) {
            $this->map = $map;
        }

        $this->aliases = $aliases;
        $this->build($aro);
    }

    /**
     * From the perspective of the given ARO, walk down the tree and
     * collect all inherited AROs levelwise such that AROs from different
     * branches with equal distance to the requested ARO will be collected at the same
     * index. The resulting array will contain a prioritized list of (list of) roles ordered from
     * the most distant AROs to the requested one itself.
     *
     * @param array|string $aro An ARO identifier
     * @return array prioritized AROs
     */
    public function roles(array|string $aro): array
    {
        $aros = [];
        $aro = $this->resolve($aro);
        $stack = [[$aro, 0]];

        while (!empty($stack)) {
            [$element, $depth] = array_pop($stack);
            $aros[$depth][] = $element;

            foreach ($this->_tree as $node => $children) {
                if (in_array($element, $children)) {
                    $stack[] = [$node, $depth + 1];
                }
            }
        }

        return array_reverse($aros);
    }

    /**
     * resolve an ARO identifier to an internal ARO string using
     * the internal mapping information.
     *
     * @param array|string $aro ARO identifier (User.jeff, array('User' => ...), etc)
     * @return string internal aro string (e.g. User/jeff, Role/default)
     */
    public function resolve(array|string $aro): string
    {
        foreach ($this->map as $aroGroup => $map) {
            [$model, $field] = explode('/', $map, 2);
            $mapped = '';

            if (is_array($aro)) {
                if (isset($aro['model']) && isset($aro['foreign_key']) && $aro['model'] === $aroGroup) {
                    $mapped = $aroGroup . '/' . $aro['foreign_key'];
                } elseif (isset($aro[$model][$field])) {
                    $mapped = $aroGroup . '/' . $aro[$model][$field];
                } elseif (isset($aro[$field])) {
                    $mapped = $aroGroup . '/' . $aro[$field];
                }
            } elseif (is_string($aro)) {
                $aro = ltrim($aro, '/');

                if (!str_contains($aro, '/')) {
                    $mapped = $aroGroup . '/' . $aro;
                } else {
                    [$aroModel, $aroValue] = explode('/', $aro, 2);

                    $aroModel = Inflector::camelize($aroModel);

                    if ($aroModel === $model || $aroModel === $aroGroup) {
                        $mapped = $aroGroup . '/' . $aroValue;
                    }
                }
            }

            if (isset($this->_tree[$mapped])) {
                return $mapped;
            }

            // is there a matching alias defined (e.g. Role/1 => Role/admin)?
            if (!empty($this->aliases[$mapped])) {
                return $this->aliases[$mapped];
            }
        }

        return static::DEFAULT_ROLE;
    }

    /**
     * adds a new ARO to the tree
     *
     * @param array $aro one or more ARO records
     * @return void
     */
    public function addRole(array $aro): void
    {
        foreach ($aro as $role => $inheritedRoles) {
            if (!isset($this->_tree[$role])) {
                $this->_tree[$role] = [];
            }

            if (!empty($inheritedRoles)) {
                if (is_string($inheritedRoles)) {
                    $inheritedRoles = array_map('trim', explode(',', $inheritedRoles));
                }

                foreach ($inheritedRoles as $dependency) {
                    // detect cycles
                    $roles = $this->roles($dependency);

                    if (in_array($role, Hash::flatten($roles))) {
                        $path = '';

                        foreach ($roles as $roleDependencies) {
                            $path .= implode('|', (array)$roleDependencies) . ' -> ';
                        }

                        trigger_error(__d('cake_dev', 'cycle detected when inheriting %s from %s. Path: %s', $role, $dependency, $path . $role));
                        continue;
                    }

                    if (!isset($this->_tree[$dependency])) {
                        $this->_tree[$dependency] = [];
                    }

                    $this->_tree[$dependency][] = $role;
                }
            }
        }
    }

    /**
     * adds one or more aliases to the internal map. Overwrites existing entries.
     *
     * @param array $alias alias from => to (e.g. Role/13 -> Role/editor)
     * @return void
     */
    public function addAlias(array $alias): void
    {
        $this->aliases = $alias + $this->aliases;
    }

    /**
     * build an ARO tree structure for internal processing
     *
     * @param array $aros array of AROs as key and their inherited AROs as values
     * @return void
     */
    public function build(array $aros): void
    {
        $this->_tree = [];
        $this->addRole($aros);
    }
}
