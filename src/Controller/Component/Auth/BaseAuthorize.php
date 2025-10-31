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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Controller\Component\Auth;

use Cake\Controller\ComponentCollection;
use Cake\Controller\Controller;
use Cake\Error\CakeException;
use Cake\Network\CakeRequest;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * Abstract base authorization adapter for AuthComponent.
 *
 * @package       Cake.Controller.Component.Auth
 * @since 2.0
 * @see AuthComponent::$authenticate
 */
abstract class BaseAuthorize
{
    /**
     * Controller for the request.
     *
     * @var Controller|null
     */
    protected ?Controller $_Controller = null;

    /**
     * Component collection instance for getting more components.
     *
     * @var ComponentCollection
     */
    protected ComponentCollection $_Collection;

    /**
     * Settings for authorize objects.
     *
     * - `actionPath` - The path to ACO nodes that contains the nodes for controllers. Used as a prefix
     *    when calling $this->action();
     * - `actionMap` - Action -> crud mappings. Used by authorization objects that want to map actions to CRUD roles.
     * - `userModel` - Model name that ARO records can be found under. Defaults to 'User'.
     *
     * @var array
     */
    public array $settings = [
        'actionPath' => null,
        'actionMap' => [
            'index' => 'read',
            'add' => 'create',
            'edit' => 'update',
            'view' => 'read',
            'delete' => 'delete',
            'remove' => 'delete',
        ],
        'userModel' => 'User',
    ];

    /**
     * Constructor
     *
     * @param ComponentCollection $collection The controller for this request.
     * @param array $settings An array of settings. This class does not use any settings.
     */
    public function __construct(ComponentCollection $collection, array $settings = [])
    {
        $this->_Collection = $collection;
        $controller = $collection->getController();
        $this->controller($controller);
        $this->settings = Hash::merge($this->settings, $settings);
    }

    /**
     * Checks user authorization.
     *
     * @param array $user Active user data
     * @param CakeRequest $request Request instance.
     * @return bool
     */
    abstract public function authorize(array $user, CakeRequest $request): bool;

    /**
     * Accessor to the controller object.
     *
     * @param Controller|null $controller null to get, a controller to set.
     * @return Controller|bool|null
     * @throws CakeException
     */
    public function controller(?Controller $controller = null): Controller|bool|null
    {
        if ($controller) {
            $this->_Controller = $controller;

            return true;
        }

        return $this->_Controller;
    }

    /**
     * Get the action path for a given request. Primarily used by authorize objects
     * that need to get information about the plugin, controller, and action being invoked.
     *
     * @param CakeRequest $request The request a path is needed for.
     * @param string $path Path format.
     * @return string the action path for the given request.
     */
    public function action(
        CakeRequest $request,
        string $path = '/:plugin/:controller/:action',
    ): string {
        $plugin = empty($request['plugin']) ? null : Inflector::camelize($request['plugin']) . '/';
        $path = str_replace(
            [':controller', ':action', ':plugin/'],
            [Inflector::camelize($request['controller']), $request['action'], $plugin],
            $this->settings['actionPath'] . $path,
        );
        $path = str_replace('//', '/', $path);

        return trim($path, '/');
    }

    /**
     * Maps crud actions to actual action names. Used to modify or get the current mapped actions.
     *
     * Create additional mappings for a standard CRUD operation:
     *
     * ```
     * $this->Auth->mapActions(array('create' => array('add', 'register'));
     * ```
     *
     * Or equivalently:
     *
     * ```
     * $this->Auth->mapActions(array('register' => 'create', 'add' => 'create'));
     * ```
     *
     * Create mappings for custom CRUD operations:
     *
     * ```
     * $this->Auth->mapActions(array('range' => 'search'));
     * ```
     *
     * You can use the custom CRUD operations to create additional generic permissions
     * that behave like CRUD operations. Doing this will require additional columns on the
     * permissions lookup. For example if one wanted an additional search CRUD operation
     * one would create and additional column '_search' in the aros_acos table. One could
     * create a custom admin CRUD operation for administration functions similarly if needed.
     *
     * @param array $map Either an array of mappings, or undefined to get current values.
     * @return array|null Either the current mappings or null when setting.
     * @see AuthComponent::mapActions()
     */
    public function mapActions(array $map = []): ?array
    {
        if (empty($map)) {
            return $this->settings['actionMap'];
        }
        foreach ($map as $action => $type) {
            if (is_array($type)) {
                foreach ($type as $typedAction) {
                    $this->settings['actionMap'][$typedAction] = $action;
                }
            } else {
                $this->settings['actionMap'][$action] = $type;
            }
        }

        return null;
    }
}
