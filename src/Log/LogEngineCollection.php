<?php
/**
 * Registry of loaded log engines
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
 * @package       Cake.Log
 * @since         CakePHP(tm) v 2.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Log;

use Cake\Core\App;
use Cake\Error\CakeLogException;
use Cake\Log\Engine\BaseLog;
use Cake\Utility\ObjectCollection;

/**
 * Registry of loaded log engines
 *
 * @package       Cake.Log
 */
class LogEngineCollection extends ObjectCollection
{
    /**
     * Loads/constructs a Log engine.
     *
     * @param string $name instance identifier
     * @param array $options Setting for the Log Engine
     * @return CakeLogInterface BaseLog engine instance
     * @throws CakeLogException when logger class does not implement a write method
     */
    public function load($name, $options = [])
    {
        $enable = $options['enabled'] ?? true;
        $loggerName = $options['engine'];
        unset($options['engine']);
        $className = static::_getLogger($loggerName);
        $logger = new $className($options);
        if (!$logger instanceof CakeLogInterface) {
            throw new CakeLogException(
                __d('cake_dev', 'logger class %s does not implement a %s method.', $loggerName, 'write()'),
            );
        }
        $this->_loaded[$name] = $logger;
        if ($enable) {
            $this->enable($name);
        }

        return $logger;
    }

    /**
     * Attempts to import a logger class from the various paths it could be on.
     * Checks that the logger class implements a write method as well.
     *
     * @param string $loggerName the plugin.className of the logger class you want to build.
     * @return mixed boolean false on any failures, string of classname to use if search was successful.
     * @throws CakeLogException
     */
    protected static function _getLogger($loggerName)
    {
        [$plugin, $name] = pluginSplit($loggerName, true);
        $originalLoggerName = $loggerName;
        if (!str_ends_with($loggerName, 'Log')) {
            $loggerName .= 'Log';
        }

        // Try to resolve class name using App::className()
        $className = App::className($loggerName, 'Log/Engine');

        // Fall back to legacy loading for backward compatibility
        if (!$className) {
            $loggerName = $name;
            if (!str_ends_with($loggerName, 'Log')) {
                $loggerName .= 'Log';
            }
            App::uses($loggerName, $plugin . 'Log/Engine');
            if (!class_exists($loggerName)) {
                throw new CakeLogException(__d('cake_dev', 'Could not load class %s', $originalLoggerName));
            }
            $className = $loggerName;
        }

        return $className;
    }
}
