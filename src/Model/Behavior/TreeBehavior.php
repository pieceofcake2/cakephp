<?php
/**
 * Tree behavior class.
 *
 * Enables a model object to act as a node-based tree.
 *
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Model.Behavior
 * @since         CakePHP v 1.2.0.4487
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Model\Behavior;

use Cake\Model\ConnectionManager;
use Cake\Model\Model;
use Cake\Model\ModelBehavior;
use Cake\Utility\Hash;

/**
 * Tree Behavior.
 *
 * Enables a model object to act as a node-based tree. Using Modified Preorder Tree Traversal
 *
 * @see http://en.wikipedia.org/wiki/Tree_traversal
 * @package       Cake.Model.Behavior
 * @link https://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html
 */
class TreeBehavior extends ModelBehavior
{
    /**
     * Errors
     *
     * @var array
     */
    public $errors = [];

    /**
     * Defaults
     *
     * @var array
     */
    protected $_defaults = [
        'parent' => 'parent_id', 'left' => 'lft', 'right' => 'rght', 'level' => null,
        'scope' => '1 = 1', 'type' => 'nested', '__parentChange' => false, 'recursive' => -1,
    ];

    /**
     * Used to preserve state between delete callbacks.
     *
     * @var array
     */
    protected $_deletedRow = [];

    /**
     * Initiate Tree behavior
     *
     * @param Model $model using this behavior of model
     * @param array $config array of configuration settings.
     * @return void
     */
    public function setup(Model $model, $config = [])
    {
        if (isset($config[0])) {
            $config['type'] = $config[0];
            unset($config[0]);
        }
        $settings = $config + $this->_defaults;

        if (in_array($settings['scope'], $model->getAssociated('belongsTo'))) {
            $data = $model->getAssociated($settings['scope']);
            $Parent = $model->{$settings['scope']};
            $settings['scope'] = $model->escapeField($data['foreignKey']) . ' = ' . $Parent->escapeField();
            $settings['recursive'] = 0;
        }
        $this->settings[$model->alias] = $settings;
    }

    /**
     * After save method. Called after all saves
     *
     * Overridden to transparently manage setting the lft and rght fields if and only if the parent field is included in the
     * parameters to be saved.
     *
     * @param Model $model Model using this behavior.
     * @param bool $created indicates whether the node just saved was created or updated
     * @param array $options Options passed from Model::save().
     * @return bool|null true on success, false on failure
     */
    public function afterSave(Model $model, bool $created, array $options = []): ?bool
    {
        extract($this->settings[$model->alias]);
        if ($created) {
            if (isset($model->data[$model->alias][$parent]) && $model->data[$model->alias][$parent]) {
                $this->_setParent($model, $model->data[$model->alias][$parent], $created);
            }
        } elseif ($this->settings[$model->alias]['__parentChange']) {
            $this->settings[$model->alias]['__parentChange'] = false;
            if ($level) {
                $this->_setChildrenLevel($model, $model->id);
            }
            $this->_setParent($model, $model->data[$model->alias][$parent]);
        }

        return null;
    }

    /**
     * Set level for descendents.
     *
     * @param Model $model Model using this behavior.
     * @param string|int $id Record ID
     * @return void
     */
    protected function _setChildrenLevel(Model $model, $id)
    {
        $settings = $this->settings[$model->alias];
        $primaryKey = $model->primaryKey;
        $depths = [$id => (int)$model->data[$model->alias][$settings['level']]];

        $children = $this->children(
            $model,
            $id,
            false,
            [$primaryKey, $settings['parent'], $settings['level']],
            $settings['left'],
            null,
            1,
            -1,
        );

        foreach ($children as $node) {
            $parentIdValue = $node[$model->alias][$settings['parent']];
            $depth = (int)$depths[$parentIdValue] + 1;
            $depths[$node[$model->alias][$primaryKey]] = $depth;

            $model->updateAll(
                [$model->escapeField($settings['level']) => $depth],
                [$model->escapeField($primaryKey) => $node[$model->alias][$primaryKey]],
            );
        }
    }

    /**
     * Runs before a find() operation
     *
     * @param Model $model Model using the behavior
     * @param array $query Query parameters as set by cake
     * @return array|bool|null
     */
    public function beforeFind(Model $model, array $query): array|bool|null
    {
        if ($model->findQueryType === 'threaded' && !isset($query['parent'])) {
            $query['parent'] = $this->settings[$model->alias]['parent'];
        }

        return $query;
    }

    /**
     * Stores the record about to be deleted.
     *
     * This is used to delete child nodes in the afterDelete.
     *
     * @param Model $model Model using this behavior.
     * @param bool $cascade If true records that depend on this record will also be deleted
     * @return bool|null
     */
    public function beforeDelete(Model $model, $cascade = true): ?bool
    {
        extract($this->settings[$model->alias]);
        $data = $model->find('first', [
            'conditions' => [$model->escapeField($model->primaryKey) => $model->id],
            'fields' => [$model->escapeField($left), $model->escapeField($right)],
            'order' => false,
            'recursive' => -1]);
        if ($data) {
            $this->_deletedRow[$model->alias] = current($data);
        }

        return true;
    }

    /**
     * After delete method.
     *
     * Will delete the current node and all children using the deleteAll method and sync the table
     *
     * @param Model $model Model using this behavior
     * @return bool|null true to continue, false to abort the delete
     */
    public function afterDelete(Model $model): ?bool
    {
        extract($this->settings[$model->alias]);
        $data = $this->_deletedRow[$model->alias];
        $this->_deletedRow[$model->alias] = null;

        if (!$data[$right] || !$data[$left]) {
            return true;
        }
        $diff = $data[$right] - $data[$left] + 1;

        if ($diff > 2) {
            if (is_string($scope)) {
                $scope = [$scope];
            }
            $scope[][$model->escapeField($left) . ' BETWEEN ? AND ?'] = [$data[$left] + 1, $data[$right] - 1];
            $model->deleteAll($scope);
        }
        $this->_sync($model, $diff, '-', '> ' . $data[$right]);

        return true;
    }

    /**
     * Before save method. Called before all saves
     *
     * Overridden to transparently manage setting the lft and rght fields if and only if the parent field is included in the
     * parameters to be saved. For newly created nodes with NO parent the left and right field values are set directly by
     * this method bypassing the setParent logic.
     *
     * @param Model $model Model using this behavior
     * @param array $options Options passed from Model::save().
     * @return bool|null true to continue, false to abort the save
     * @see Model::save()
     */
    public function beforeSave(Model $model, array $options = []): ?bool
    {
        extract($this->settings[$model->alias]);

        $this->_addToWhitelist($model, [$left, $right]);
        if ($level) {
            $this->_addToWhitelist($model, $level);
        }
        $parentIsSet = array_key_exists($parent, $model->data[$model->alias]);

        if (!$model->id || !$model->exists($model->getID())) {
            if ($parentIsSet && $model->data[$model->alias][$parent]) {
                $parentNode = $this->_getNode($model, $model->data[$model->alias][$parent]);
                if (!$parentNode) {
                    return false;
                }

                $model->data[$model->alias][$left] = 0;
                $model->data[$model->alias][$right] = 0;
                if ($level) {
                    $model->data[$model->alias][$level] = (int)$parentNode[$model->alias][$level] + 1;
                }

                return true;
            }

            $edge = $this->_getMax($model, $scope, $right, $recursive);
            $model->data[$model->alias][$left] = $edge + 1;
            $model->data[$model->alias][$right] = $edge + 2;
            if ($level) {
                $model->data[$model->alias][$level] = 0;
            }

            return true;
        }

        if ($parentIsSet) {
            if ($model->data[$model->alias][$parent] != $model->field($parent)) {
                $this->settings[$model->alias]['__parentChange'] = true;
            }
            if (!$model->data[$model->alias][$parent]) {
                $model->data[$model->alias][$parent] = null;
                $this->_addToWhitelist($model, $parent);
                if ($level) {
                    $model->data[$model->alias][$level] = 0;
                }

                return true;
            }

            $values = $this->_getNode($model, $model->id);
            if (empty($values)) {
                return false;
            }
            [$node] = array_values($values);

            $parentNode = $this->_getNode($model, $model->data[$model->alias][$parent]);
            if (!$parentNode) {
                return false;
            }
            [$parentNode] = array_values($parentNode);

            if (($node[$left] < $parentNode[$left]) && ($parentNode[$right] < $node[$right])) {
                return false;
            }
            if ($node[$model->primaryKey] === $parentNode[$model->primaryKey]) {
                return false;
            }
            if ($level) {
                $model->data[$model->alias][$level] = (int)$parentNode[$level] + 1;
            }
        }

        return true;
    }

    /**
     * Returns a single node from the tree from its primary key
     *
     * @param Model $model Model using this behavior
     * @param string|int $id The ID of the record to read
     * @return array|bool The record read or false
     */
    protected function _getNode(Model $model, $id)
    {
        $settings = $this->settings[$model->alias];
        $fields = [$model->primaryKey, $settings['parent'], $settings['left'], $settings['right']];
        if ($settings['level']) {
            $fields[] = $settings['level'];
        }

        return $model->find('first', [
            'conditions' => [$model->escapeField() => $id],
            'fields' => $fields,
            'recursive' => $settings['recursive'],
            'order' => false,
        ]);
    }

    /**
     * Get the number of child nodes
     *
     * If the direct parameter is set to true, only the direct children are counted (based upon the parent_id field)
     * If false is passed for the id parameter, all top level nodes are counted, or all nodes are counted.
     *
     * @param Model $model Model using this behavior
     * @param string|int|bool $id The ID of the record to read or false to read all top level nodes
     * @param bool $direct whether to count direct, or all, children
     * @return int number of child nodes
     * @link https://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::childCount
     */
    public function childCount(Model $model, $id = null, $direct = false)
    {
        if (is_array($id)) {
            extract(array_merge(['id' => null], $id));
        }
        if ($id === null && $model->id) {
            $id = $model->id;
        } elseif (!$id) {
            $id = null;
        }
        extract($this->settings[$model->alias]);

        if ($direct) {
            return $model->find('count', ['conditions' => [$scope, $model->escapeField($parent) => $id]]);
        }

        if ($id === null) {
            return $model->find('count', ['conditions' => $scope]);
        } elseif ($model->id === $id && isset($model->data[$model->alias][$left]) && isset($model->data[$model->alias][$right])) {
            $data = $model->data[$model->alias];
        } else {
            $data = $this->_getNode($model, $id);
            if (!$data) {
                return 0;
            }
            $data = $data[$model->alias];
        }

        return ($data[$right] - $data[$left] - 1) / 2;
    }

    /**
     * Get the child nodes of the current model
     *
     * If the direct parameter is set to true, only the direct children are returned (based upon the parent_id field)
     * If false is passed for the id parameter, top level, or all (depending on direct parameter appropriate) are counted.
     *
     * @param Model $model Model using this behavior
     * @param string|int $id The ID of the record to read
     * @param bool $direct whether to return only the direct, or all, children
     * @param array|string $fields Either a single string of a field name, or an array of field names
     * @param string $order SQL ORDER BY conditions (e.g. "price DESC" or "name ASC") defaults to the tree order
     * @param int $limit SQL LIMIT clause, for calculating items per page.
     * @param int $page Page number, for accessing paged data
     * @param int $recursive The number of levels deep to fetch associated records
     * @return array Array of child nodes
     * @link https://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::children
     */
    public function children(Model $model, $id = null, $direct = false, $fields = null, $order = null, $limit = null, $page = 1, $recursive = null)
    {
        $options = [];
        if (is_array($id)) {
            $options = $this->_getOptions($id);
            extract(array_merge(['id' => null], $id));
        }
        $overrideRecursive = $recursive;

        if ($id === null && $model->id) {
            $id = $model->id;
        } elseif (!$id) {
            $id = null;
        }

        extract($this->settings[$model->alias]);

        if ($overrideRecursive !== null) {
            $recursive = $overrideRecursive;
        }
        if (!$order) {
            $order = $model->escapeField($left) . ' asc';
        }
        if ($direct) {
            $conditions = [$scope, $model->escapeField($parent) => $id];

            return $model->find('all', compact('conditions', 'fields', 'order', 'limit', 'page', 'recursive'));
        }

        if (!$id) {
            $conditions = $scope;
        } else {
            $result = array_values((array)$model->find('first', [
                'conditions' => [$scope, $model->escapeField() => $id],
                'fields' => [$left, $right],
                'recursive' => $recursive,
                'order' => false,
            ]));

            if (empty($result) || !isset($result[0])) {
                return [];
            }
            $conditions = [$scope,
                $model->escapeField($right) . ' <' => $result[0][$right],
                $model->escapeField($left) . ' >' => $result[0][$left],
            ];
        }
        $options = array_merge(compact(
            'conditions',
            'fields',
            'order',
            'limit',
            'page',
            'recursive',
        ), $options);

        return $model->find('all', $options);
    }

    /**
     * A convenience method for returning a hierarchical array used for HTML select boxes
     *
     * @param Model $model Model using this behavior
     * @param array|string $conditions SQL conditions as a string or as an array('field' =>'value',...)
     * @param string $keyPath A string path to the key, i.e. "{n}.Post.id"
     * @param string $valuePath A string path to the value, i.e. "{n}.Post.title"
     * @param string $spacer The character or characters which will be repeated
     * @param int $recursive The number of levels deep to fetch associated records
     * @return array An associative array of records, where the id is the key, and the display field is the value
     * @link https://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::generateTreeList
     */
    public function generateTreeList(Model $model, $conditions = null, $keyPath = null, $valuePath = null, $spacer = '_', $recursive = null)
    {
        $overrideRecursive = $recursive;
        extract($this->settings[$model->alias]);
        if ($overrideRecursive !== null) {
            $recursive = $overrideRecursive;
        }

        $fields = null;
        if (!$keyPath && !$valuePath && $model->hasField($model->displayField)) {
            $fields = [$model->primaryKey, $model->displayField, $left, $right];
        }

        $conditions = (array)$conditions;
        if ($scope) {
            $conditions[] = $scope;
        }

        $order = $model->escapeField($left) . ' asc';
        $results = $model->find('all', compact('conditions', 'fields', 'order', 'recursive'));

        return $this->formatTreeList($model, $results, compact('keyPath', 'valuePath', 'spacer'));
    }

    /**
     * Formats result of a find() call to a hierarchical array used for HTML select boxes.
     *
     * Note that when using your own find() call this expects the order to be "left" field asc in order
     * to generate the same result as using generateTreeList() directly.
     *
     * Options:
     *
     * - 'keyPath': A string path to the key, i.e. "{n}.Post.id"
     * - 'valuePath': A string path to the value, i.e. "{n}.Post.title"
     * - 'spacer': The character or characters which will be repeated
     *
     * @param Model $model Model using this behavior
     * @param array $results Result array of a find() call
     * @param array $options Options
     * @return array An associative array of records, where the id is the key, and the display field is the value
     */
    public function formatTreeList(Model $model, array $results, array $options = [])
    {
        if (empty($results)) {
            return [];
        }
        $defaults = [
            'keyPath' => null,
            'valuePath' => null,
            'spacer' => '_',
        ];
        $options += $defaults;

        extract($this->settings[$model->alias]);

        if (!$options['keyPath']) {
            $options['keyPath'] = '{n}.' . $model->alias . '.' . $model->primaryKey;
        }

        if (!$options['valuePath']) {
            $options['valuePath'] = ['%s%s', '{n}.tree_prefix', '{n}.' . $model->alias . '.' . $model->displayField];
        } elseif (is_string($options['valuePath'])) {
            $options['valuePath'] = ['%s%s', '{n}.tree_prefix', $options['valuePath']];
        } else {
            array_unshift($options['valuePath'], '%s' . $options['valuePath'][0], '{n}.tree_prefix');
        }

        $stack = [];

        foreach ($results as $i => $result) {
            $count = count($stack);
            while ($stack && ($stack[$count - 1] < $result[$model->alias][$right])) {
                array_pop($stack);
                $count--;
            }
            $results[$i]['tree_prefix'] = str_repeat($options['spacer'], $count);
            $stack[] = $result[$model->alias][$right];
        }

        return Hash::combine($results, $options['keyPath'], $options['valuePath']);
    }

    /**
     * Get the parent node
     *
     * reads the parent id and returns this node
     *
     * @param Model $model Model using this behavior
     * @param string|int $id The ID of the record to read
     * @param array|string $fields Fields to get
     * @param int $recursive The number of levels deep to fetch associated records
     * @return array|bool Array of data for the parent node
     * @link https://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::getParentNode
     */
    public function getParentNode(Model $model, $id = null, $fields = null, $recursive = null)
    {
        $options = [];
        if (is_array($id)) {
            $options = $this->_getOptions($id);
            extract(array_merge(['id' => null], $id));
        }
        $overrideRecursive = $recursive;
        if (empty($id)) {
            $id = $model->id;
        }
        extract($this->settings[$model->alias]);
        if ($overrideRecursive !== null) {
            $recursive = $overrideRecursive;
        }
        $parentId = $model->find('first', [
            'conditions' => [$model->primaryKey => $id],
            'fields' => [$parent],
            'order' => false,
            'recursive' => -1,
        ]);

        if ($parentId) {
            $parentId = $parentId[$model->alias][$parent];
            $options = array_merge([
                'conditions' => [$model->escapeField() => $parentId],
                'fields' => $fields,
                'order' => false,
                'recursive' => $recursive,
            ], $options);
            $parent = $model->find('first', $options);

            return $parent;
        }

        return false;
    }

    /**
     * Convenience method to create default find() options from $arg when it is an
     * associative array.
     *
     * @param array $arg Array
     * @return array Options array
     */
    protected function _getOptions($arg)
    {
        return count(array_filter(array_keys($arg), 'is_string')) > 0 ?
            $arg :
            [];
    }

    /**
     * Get the path to the given node
     *
     * @param Model $model Model using this behavior
     * @param string|int|null $id The ID of the record to read
     * @param array|string|null $fields Either a single string of a field name, or an array of field names
     * @param int|null $recursive The number of levels deep to fetch associated records
     * @return array Array of nodes from top most parent to current node
     * @link https://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::getPath
     */
    public function getPath(Model $model, $id = null, $fields = null, $recursive = null)
    {
        $options = [];
        if (is_array($id)) {
            $options = $this->_getOptions($id);
            extract(array_merge(['id' => null], $id));
        }

        if (!empty($options)) {
            $fields = null;
            if (!empty($options['fields'])) {
                $fields = $options['fields'];
            }
            if (!empty($options['recursive'])) {
                $recursive = $options['recursive'];
            }
        }
        $overrideRecursive = $recursive;
        if (empty($id)) {
            $id = $model->id;
        }
        extract($this->settings[$model->alias]);
        if ($overrideRecursive !== null) {
            $recursive = $overrideRecursive;
        }
        $result = $model->find('first', [
            'conditions' => [$model->escapeField() => $id],
            'fields' => [$left, $right],
            'order' => false,
            'recursive' => $recursive,
        ]);
        if ($result) {
            $result = array_values($result);
        } else {
            return [];
        }
        $item = $result[0];
        $options = array_merge([
            'conditions' => [
                $scope,
                $model->escapeField($left) . ' <=' => $item[$left],
                $model->escapeField($right) . ' >=' => $item[$right],
            ],
            'fields' => $fields,
            'order' => [$model->escapeField($left) => 'asc'],
            'recursive' => $recursive,
        ], $options);
        $results = $model->find('all', $options);

        return $results;
    }

    /**
     * Reorder the node without changing the parent.
     *
     * If the node is the last child, or is a top level node with no subsequent node this method will return false
     *
     * @param Model $model Model using this behavior
     * @param string|int|null $id The ID of the record to move
     * @param int|bool $number how many places to move the node or true to move to last position
     * @return bool true on success, false on failure
     * @link https://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::moveDown
     */
    public function moveDown(Model $model, $id = null, $number = 1)
    {
        if (is_array($id)) {
            extract(array_merge(['id' => null], $id));
        }
        if (!$number) {
            return false;
        }
        if (empty($id)) {
            $id = $model->id;
        }
        extract($this->settings[$model->alias]);
        [$node] = array_values($this->_getNode($model, $id));
        if ($node[$parent]) {
            [$parentNode] = array_values($this->_getNode($model, $node[$parent]));
            if ($node[$right] + 1 == $parentNode[$right]) {
                return false;
            }
        }
        $nextNode = $model->find('first', [
            'conditions' => [$scope, $model->escapeField($left) => $node[$right] + 1],
            'fields' => [$model->primaryKey, $left, $right],
            'order' => false,
            'recursive' => $recursive],);
        if ($nextNode) {
            [$nextNode] = array_values($nextNode);
        } else {
            return false;
        }
        $edge = $this->_getMax($model, $scope, $right, $recursive);
        $this->_sync($model, $edge - $node[$left] + 1, '+', 'BETWEEN ' . $node[$left] . ' AND ' . $node[$right]);
        $this->_sync($model, $nextNode[$left] - $node[$left], '-', 'BETWEEN ' . $nextNode[$left] . ' AND ' . $nextNode[$right]);
        $this->_sync($model, $edge - $node[$left] - ($nextNode[$right] - $nextNode[$left]), '-', '> ' . $edge);

        if (is_int($number)) {
            $number--;
        }
        if ($number) {
            $this->moveDown($model, $id, $number);
        }

        return true;
    }

    /**
     * Reorder the node without changing the parent.
     *
     * If the node is the first child, or is a top level node with no previous node this method will return false
     *
     * @param Model $model Model using this behavior
     * @param string|int|null $id The ID of the record to move
     * @param int|bool $number how many places to move the node, or true to move to first position
     * @return bool true on success, false on failure
     * @link https://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::moveUp
     */
    public function moveUp(Model $model, $id = null, $number = 1)
    {
        if (is_array($id)) {
            extract(array_merge(['id' => null], $id));
        }
        if (!$number) {
            return false;
        }
        if (empty($id)) {
            $id = $model->id;
        }
        extract($this->settings[$model->alias]);
        [$node] = array_values($this->_getNode($model, $id));
        if ($node[$parent]) {
            [$parentNode] = array_values($this->_getNode($model, $node[$parent]));
            if ($node[$left] - 1 == $parentNode[$left]) {
                return false;
            }
        }
        $previousNode = $model->find('first', [
            'conditions' => [$scope, $model->escapeField($right) => $node[$left] - 1],
            'fields' => [$model->primaryKey, $left, $right],
            'order' => false,
            'recursive' => $recursive,
        ]);

        if ($previousNode) {
            [$previousNode] = array_values($previousNode);
        } else {
            return false;
        }
        $edge = $this->_getMax($model, $scope, $right, $recursive);
        $this->_sync($model, $edge - $previousNode[$left] + 1, '+', 'BETWEEN ' . $previousNode[$left] . ' AND ' . $previousNode[$right]);
        $this->_sync($model, $node[$left] - $previousNode[$left], '-', 'BETWEEN ' . $node[$left] . ' AND ' . $node[$right]);
        $this->_sync($model, $edge - $previousNode[$left] - ($node[$right] - $node[$left]), '-', '> ' . $edge);
        if (is_int($number)) {
            $number--;
        }
        if ($number) {
            $this->moveUp($model, $id, $number);
        }

        return true;
    }

    /**
     * Recover a corrupted tree
     *
     * The mode parameter is used to specify the source of info that is valid/correct. The opposite source of data
     * will be populated based upon that source of info. E.g. if the MPTT fields are corrupt or empty, with the $mode
     * 'parent' the values of the parent_id field will be used to populate the left and right fields. The missingParentAction
     * parameter only applies to "parent" mode and determines what to do if the parent field contains an id that is not present.
     *
     * @param Model $model Model using this behavior
     * @param string $mode parent or tree
     * @param string|int|null $missingParentAction 'return' to do nothing and return, 'delete' to
     * delete, or the id of the parent to set as the parent_id
     * @return bool true on success, false on failure
     * @link https://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::recover
     */
    public function recover(Model $model, $mode = 'parent', $missingParentAction = null)
    {
        if (is_array($mode)) {
            extract(array_merge(['mode' => 'parent'], $mode));
        }
        extract($this->settings[$model->alias]);
        $model->recursive = $recursive;
        if ($mode === 'parent') {
            $model->bindModel(['belongsTo' => ['VerifyParent' => [
                'className' => $model->name,
                'foreignKey' => $parent,
                'fields' => [$model->primaryKey, $left, $right, $parent],
            ]]]);
            $missingParents = $model->find('list', [
                'recursive' => 0,
                'conditions' => [$scope, [
                    'NOT' => [$model->escapeField($parent) => null], $model->VerifyParent->escapeField() => null,
                ]],
                'order' => false,
            ]);
            $model->unbindModel(['belongsTo' => ['VerifyParent']]);
            if ($missingParents) {
                if ($missingParentAction === 'return') {
                    foreach ($missingParents as $id => $display) {
                        $this->errors[] = 'cannot find the parent for ' . $model->alias . ' with id ' . $id . '(' . $display . ')';
                    }

                    return false;
                } elseif ($missingParentAction === 'delete') {
                    $model->deleteAll([$model->escapeField($model->primaryKey) => array_flip($missingParents)], false);
                } else {
                    $model->updateAll([$model->escapeField($parent) => $missingParentAction], [$model->escapeField($model->primaryKey) => array_flip($missingParents)]);
                }
            }

            $this->_recoverByParentId($model);
        } else {
            $db = ConnectionManager::getDataSource($model->useDbConfig);
            foreach ($model->find('all', ['conditions' => $scope, 'fields' => [$model->primaryKey, $parent], 'order' => $left]) as $array) {
                $path = $this->getPath($model, $array[$model->alias][$model->primaryKey]);
                $parentId = null;
                if (count($path) > 1) {
                    $parentId = $path[count($path) - 2][$model->alias][$model->primaryKey];
                }
                $model->updateAll([$parent => $db->value($parentId, $parent)], [$model->escapeField() => $array[$model->alias][$model->primaryKey]]);
            }
        }

        return true;
    }

    /**
     * _recoverByParentId
     *
     * Recursive helper function used by recover
     *
     * @param Model $model Model instance.
     * @param int $counter Counter
     * @param string|int|null $parentId Parent record Id
     * @return int counter
     */
    protected function _recoverByParentId(Model $model, $counter = 1, $parentId = null)
    {
        $params = [
            'conditions' => [
                $this->settings[$model->alias]['parent'] => $parentId,
            ],
            'fields' => [$model->primaryKey],
            'page' => 1,
            'limit' => 100,
            'order' => [$model->primaryKey],
        ];

        $scope = $this->settings[$model->alias]['scope'];
        if ($scope && ($scope !== '1 = 1' && $scope !== true)) {
            $params['conditions'][] = $scope;
        }

        $children = $model->find('all', $params);
        $hasChildren = (bool)$children;

        if ($parentId !== null) {
            if ($hasChildren) {
                $model->updateAll(
                    [$this->settings[$model->alias]['left'] => $counter],
                    [$model->escapeField() => $parentId],
                );
                $counter++;
            } else {
                $model->updateAll(
                    [
                        $this->settings[$model->alias]['left'] => $counter,
                        $this->settings[$model->alias]['right'] => $counter + 1,
                    ],
                    [$model->escapeField() => $parentId],
                );
                $counter += 2;
            }
        }

        while ($children) {
            foreach ($children as $row) {
                $counter = $this->_recoverByParentId($model, $counter, $row[$model->alias][$model->primaryKey]);
            }

            if (count($children) !== $params['limit']) {
                break;
            }
            $params['page']++;
            $children = $model->find('all', $params);
        }

        if ($parentId !== null && $hasChildren) {
            $model->updateAll(
                [$this->settings[$model->alias]['right'] => $counter],
                [$model->escapeField() => $parentId],
            );
            $counter++;
        }

        return $counter;
    }

    /**
     * Reorder method.
     *
     * Reorders the nodes (and child nodes) of the tree according to the field and direction specified in the parameters.
     * This method does not change the parent of any node.
     *
     * Requires a valid tree, by default it verifies the tree before beginning.
     *
     * Options:
     *
     * - 'id' id of record to use as top node for reordering
     * - 'field' Which field to use in reordering defaults to displayField
     * - 'order' Direction to order either DESC or ASC (defaults to ASC)
     * - 'verify' Whether or not to verify the tree before reorder. defaults to true.
     *
     * @param Model $model Model using this behavior
     * @param array $options array of options to use in reordering.
     * @return bool true on success, false on failure
     * @link https://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::reorder
     */
    public function reorder(Model $model, $options = [])
    {
        $options += ['id' => null, 'field' => $model->displayField, 'order' => 'ASC', 'verify' => true];
        extract($options);
        if ($verify && !$this->verify($model)) {
            return false;
        }
        $verify = false;
        extract($this->settings[$model->alias]);
        $fields = [$model->primaryKey, $field, $left, $right];
        $sort = $field . ' ' . $order;
        $nodes = $this->children($model, $id, true, $fields, $sort, null, null, $recursive);

        $cacheQueries = $model->cacheQueries;
        $model->cacheQueries = false;
        if ($nodes) {
            foreach ($nodes as $node) {
                $id = $node[$model->alias][$model->primaryKey];
                $this->moveDown($model, $id, true);
                if ($node[$model->alias][$left] != $node[$model->alias][$right] - 1) {
                    $this->reorder($model, compact('id', 'field', 'order', 'verify'));
                }
            }
        }
        $model->cacheQueries = $cacheQueries;

        return true;
    }

    /**
     * Remove the current node from the tree, and reparent all children up one level.
     *
     * If the parameter delete is false, the node will become a new top level node. Otherwise the node will be deleted
     * after the children are reparented.
     *
     * @param Model $model Model using this behavior
     * @param string|int|null $id The ID of the record to remove
     * @param bool $delete whether to delete the node after reparenting children (if any)
     * @return bool true on success, false on failure
     * @link https://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::removeFromTree
     */
    public function removeFromTree(Model $model, $id = null, $delete = false)
    {
        if (is_array($id)) {
            extract(array_merge(['id' => null], $id));
        }
        extract($this->settings[$model->alias]);

        [$node] = array_values($this->_getNode($model, $id));

        if ($node[$right] == $node[$left] + 1) {
            if ($delete) {
                return $model->delete($id);
            }
            $model->id = $id;

            return $model->saveField($parent, null);
        } elseif ($node[$parent]) {
            [$parentNode] = array_values($this->_getNode($model, $node[$parent]));
        } else {
            $parentNode[$right] = $node[$right] + 1;
        }

        $db = ConnectionManager::getDataSource($model->useDbConfig);
        $model->updateAll(
            [$parent => $db->value($node[$parent], $parent)],
            [$model->escapeField($parent) => $node[$model->primaryKey]],
        );
        $this->_sync($model, 1, '-', 'BETWEEN ' . ($node[$left] + 1) . ' AND ' . ($node[$right] - 1));
        $this->_sync($model, 2, '-', '> ' . $node[$right]);
        $model->id = $id;

        if ($delete) {
            $model->updateAll(
                [
                    $model->escapeField($left) => 0,
                    $model->escapeField($right) => 0,
                    $model->escapeField($parent) => null,
                ],
                [$model->escapeField() => $id],
            );

            return $model->delete($id);
        }
        $edge = $this->_getMax($model, $scope, $right, $recursive);
        if ($node[$right] == $edge) {
            $edge = $edge - 2;
        }
        $model->id = $id;

        return $model->save(
            [$left => $edge + 1, $right => $edge + 2, $parent => null],
            ['callbacks' => false, 'validate' => false],
        );
    }

    /**
     * Check if the current tree is valid.
     *
     * Returns true if the tree is valid otherwise an array of (type, incorrect left/right index, message)
     *
     * @param Model $model Model using this behavior
     * @return mixed true if the tree is valid or empty, otherwise an array of (error type [index, node],
     *  [incorrect left/right index,node id], message)
     * @link https://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::verify
     */
    public function verify(Model $model)
    {
        extract($this->settings[$model->alias]);
        if (!$model->find('count', ['conditions' => $scope])) {
            return true;
        }
        $min = $this->_getMin($model, $scope, $left, $recursive);
        $edge = $this->_getMax($model, $scope, $right, $recursive);
        $errors = [];

        for ($i = $min; $i <= $edge; $i++) {
            $count = $model->find('count', ['conditions' => [
                $scope, 'OR' => [$model->escapeField($left) => $i, $model->escapeField($right) => $i],
            ]]);
            if ($count != 1) {
                if (!$count) {
                    $errors[] = ['index', $i, 'missing'];
                } else {
                    $errors[] = ['index', $i, 'duplicate'];
                }
            }
        }
        $node = $model->find('first', [
            'conditions' => [$scope, $model->escapeField($right) . '< ' . $model->escapeField($left)],
            'order' => false,
            'recursive' => 0,
        ]);
        if ($node) {
            $errors[] = ['node', $node[$model->alias][$model->primaryKey], 'left greater than right.'];
        }

        $model->bindModel(['belongsTo' => ['VerifyParent' => [
            'className' => $model->name,
            'foreignKey' => $parent,
            'fields' => [$model->primaryKey, $left, $right, $parent],
        ]]]);

        $rows = $model->find('all', ['conditions' => $scope, 'recursive' => 0]);
        foreach ($rows as $instance) {
            if ($instance[$model->alias][$left] === null || $instance[$model->alias][$right] === null) {
                $errors[] = ['node', $instance[$model->alias][$model->primaryKey],
                    'has invalid left or right values'];
            } elseif ($instance[$model->alias][$left] == $instance[$model->alias][$right]) {
                $errors[] = ['node', $instance[$model->alias][$model->primaryKey],
                    'left and right values identical'];
            } elseif ($instance[$model->alias][$parent]) {
                if (!$instance['VerifyParent'][$model->primaryKey]) {
                    $errors[] = ['node', $instance[$model->alias][$model->primaryKey],
                        'The parent node ' . $instance[$model->alias][$parent] . ' doesn\'t exist'];
                } elseif ($instance[$model->alias][$left] < $instance['VerifyParent'][$left]) {
                    $errors[] = ['node', $instance[$model->alias][$model->primaryKey],
                        'left less than parent (node ' . $instance['VerifyParent'][$model->primaryKey] . ').'];
                } elseif ($instance[$model->alias][$right] > $instance['VerifyParent'][$right]) {
                    $errors[] = ['node', $instance[$model->alias][$model->primaryKey],
                        'right greater than parent (node ' . $instance['VerifyParent'][$model->primaryKey] . ').'];
                }
            } elseif ($model->find('count', ['conditions' => [$scope, $model->escapeField($left) . ' <' => $instance[$model->alias][$left], $model->escapeField($right) . ' >' => $instance[$model->alias][$right]], 'recursive' => 0])) {
                $errors[] = ['node', $instance[$model->alias][$model->primaryKey], 'The parent field is blank, but has a parent'];
            }
        }
        if ($errors) {
            return $errors;
        }

        return true;
    }

    /**
     * Returns the depth level of a node in the tree.
     *
     * @param Model $model Model using this behavior
     * @param string|int|null $id The primary key for record to get the level of.
     * @return int|bool Integer of the level or false if the node does not exist.
     */
    public function getLevel(Model $model, $id = null)
    {
        if ($id === null) {
            $id = $model->id;
        }

        $node = $model->find('first', [
            'conditions' => [$model->escapeField() => $id],
            'order' => false,
            'recursive' => -1,
        ]);

        if (empty($node)) {
            return false;
        }

        extract($this->settings[$model->alias]);

        return $model->find('count', [
            'conditions' => [
                $scope,
                $left . ' <' => $node[$model->alias][$left],
                $right . ' >' => $node[$model->alias][$right],
            ],
            'order' => false,
            'recursive' => -1,
        ]);
    }

    /**
     * Sets the parent of the given node
     *
     * The force parameter is used to override the "don't change the parent to the current parent" logic in the event
     * of recovering a corrupted table, or creating new nodes. Otherwise it should always be false. In reality this
     * method could be private, since calling save with parent_id set also calls setParent
     *
     * @param Model $model Model using this behavior
     * @param string|int|null $parentId Parent record Id
     * @param bool $created True if newly created record else false.
     * @return bool true on success, false on failure
     */
    protected function _setParent(Model $model, $parentId = null, $created = false)
    {
        extract($this->settings[$model->alias]);
        [$node] = array_values($this->_getNode($model, $model->id));
        $edge = $this->_getMax($model, $scope, $right, $recursive, $created);

        if (empty($parentId)) {
            $this->_sync($model, $edge - $node[$left] + 1, '+', 'BETWEEN ' . $node[$left] . ' AND ' . $node[$right], $created);
            $this->_sync($model, $node[$right] - $node[$left] + 1, '-', '> ' . $node[$left], $created);
        } else {
            $values = $this->_getNode($model, $parentId);

            if ($values === false) {
                return false;
            }
            $parentNode = array_values($values);

            if (empty($parentNode) || empty($parentNode[0])) {
                return false;
            }
            $parentNode = $parentNode[0];

            if (($model->id === $parentId)) {
                return false;
            } elseif (($node[$left] < $parentNode[$left]) && ($parentNode[$right] < $node[$right])) {
                return false;
            }
            if (empty($node[$left]) && empty($node[$right])) {
                $this->_sync($model, 2, '+', '>= ' . $parentNode[$right], $created);
                $result = $model->save(
                    [$left => $parentNode[$right], $right => $parentNode[$right] + 1, $parent => $parentId],
                    ['validate' => false, 'callbacks' => false],
                );
                $model->data = $result;
            } else {
                $this->_sync($model, $edge - $node[$left] + 1, '+', 'BETWEEN ' . $node[$left] . ' AND ' . $node[$right], $created);
                $diff = $node[$right] - $node[$left] + 1;

                if ($node[$left] > $parentNode[$left]) {
                    if ($node[$right] < $parentNode[$right]) {
                        $this->_sync($model, $diff, '-', 'BETWEEN ' . $node[$right] . ' AND ' . ($parentNode[$right] - 1), $created);
                        $this->_sync($model, $edge - $parentNode[$right] + $diff + 1, '-', '> ' . $edge, $created);
                    } else {
                        $this->_sync($model, $diff, '+', 'BETWEEN ' . $parentNode[$right] . ' AND ' . $node[$right], $created);
                        $this->_sync($model, $edge - $parentNode[$right] + 1, '-', '> ' . $edge, $created);
                    }
                } else {
                    $this->_sync($model, $diff, '-', 'BETWEEN ' . $node[$right] . ' AND ' . ($parentNode[$right] - 1), $created);
                    $this->_sync($model, $edge - $parentNode[$right] + $diff + 1, '-', '> ' . $edge, $created);
                }
            }
        }

        return true;
    }

    /**
     * get the maximum index value in the table.
     *
     * @param Model $model Model Instance.
     * @param string $scope Scoping conditions.
     * @param string $right Right value
     * @param int $recursive Recursive find value.
     * @param bool $created Whether it's a new record.
     * @return int
     */
    protected function _getMax(Model $model, $scope, $right, $recursive = -1, $created = false)
    {
        $db = ConnectionManager::getDataSource($model->useDbConfig);
        if ($created) {
            if (is_string($scope)) {
                $scope .= ' AND ' . $model->escapeField() . ' <> ';
                $scope .= $db->value($model->id, $model->getColumnType($model->primaryKey));
            } else {
                $scope['NOT'][$model->alias . '.' . $model->primaryKey] = $model->id;
            }
        }
        $name = $model->escapeField($right);
        [$edge] = array_values($model->find('first', [
            'conditions' => $scope,
            'fields' => $db->calculate($model, 'max', [$name, $right]),
            'recursive' => $recursive,
            'order' => false,
            'callbacks' => false,
        ]));

        return empty($edge[$right]) ? 0 : $edge[$right];
    }

    /**
     * get the minimum index value in the table.
     *
     * @param Model $model Model instance.
     * @param string $scope Scoping conditions.
     * @param string $left Left value.
     * @param int $recursive Recurursive find value.
     * @return int
     */
    protected function _getMin(Model $model, $scope, $left, $recursive = -1)
    {
        $db = ConnectionManager::getDataSource($model->useDbConfig);
        $name = $model->escapeField($left);
        [$edge] = array_values($model->find('first', [
            'conditions' => $scope,
            'fields' => $db->calculate($model, 'min', [$name, $left]),
            'recursive' => $recursive,
            'order' => false,
            'callbacks' => false,
        ]));

        return empty($edge[$left]) ? 0 : $edge[$left];
    }

    /**
     * Table sync method.
     *
     * Handles table sync operations, Taking account of the behavior scope.
     *
     * @param Model $model Model instance.
     * @param int $shift Shift by.
     * @param string $dir Direction.
     * @param array $conditions Conditions.
     * @param bool $created Whether it's a new record.
     * @param string $field Field type.
     * @return void
     */
    protected function _sync(Model $model, $shift, $dir = '+', $conditions = [], $created = false, $field = 'both')
    {
        $ModelRecursive = $model->recursive;
        extract($this->settings[$model->alias]);
        $model->recursive = $recursive;

        if ($field === 'both') {
            $this->_sync($model, $shift, $dir, $conditions, $created, $left);
            $field = $right;
        }
        if (is_string($conditions)) {
            $conditions = [$model->escapeField($field) . " {$conditions}"];
        }
        if (($scope !== '1 = 1' && $scope !== true) && $scope) {
            $conditions[] = $scope;
        }
        if ($created) {
            $conditions['NOT'][$model->escapeField()] = $model->id;
        }
        $model->updateAll([$model->escapeField($field) => $model->escapeField($field) . ' ' . $dir . ' ' . $shift], $conditions);
        $model->recursive = $ModelRecursive;
    }
}
