<?php
/**
 * Behavior for binding management.
 *
 * Behavior to simplify manipulating a model's bindings when doing a find operation
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
 * @package       Cake.Model.Behavior
 * @since         CakePHP(tm) v 1.2.0.5669
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Model\Behavior;

use Cake\Model\Model;
use Cake\Model\ModelBehavior;
use Cake\Utility\Hash;

/**
 * Behavior to allow for dynamic and atomic manipulation of a Model's associations
 * used for a find call. Most useful for limiting the amount of associations and
 * data returned.
 *
 * @package       Cake.Model.Behavior
 * @link https://book.cakephp.org/2.0/en/core-libraries/behaviors/containable.html
 */
class ContainableBehavior extends ModelBehavior
{
    /**
     * Types of relationships available for models
     *
     * @var array
     */
    public $types = ['belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany'];

    /**
     * Runtime configuration for this behavior
     *
     * @var array
     */
    public $runtime = [];

    /**
     * Initiate behavior for the model using specified settings.
     *
     * Available settings:
     *
     * - recursive: (boolean, optional) set to true to allow containable to automatically
     *   determine the recursiveness level needed to fetch specified models,
     *   and set the model recursiveness to this level. setting it to false
     *   disables this feature. DEFAULTS TO: true
     * - notices: (boolean, optional) issues E_NOTICES for bindings referenced in a
     *   containable call that are not valid. DEFAULTS TO: true
     * - autoFields: (boolean, optional) auto-add needed fields to fetch requested
     *   bindings. DEFAULTS TO: true
     *
     * @param Model $model Model using the behavior
     * @param array $settings Settings to override for model.
     * @return void
     */
    public function setup(Model $model, $settings = [])
    {
        if (!isset($this->settings[$model->alias])) {
            $this->settings[$model->alias] = ['recursive' => true, 'notices' => true, 'autoFields' => true];
        }
        $this->settings[$model->alias] = array_merge($this->settings[$model->alias], $settings);
    }

    /**
     * Runs before a find() operation. Used to allow 'contain' setting
     * as part of the find call, like this:
     *
     * `Model->find('all', array('contain' => array('Model1', 'Model2')));`
     *
     * ```
     * Model->find('all', array('contain' => array(
     *  'Model1' => array('Model11', 'Model12'),
     *  'Model2',
     *  'Model3' => array(
     *      'Model31' => 'Model311',
     *      'Model32',
     *      'Model33' => array('Model331', 'Model332')
     * )));
     * ```
     *
     * @param Model $model Model using the behavior
     * @param array $query Query parameters as set by cake
     * @return array
     */
    public function beforeFind(Model $model, $query)
    {
        $reset = ($query['reset'] ?? true);
        $noContain = false;
        $contain = [];

        if (isset($this->runtime[$model->alias]['contain'])) {
            $noContain = empty($this->runtime[$model->alias]['contain']);
            $contain = $this->runtime[$model->alias]['contain'];
            unset($this->runtime[$model->alias]['contain']);
        }

        if (isset($query['contain'])) {
            $noContain = $noContain || empty($query['contain']);
            if ($query['contain'] !== false) {
                $contain = array_merge($contain, (array)$query['contain']);
            }
        }
        $noContain = $noContain && empty($contain);

        if ($noContain || empty($contain)) {
            if ($noContain) {
                $query['recursive'] = -1;
            }

            return $query;
        }
        if ((isset($contain[0]) && is_bool($contain[0])) || is_bool(end($contain))) {
            $reset = is_bool(end($contain))
                ? array_pop($contain)
                : array_shift($contain);
        }
        $containments = $this->containments($model, $contain);
        $map = $this->containmentsMap($containments);

        $mandatory = [];
        foreach ($containments['models'] as $_model) {
            $instance = $_model['instance'];
            $needed = $this->fieldDependencies($instance, $map, false);
            if (!empty($needed)) {
                $mandatory = array_merge($mandatory, $needed);
            }
            if ($contain) {
                $backupBindings = [];
                foreach ($this->types as $relation) {
                    if (!empty($instance->__backAssociation[$relation])) {
                        $backupBindings[$relation] = $instance->__backAssociation[$relation];
                    } else {
                        $backupBindings[$relation] = $instance->{$relation};
                    }
                }
                foreach ($this->types as $type) {
                    $unbind = [];
                    foreach ($instance->{$type} as $assoc => $options) {
                        if (!isset($_model['keep'][$assoc])) {
                            $unbind[] = $assoc;
                        }
                    }
                    if (!empty($unbind)) {
                        if (!$reset && empty($instance->__backOriginalAssociation)) {
                            $instance->__backOriginalAssociation = $backupBindings;
                        }
                        $instance->unbindModel([$type => $unbind], $reset);
                    }
                    foreach ($instance->{$type} as $assoc => $options) {
                        if (isset($_model['keep'][$assoc]) && !empty($_model['keep'][$assoc])) {
                            if (isset($_model['keep'][$assoc]['fields'])) {
                                $_model['keep'][$assoc]['fields'] = $this->fieldDependencies($containments['models'][$assoc]['instance'], $map, $_model['keep'][$assoc]['fields']);
                            }
                            if (!$reset && empty($instance->__backOriginalAssociation)) {
                                $instance->__backOriginalAssociation = $backupBindings;
                            } elseif ($reset) {
                                $instance->__backAssociation[$type] = $backupBindings[$type];
                            }
                            $instance->{$type}[$assoc] = array_merge($instance->{$type}[$assoc], $_model['keep'][$assoc]);
                        }
                        if (!$reset) {
                            $instance->__backInnerAssociation[] = $assoc;
                        }
                    }
                }
            }
        }

        if ($this->settings[$model->alias]['recursive']) {
            $query['recursive'] = isset($query['recursive']) ? max($query['recursive'], $containments['depth']) : $containments['depth'];
        }

        $autoFields = ($this->settings[$model->alias]['autoFields']
            && !in_array($model->findQueryType, ['list', 'count'])
            && !empty($query['fields']));

        if (!$autoFields) {
            return $query;
        }

        $query['fields'] = (array)$query['fields'];
        foreach (['hasOne', 'belongsTo'] as $type) {
            if (!empty($model->{$type})) {
                foreach ($model->{$type} as $assoc => $data) {
                    if ($model->useDbConfig === $model->{$assoc}->useDbConfig && !empty($data['fields'])) {
                        foreach ((array)$data['fields'] as $field) {
                            $query['fields'][] = (!str_contains($field, '.') ? $assoc . '.' : '') . $field;
                        }
                    }
                }
            }
        }

        if (!empty($mandatory[$model->alias])) {
            foreach ($mandatory[$model->alias] as $field) {
                if ($field === '--primaryKey--') {
                    $field = $model->primaryKey;
                } elseif (preg_match('/^.+\.\-\-[^-]+\-\-$/', $field)) {
                    [$modelName, $field] = explode('.', $field);
                    if ($model->useDbConfig === $model->{$modelName}->useDbConfig) {
                        $field = $modelName . '.' . (
                            $field === '--primaryKey--' ? $model->$modelName->primaryKey : $field
                        );
                    } else {
                        $field = null;
                    }
                }
                if ($field !== null) {
                    $query['fields'][] = $field;
                }
            }
        }
        $query['fields'] = array_unique($query['fields']);

        return $query;
    }

    /**
     * Unbinds all relations from a model except the specified ones. Calling this function without
     * parameters unbinds all related models.
     *
     * @param Model $model Model on which binding restriction is being applied
     * @param mixed ...$args Additional contain parameters
     * @return void
     * @link https://book.cakephp.org/2.0/en/core-libraries/behaviors/containable.html#using-containable
     */
    public function contain(Model $model, ...$args): void
    {
        $contain = call_user_func_array('am', $args);
        $this->runtime[$model->alias]['contain'] = $contain;
    }

    /**
     * Permanently restore the original binding settings of given model, useful
     * for restoring the bindings after using 'reset' => false as part of the
     * contain call.
     *
     * @param Model $model Model on which to reset bindings
     * @return void
     */
    public function resetBindings(Model $model)
    {
        if (!empty($model->__backOriginalAssociation)) {
            $model->__backAssociation = $model->__backOriginalAssociation;
            unset($model->__backOriginalAssociation);
        }
        $model->resetAssociations();
        if (!empty($model->__backInnerAssociation)) {
            $assocs = $model->__backInnerAssociation;
            $model->__backInnerAssociation = [];
            foreach ($assocs as $currentModel) {
                $this->resetBindings($model->$currentModel);
            }
        }
    }

    /**
     * Process containments for model.
     *
     * @param Model $model Model on which binding restriction is being applied
     * @param array $contain Parameters to use for restricting this model
     * @param array $containments Current set of containments
     * @param bool $throwErrors Whether non-existent bindings show throw errors
     * @return array Containments
     */
    public function containments(Model $model, $contain, $containments = [], $throwErrors = null)
    {
        $options = ['className', 'joinTable', 'with', 'foreignKey', 'associationForeignKey', 'conditions', 'fields', 'order', 'limit', 'offset', 'unique', 'finderQuery'];
        $keep = [];
        if ($throwErrors === null) {
            $throwErrors = (empty($this->settings[$model->alias]) ? true : $this->settings[$model->alias]['notices']);
        }
        foreach ((array)$contain as $name => $children) {
            if (is_numeric($name)) {
                $name = $children;
                $children = [];
            }
            if (preg_match('/(?<!\.)\(/', $name)) {
                $name = str_replace('(', '.(', $name);
            }
            if (str_contains($name, '.')) {
                $chain = explode('.', $name);
                $name = array_shift($chain);
                $children = [implode('.', $chain) => $children];
            }

            $children = (array)$children;
            foreach ($children as $key => $val) {
                if (is_string($key) && is_string($val) && !in_array($key, $options, true)) {
                    $children[$key] = (array)$val;
                }
            }

            $keys = array_keys($children);
            if ($keys && isset($children[0])) {
                $keys = array_merge(array_values($children), $keys);
            }

            foreach ($keys as $i => $key) {
                if (is_array($key)) {
                    continue;
                }
                $optionKey = in_array($key, $options, true);
                if (!$optionKey && is_string($key) && preg_match('/^[a-z(]/', $key) && (!isset($model->{$key}) || !is_object($model->{$key}))) {
                    $option = 'fields';
                    $val = [$key];
                    if ($key[0] === '(') {
                        $val = preg_split('/\s*,\s*/', substr($key, 1, -1));
                    } elseif (preg_match('/ASC|DESC$/', $key)) {
                        $option = 'order';
                        $val = $model->{$name}->alias . '.' . $key;
                    } elseif (preg_match('/[ =!]/', $key)) {
                        $option = 'conditions';
                        $val = $model->{$name}->alias . '.' . $key;
                    }
                    $children[$option] = is_array($val) ? $val : [$val];
                    $newChildren = null;
                    if (!empty($name) && !empty($children[$key])) {
                        $newChildren = $children[$key];
                    }
                    unset($children[$key], $children[$i]);
                    $key = $option;
                    $optionKey = true;
                    if (!empty($newChildren)) {
                        $children = Hash::merge($children, $newChildren);
                    }
                }
                if ($optionKey && isset($children[$key])) {
                    if (!empty($keep[$name][$key]) && is_array($keep[$name][$key])) {
                        $keep[$name][$key] = array_merge(($keep[$name][$key] ?? []), (array)$children[$key]);
                    } else {
                        $keep[$name][$key] = $children[$key];
                    }
                    unset($children[$key]);
                }
            }

            if (!isset($model->{$name}) || !is_object($model->{$name})) {
                if ($throwErrors) {
                    trigger_error(__d('cake_dev', 'Model "%s" is not associated with model "%s"', $model->alias, $name), E_USER_WARNING);
                }
                continue;
            }

            $containments = $this->containments($model->{$name}, $children, $containments);
            $depths[] = $containments['depth'] + 1;
            if (!isset($keep[$name])) {
                $keep[$name] = [];
            }
        }

        if (!isset($containments['models'][$model->alias])) {
            $containments['models'][$model->alias] = ['keep' => [], 'instance' => &$model];
        }

        $containments['models'][$model->alias]['keep'] = array_merge($containments['models'][$model->alias]['keep'], $keep);
        $containments['depth'] = empty($depths) ? 0 : max($depths);

        return $containments;
    }

    /**
     * Calculate needed fields to fetch the required bindings for the given model.
     *
     * @param Model $model Model
     * @param array $map Map of relations for given model
     * @param array|bool $fields If array, fields to initially load, if false use $Model as primary model
     * @return array Fields
     */
    public function fieldDependencies(Model $model, $map, $fields = [])
    {
        if ($fields === false) {
            $fields = [];
            foreach ($map as $parent => $children) {
                foreach ($children as $type => $bindings) {
                    foreach ($bindings as $dependency) {
                        if ($type === 'hasAndBelongsToMany') {
                            $fields[$parent][] = '--primaryKey--';
                        } elseif ($type === 'belongsTo') {
                            $fields[$parent][] = $dependency . '.--primaryKey--';
                        }
                    }
                }
            }

            return $fields;
        }
        if (empty($map[$model->alias])) {
            return $fields;
        }
        foreach ($map[$model->alias] as $type => $bindings) {
            foreach ($bindings as $dependency) {
                $innerFields = [];
                switch ($type) {
                    case 'belongsTo':
                        $fields[] = $model->{$type}[$dependency]['foreignKey'];
                        break;
                    case 'hasOne':
                    case 'hasMany':
                        $innerFields[] = $model->$dependency->primaryKey;
                        $fields[] = $model->primaryKey;
                        break;
                }
                if (!empty($innerFields) && !empty($model->{$type}[$dependency]['fields'])) {
                    $model->{$type}[$dependency]['fields'] = array_unique(array_merge($model->{$type}[$dependency]['fields'], $innerFields));
                }
            }
        }

        return array_unique($fields);
    }

    /**
     * Build the map of containments
     *
     * @param array $containments Containments
     * @return array Built containments
     */
    public function containmentsMap($containments)
    {
        $map = [];
        foreach ($containments['models'] as $name => $model) {
            $instance = $model['instance'];
            foreach ($this->types as $type) {
                foreach ($instance->{$type} as $assoc => $options) {
                    if (isset($model['keep'][$assoc])) {
                        $map[$name][$type] = isset($map[$name][$type]) ? array_merge($map[$name][$type], (array)$assoc) : (array)$assoc;
                    }
                }
            }
        }

        return $map;
    }
}
