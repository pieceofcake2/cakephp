<?php
/**
 * PHP configuration based AclInterface implementation
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
 * @package       Cake.Controller.Component.Acl
 * @since         CakePHP(tm) v 2.1
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Controller\Component\Acl;

use Cake\Configure\PhpReader;
use Cake\Controller\Component;
use Cake\Controller\Component\AclComponent;
use Cake\Core\CakeObject;
use Cake\Error\AclException;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * PhpAcl implements an access control system using a plain PHP configuration file.
 * An example file can be found in app/Config/acl.php
 *
 * @package Cake.Controller.Component.Acl
 */
class PhpAcl extends CakeObject implements AclInterface
{
    /**
     * Constant for deny
     *
     * @var bool
     */
    public const DENY = false;

    /**
     * Constant for allow
     *
     * @var bool
     */
    public const ALLOW = true;

    /**
     * Options:
     *  - policy: determines behavior of the check method. Deny policy needs explicit allow rules, allow policy needs explicit deny rules
     *  - config: absolute path to config file that contains the acl rules (@see app/Config/acl.php)
     *
     * @var array
     */
    public $options = [];

    /**
     * Aro Object
     *
     * @var PhpAro
     */
    public $Aro = null;

    /**
     * Aco Object
     *
     * @var PhpAco
     */
    public $Aco = null;

    /**
     * Constructor
     *
     * Sets a few default settings up.
     */
    public function __construct()
    {
        $this->options = [
            'policy' => static::DENY,
            'config' => CONFIG . 'acl.php',
        ];
    }

    /**
     * Initialize method
     *
     * @param AclComponent $Component Component instance
     * @return void
     */
    public function initialize(Component $Component)
    {
        if (!empty($Component->settings['adapter'])) {
            $this->options = $Component->settings['adapter'] + $this->options;
        }

        $Reader = new PhpReader(dirname($this->options['config']) . DS);
        $config = $Reader->read(basename($this->options['config']));
        $this->build($config);
        $Component->Aco = $this->Aco;
        $Component->Aro = $this->Aro;
    }

    /**
     * build and setup internal ACL representation
     *
     * @param array $config configuration array, see docs
     * @return void
     * @throws AclException When required keys are missing.
     */
    public function build(array $config)
    {
        if (empty($config['roles'])) {
            throw new AclException(__d('cake_dev', '"roles" section not found in configuration.'));
        }

        if (empty($config['rules']['allow']) && empty($config['rules']['deny'])) {
            throw new AclException(__d('cake_dev', 'Neither "allow" nor "deny" rules were provided in configuration.'));
        }

        $rules['allow'] = !empty($config['rules']['allow']) ? $config['rules']['allow'] : [];
        $rules['deny'] = !empty($config['rules']['deny']) ? $config['rules']['deny'] : [];
        $roles = !empty($config['roles']) ? $config['roles'] : [];
        $map = !empty($config['map']) ? $config['map'] : [];
        $alias = !empty($config['alias']) ? $config['alias'] : [];

        $this->Aro = new PhpAro($roles, $map, $alias);
        $this->Aco = new PhpAco($rules);
    }

    /**
     * Allow ARO access to ACO. The change is only applied to the in-memory permission tree.
     *
     * @param string $aro ARO The requesting object identifier.
     * @param string $aco ACO The controlled object identifier.
     * @param string $action Action (defaults to *)
     * @return null PhpAcl does not report success
     */
    public function allow($aro, $aco, $action = '*')
    {
        $this->Aco->access($this->Aro->resolve($aro), $aco, $action, 'allow');

        return null;
    }

    /**
     * deny ARO access to ACO
     *
     * @param string $aro ARO The requesting object identifier.
     * @param string $aco ACO The controlled object identifier.
     * @param string $action Action (defaults to *)
     * @return null PhpAcl does not report success
     */
    public function deny($aro, $aco, $action = '*')
    {
        $this->Aco->access($this->Aro->resolve($aro), $aco, $action, 'deny');

        return null;
    }

    /**
     * No op method
     *
     * @param string $aro ARO The requesting object identifier.
     * @param string $aco ACO The controlled object identifier.
     * @param string $action Action (defaults to *)
     * @return bool Success
     */
    public function inherit($aro, $aco, $action = '*')
    {
        return false;
    }

    /**
     * Main ACL check function. Checks to see if the ARO (access request object) has access to the
     * ACO (access control object).
     *
     * @param string $aro ARO
     * @param string $aco ACO
     * @param string $action Action
     * @return bool true if access is granted, false otherwise
     */
    public function check($aro, $aco, $action = '*')
    {
        $allow = $this->options['policy'];
        $prioritizedAros = $this->Aro->roles($aro);

        if ($action && $action !== '*') {
            $aco .= '/' . $action;
        }

        $path = $this->Aco->path($aco);

        if (empty($path)) {
            return $allow;
        }

        foreach ($path as $node) {
            foreach ($prioritizedAros as $aros) {
                if (!empty($node['allow'])) {
                    $allow = $allow || count(array_intersect($node['allow'], $aros));
                }

                if (!empty($node['deny'])) {
                    $allow = $allow && !count(array_intersect($node['deny'], $aros));
                }
            }
        }

        return $allow;
    }
}

class_alias(PhpAcl::class, 'PhpAcl');
