<?php
/**
 * Components collection is used as a registry for loaded components and handles loading
 * and constructing component class objects.
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
 * @package       Cake.Controller
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Controller;

use Cake\Core\App;
use Cake\Error\MissingComponentException;
use Cake\Event\CakeEventListener;
use Cake\Utility\ObjectCollection;

/**
 * Components collection is used as a registry for loaded components and handles loading
 * and constructing component class objects.
 *
 * @package       Cake.Controller
 */
class ComponentCollection extends ObjectCollection implements CakeEventListener
{
    /**
     * A hash of loaded objects, indexed by name
     *
     * @var array<Component>
     */
    protected array $_loaded = [];

    /**
     * The controller that this collection was initialized with.
     *
     * @var Controller
     */
    protected $_Controller = null;

    /**
     * Initializes all the Components for a controller.
     * Attaches a reference of each component to the Controller.
     *
     * @param Controller $controller Controller to initialize components for.
     * @return void
     */
    public function init(Controller $controller): void
    {
        if (empty($controller->components)) {
            return;
        }
        $this->_Controller = $controller;
        $components = ComponentCollection::normalizeObjectArray($controller->components);
        foreach ($components as $name => $properties) {
            $controller->{$name} = $this->load($properties['class'], $properties['settings']);
        }
    }

    /**
     * Set the controller associated with the collection.
     *
     * @param Controller $controller Controller to set
     * @return void
     */
    public function setController(Controller $controller): void
    {
        $this->_Controller = $controller;
    }

    /**
     * Get the controller associated with the collection.
     *
     * @return Controller|null Controller instance
     */
    public function getController(): ?Controller
    {
        return $this->_Controller;
    }

    /**
     * Loads/constructs a component. Will return the instance in the registry if it already exists.
     * You can use `$settings['enabled'] = false` to disable callbacks on a component when loading it.
     * Callbacks default to on. Disabled component methods work as normal, only callbacks are disabled.
     *
     * You can alias your component as an existing component by setting the 'className' key, i.e.,
     * ```
     * public $components = array(
     *   'Email' => array(
     *     'className' => 'AliasedEmail'
     *   );
     * );
     * ```
     * All calls to the `Email` component would use `AliasedEmail` instead.
     *
     * @template T of Component
     * @param class-string<T>|string $name Component name to load
     * @param array $options Settings for the component.
     * @return Component|T A component object, Either the existing loaded component or a new one.
     * @throws MissingComponentException when the component could not be found
     */
    public function load(string $name, array $options = []): Component
    {
        if (isset($options['className'])) {
            $alias = $name;
            $name = $options['className'];
        }
        [$plugin, $_name] = pluginSplit($name, true);
        if (!isset($alias)) {
            $alias = $_name;
        }
        if (isset($this->_loaded[$alias])) {
            return $this->_loaded[$alias];
        }

        $componentClass = App::className($name, 'Controller/Component', 'Component');

        if (!$componentClass) {
            throw new MissingComponentException([
                'class' => $_name . 'Component',
                'plugin' => $plugin ? substr($plugin, 0, -1) : null,
            ]);
        }

        $this->_loaded[$alias] = new $componentClass($this, $options);
        $enable = $options['enabled'] ?? true;
        if ($enable) {
            $this->enable($alias);
        }

        return $this->_loaded[$alias];
    }

    /**
     * Returns the implemented events that will get routed to the trigger function
     * in order to dispatch them separately on each component
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        return [
            'Controller.initialize' => ['callable' => 'trigger'],
            'Controller.startup' => ['callable' => 'trigger'],
            'Controller.beforeRender' => ['callable' => 'trigger'],
            'Controller.beforeRedirect' => ['callable' => 'trigger'],
            'Controller.shutdown' => ['callable' => 'trigger'],
        ];
    }
}
