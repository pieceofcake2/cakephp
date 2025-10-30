<?php
/**
 * Task collection is used as a registry for loaded tasks and handles loading
 * and constructing task class objects.
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
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Console;

use Cake\Core\App;
use Cake\Error\MissingTaskException;
use Cake\Utility\ObjectCollection;

/**
 * Collection object for Tasks. Provides features
 * for lazily loading tasks, and firing callbacks on loaded tasks.
 *
 * @package       Cake.Console
 */
class TaskCollection extends ObjectCollection
{
    /**
     * Shell to use to set params to tasks.
     *
     * @var Shell
     */
    protected Shell $_Shell;

    /**
     * The directory inside each shell path that contains tasks.
     *
     * @var string
     */
    public string $taskPathPrefix = 'tasks/';

    /**
     * @var array<Shell>
     */
    protected array $_loaded = [];

    /**
     * Constructor
     *
     * @param Shell $shell The shell this task collection is attached to.
     */
    public function __construct(Shell $shell)
    {
        $this->_Shell = $shell;
    }

    /**
     * Loads/constructs a task. Will return the instance in the registry if it already exists.
     *
     * You can alias your task as an existing task by setting the 'className' key, i.e.,
     * ```
     * public $tasks = [
     *     'DbConfig' => [
     *         'className' => 'Bakeplus.DbConfigure',
     *     ];
     * ];
     * ```
     * All calls to the `DbConfig` task would use `DbConfigure` found in the `Bakeplus` plugin instead.
     *
     * @param string $name Task name to load
     * @param array $options Settings for the task.
     * @return Shell A task object, Either the existing loaded task or a new one.
     * @throws MissingTaskException when the task could not be found
     */
    public function load($name, array $options = []): Shell
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

        $taskClass = App::className($name, 'Console/Command/Task', 'Task');

        if (!$taskClass) {
            throw new MissingTaskException([
                'class' => $_name . 'Task',
                'plugin' => $plugin ? substr($plugin, 0, -1) : null,
            ]);
        }

        $this->_loaded[$alias] = new $taskClass(
            $this->_Shell->stdout,
            $this->_Shell->stderr,
            $this->_Shell->stdin,
        );

        return $this->_loaded[$alias];
    }
}
