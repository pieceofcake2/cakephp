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
 * @package       Cake.Model.Behavior
 * @since         CakePHP(tm) v 1.2.0.4525
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Model\Behavior;

use Cake\Core\Configure;
use Cake\Error\CakeException;
use Cake\I18n\I18n;
use Cake\Model\ConnectionManager;
use Cake\Model\Model;
use Cake\Model\ModelBehavior;
use Cake\Utility\CakeText;
use Cake\Utility\ClassRegistry;
use stdClass;

/**
 * Translate behavior
 *
 * @package       Cake.Model.Behavior
 * @link https://book.cakephp.org/2.0/en/core-libraries/behaviors/translate.html
 */
class TranslateBehavior extends ModelBehavior
{
    /**
     * Used for runtime configuration of model
     *
     * @var array
     */
    public $runtime = [];

    /**
     * Stores the joinTable object for generating joins.
     *
     * @var object
     */
    protected $_joinTable;

    /**
     * Stores the runtime model for generating joins.
     *
     * @var Model
     */
    protected $_runtimeModel;

    /**
     * Callback
     *
     * $config for TranslateBehavior should be
     * array('fields' => array('field_one',
     * 'field_two' => 'FieldAssoc', 'field_three'))
     *
     * With above example only one permanent hasMany will be joined (for field_two
     * as FieldAssoc)
     *
     * $config could be empty - and translations configured dynamically by
     * bindTranslation() method
     *
     * By default INNER joins are used to fetch translations. In order to use
     * other join types $config should contain 'joinType' key:
     * ```
     * array(
     *     'fields' => array('field_one', 'field_two' => 'FieldAssoc', 'field_three'),
     *     'joinType' => 'LEFT',
     * )
     * ```
     * In a model it may be configured this way:
     * ```
     * public $actsAs = array(
     *     'Translate' => array(
     *         'content',
     *         'title',
     *         'joinType' => 'LEFT',
     *     ),
     * );
     * ```
     *
     * @param Model $model Model the behavior is being attached to.
     * @param array $config Array of configuration information.
     * @return mixed
     */
    public function setup(Model $model, array $config = []): void
    {
        $db = ConnectionManager::getDataSource($model->useDbConfig);
        if (!$db->connected) {
            trigger_error(
                __d('cake_dev', 'Datasource %s for TranslateBehavior of model %s is not connected', $model->useDbConfig, $model->alias),
                E_USER_ERROR,
            );

            return;
        }

        $this->settings[$model->alias] = [];
        $this->runtime[$model->alias] = [
            'fields' => [],
            'joinType' => 'INNER',
        ];
        if (isset($config['joinType'])) {
            $this->runtime[$model->alias]['joinType'] = $config['joinType'];
            unset($config['joinType']);
        }
        $this->translateModel($model);
        $this->bindTranslation($model, $config, false);
    }

    /**
     * Cleanup Callback unbinds bound translations and deletes setting information.
     *
     * @param Model $model Model being detached.
     * @return void
     */
    public function cleanup(Model $model)
    {
        $this->unbindTranslation($model);
        unset($this->settings[$model->alias]);
        unset($this->runtime[$model->alias]);
    }

    /**
     * beforeFind Callback
     *
     * @param Model $model Model find is being run on.
     * @param array $query Array of Query parameters.
     * @return array|bool|null Modified query
     */
    public function beforeFind(Model $model, array $query): array|bool|null
    {
        $this->runtime[$model->alias]['virtualFields'] = $model->virtualFields;
        $locale = $this->_getLocale($model);
        if (empty($locale)) {
            return $query;
        }
        $db = $model->getDataSource();
        $RuntimeModel = $this->translateModel($model);

        if (!empty($RuntimeModel->tablePrefix)) {
            $tablePrefix = $RuntimeModel->tablePrefix;
        } else {
            $tablePrefix = $db->config['prefix'];
        }
        $joinTable = new stdClass();
        $joinTable->tablePrefix = $tablePrefix;
        $joinTable->table = $RuntimeModel->table;
        $joinTable->schemaName = $RuntimeModel->getDataSource()->getSchemaName();

        $this->_joinTable = $joinTable;
        $this->_runtimeModel = $RuntimeModel;

        if (is_string($query['fields'])) {
            if ($query['fields'] === "COUNT(*) AS {$db->name('count')}") {
                $query['fields'] = "COUNT(DISTINCT({$db->name($model->escapeField())})) {$db->alias}count";
                $query['joins'][] = [
                    'type' => $this->runtime[$model->alias]['joinType'],
                    'alias' => $RuntimeModel->alias,
                    'table' => $joinTable,
                    'conditions' => [
                        $model->escapeField() => $db->identifier($RuntimeModel->escapeField('foreign_key')),
                        $RuntimeModel->escapeField('model') => $model->name,
                        $RuntimeModel->escapeField('locale') => $locale,
                    ],
                ];
                $conditionFields = $this->_checkConditions($model, $query);
                foreach ($conditionFields as $field) {
                    $query = $this->_addJoin($model, $query, $field, $field, $locale);
                }
                unset($this->_joinTable, $this->_runtimeModel);

                return $query;
            } else {
                $query['fields'] = CakeText::tokenize($query['fields']);
            }
        }
        $addFields = $this->_getFields($model, $query);
        $this->runtime[$model->alias]['virtualFields'] = $model->virtualFields;
        $query = $this->_addAllJoins($model, $query, $addFields);
        $this->runtime[$model->alias]['beforeFind'] = $addFields;
        unset($this->_joinTable, $this->_runtimeModel);

        return $query;
    }

    /**
     * Gets fields to be retrieved.
     *
     * @param Model $model The model being worked on.
     * @param array $query The query array to take fields from.
     * @return array The fields.
     */
    protected function _getFields(Model $model, $query)
    {
        $fields = array_merge(
            $this->settings[$model->alias],
            $this->runtime[$model->alias]['fields'],
        );
        $addFields = [];
        if (empty($query['fields'])) {
            $addFields = $fields;
        } elseif (is_array($query['fields'])) {
            $isAllFields = (
                in_array($model->alias . '.' . '*', $query['fields']) ||
                in_array($model->escapeField('*'), $query['fields'])
            );
            foreach ($fields as $key => $value) {
                $field = is_numeric($key) ? $value : $key;
                if (
                    $isAllFields ||
                    in_array($model->alias . '.' . $field, $query['fields']) ||
                    in_array($field, $query['fields'])
                ) {
                    $addFields[] = $field;
                }
            }
        }

        return $addFields;
    }

    /**
     * Appends all necessary joins for translated fields.
     *
     * @param Model $model The model being worked on.
     * @param array $query The query array to append joins to.
     * @param array $addFields The fields being joined.
     * @return array The modified query
     */
    protected function _addAllJoins(Model $model, $query, $addFields)
    {
        $locale = $this->_getLocale($model);
        if ($addFields) {
            foreach ($addFields as $_f => $field) {
                $aliasField = is_numeric($_f) ? $field : $_f;
                foreach ([$aliasField, $model->alias . '.' . $aliasField] as $_field) {
                    $key = array_search($_field, (array)$query['fields']);
                    if ($key !== false) {
                        unset($query['fields'][$key]);
                    }
                }
                $query = $this->_addJoin($model, $query, $field, $aliasField, $locale);
            }
        }

        return $query;
    }

    /**
     * Check a query's conditions for translated fields.
     * Return an array of translated fields found in the conditions.
     *
     * @param Model $model The model being read.
     * @param array $query The query array.
     * @return array The list of translated fields that are in the conditions.
     */
    protected function _checkConditions(Model $model, $query)
    {
        if (empty($query['conditions']) || (!empty($query['conditions']) && !is_array($query['conditions']))) {
            return [];
        }

        return $this->_getConditionFields($model, $query['conditions']);
    }

    /**
     * Extracts condition field names recursively.
     *
     * @param Model $model The model being read.
     * @param array $conditions The conditions array.
     * @return array The list of condition fields.
     */
    protected function _getConditionFields(Model $model, $conditions)
    {
        $conditionFields = [];
        foreach ($conditions as $col => $val) {
            if (is_array($val)) {
                $subConditionFields = $this->_getConditionFields($model, $val);
                $conditionFields = array_merge($conditionFields, $subConditionFields);
            }
            foreach ($this->settings[$model->alias] as $field => $assoc) {
                if (is_numeric($field)) {
                    $field = $assoc;
                }
                if (str_contains($col, $field)) {
                    $conditionFields[] = $field;
                }
            }
        }

        return $conditionFields;
    }

    /**
     * Appends a join for translated fields.
     *
     * @param Model $model The model being worked on.
     * @param array $query The query array to append a join to.
     * @param string $field The field name being joined.
     * @param string $aliasField The aliased field name being joined.
     * @param array|string $locale The locale(s) having joins added.
     * @return array The modified query
     */
    protected function _addJoin(Model $model, $query, $field, $aliasField, $locale)
    {
        $db = ConnectionManager::getDataSource($model->useDbConfig);
        $RuntimeModel = $this->_runtimeModel;
        $joinTable = $this->_joinTable;
        $aliasVirtual = "i18n_{$field}";
        $alias = "I18n__{$field}";
        if (is_array($locale)) {
            foreach ($locale as $_locale) {
                $aliasVirtualLocale = "{$aliasVirtual}_{$_locale}";
                $aliasLocale = "{$alias}__{$_locale}";
                $model->virtualFields[$aliasVirtualLocale] = "{$aliasLocale}.content";
                if (!empty($query['fields']) && is_array($query['fields'])) {
                    $query['fields'][] = $aliasVirtualLocale;
                }
                $query['joins'][] = [
                    'type' => 'LEFT',
                    'alias' => $aliasLocale,
                    'table' => $joinTable,
                    'conditions' => [
                        $model->escapeField() => $db->identifier("{$aliasLocale}.foreign_key"),
                        "{$aliasLocale}.model" => $model->name,
                        "{$aliasLocale}.{$RuntimeModel->displayField}" => $aliasField,
                        "{$aliasLocale}.locale" => $_locale,
                    ],
                ];
            }
        } else {
            $model->virtualFields[$aliasVirtual] = "{$alias}.content";
            if (!empty($query['fields']) && is_array($query['fields'])) {
                $query['fields'][] = $aliasVirtual;
            }
            $query['joins'][] = [
                'type' => $this->runtime[$model->alias]['joinType'],
                'alias' => $alias,
                'table' => $joinTable,
                'conditions' => [
                    "{$model->alias}.{$model->primaryKey}" => $db->identifier("{$alias}.foreign_key"),
                    "{$alias}.model" => $model->name,
                    "{$alias}.{$RuntimeModel->displayField}" => $aliasField,
                    "{$alias}.locale" => $locale,
                ],
            ];
        }

        return $query;
    }

    /**
     * afterFind Callback
     *
     * @param Model $model Model find was run on
     * @param array $results Array of model results.
     * @param bool $primary Did the find originate on $model.
     * @return array Modified results
     */
    public function afterFind(Model $model, mixed $results, bool $primary = false): mixed
    {
        $model->virtualFields = $this->runtime[$model->alias]['virtualFields'];

        $this->runtime[$model->alias]['virtualFields'] = [];
        if (!empty($this->runtime[$model->alias]['restoreFields'])) {
            $this->runtime[$model->alias]['fields'] = $this->runtime[$model->alias]['restoreFields'];
            unset($this->runtime[$model->alias]['restoreFields']);
        }

        $locale = $this->_getLocale($model);

        if (empty($locale) || empty($results) || empty($this->runtime[$model->alias]['beforeFind'])) {
            return $results;
        }
        $beforeFind = $this->runtime[$model->alias]['beforeFind'];

        foreach ($results as $key => &$row) {
            $results[$key][$model->alias]['locale'] = is_array($locale) ? current($locale) : $locale;
            foreach ($beforeFind as $_f => $field) {
                $aliasField = is_numeric($_f) ? $field : $_f;
                $aliasVirtual = "i18n_{$field}";
                if (is_array($locale)) {
                    foreach ($locale as $_locale) {
                        $aliasVirtualLocale = "{$aliasVirtual}_{$_locale}";
                        if (!isset($row[$model->alias][$aliasField]) && !empty($row[$model->alias][$aliasVirtualLocale])) {
                            $row[$model->alias][$aliasField] = $row[$model->alias][$aliasVirtualLocale];
                            $row[$model->alias]['locale'] = $_locale;
                        }
                        unset($row[$model->alias][$aliasVirtualLocale]);
                    }

                    if (!isset($row[$model->alias][$aliasField])) {
                        $row[$model->alias][$aliasField] = '';
                    }
                } else {
                    $value = '';
                    if (isset($row[$model->alias][$aliasVirtual])) {
                        $value = $row[$model->alias][$aliasVirtual];
                    }
                    $row[$model->alias][$aliasField] = $value;
                    unset($row[$model->alias][$aliasVirtual]);
                }
            }
        }

        return $results;
    }

    /**
     * beforeValidate Callback
     *
     * @param Model $model Model invalidFields was called on.
     * @param array $options Options passed from Model::save().
     * @return bool
     * @see Model::save()
     */
    public function beforeValidate(Model $model, array $options = []): ?bool
    {
        unset($this->runtime[$model->alias]['beforeSave']);
        $this->_setRuntimeData($model);

        return true;
    }

    /**
     * beforeSave callback.
     *
     * Copies data into the runtime property when `$options['validate']` is
     * disabled. Or the runtime data hasn't been set yet.
     *
     * @param Model $model Model save was called on.
     * @param array $options Options passed from Model::save().
     * @return bool|null true.
     * @see Model::save()
     */
    public function beforeSave(Model $model, array $options = []): ?bool
    {
        if (isset($options['validate']) && !$options['validate']) {
            unset($this->runtime[$model->alias]['beforeSave']);
        }
        if (isset($this->runtime[$model->alias]['beforeSave'])) {
            return true;
        }
        $this->_setRuntimeData($model);

        return true;
    }

    /**
     * Sets the runtime data.
     *
     * Used from beforeValidate() and beforeSave() for compatibility issues,
     * and to allow translations to be persisted even when validation
     * is disabled.
     *
     * @param Model $model Model using this behavior.
     * @return void
     */
    protected function _setRuntimeData(Model $model): void
    {
        $locale = $this->_getLocale($model);
        if (empty($locale)) {
            return;
        }

        $fields = array_merge($this->settings[$model->alias], $this->runtime[$model->alias]['fields']);
        $tempData = [];

        foreach ($fields as $key => $value) {
            $field = is_numeric($key) ? $value : $key;

            if (isset($model->data[$model->alias][$field])) {
                $tempData[$field] = $model->data[$model->alias][$field];
                if (is_array($model->data[$model->alias][$field])) {
                    if (is_string($locale) && !empty($model->data[$model->alias][$field][$locale])) {
                        $model->data[$model->alias][$field] = $model->data[$model->alias][$field][$locale];
                    } else {
                        $values = array_values($model->data[$model->alias][$field]);
                        $model->data[$model->alias][$field] = $values[0];
                    }
                }
            }
        }
        $this->runtime[$model->alias]['beforeSave'] = $tempData;
    }

    /**
     * Restores model data to the original data.
     * This solves issues with saveAssociated and validate = first.
     *
     * @param Model $model Model using this behavior.
     * @return bool|null true.
     */
    public function afterValidate(Model $model): ?bool
    {
        $model->data[$model->alias] = array_merge(
            $model->data[$model->alias],
            $this->runtime[$model->alias]['beforeSave'],
        );

        return true;
    }

    /**
     * afterSave Callback
     *
     * @param Model $model Model the callback is called on
     * @param bool $created Whether or not the save created a record.
     * @param array $options Options passed from Model::save().
     * @return bool|null
     */
    public function afterSave(Model $model, bool $created, array $options = []): ?bool
    {
        if (!isset($this->runtime[$model->alias]['beforeValidate']) && !isset($this->runtime[$model->alias]['beforeSave'])) {
            return null;
        }
        if (isset($this->runtime[$model->alias]['beforeValidate'])) {
            $tempData = $this->runtime[$model->alias]['beforeValidate'];
        } else {
            $tempData = $this->runtime[$model->alias]['beforeSave'];
        }

        unset($this->runtime[$model->alias]['beforeValidate'], $this->runtime[$model->alias]['beforeSave']);
        $conditions = ['model' => $model->name, 'foreign_key' => $model->id];
        $RuntimeModel = $this->translateModel($model);

        if ($created) {
            $tempData = $this->_prepareTranslations($model, $tempData);
        }
        $locale = $this->_getLocale($model);
        $atomic = [];
        if (isset($options['atomic'])) {
            $atomic = ['atomic' => $options['atomic']];
        }

        foreach ($tempData as $field => $value) {
            unset($conditions['content']);
            $conditions['field'] = $field;
            if (is_array($value)) {
                $conditions['locale'] = array_keys($value);
            } else {
                $conditions['locale'] = $locale;
                if (is_array($locale)) {
                    $value = [$locale[0] => $value];
                } else {
                    $value = [$locale => $value];
                }
            }
            $translations = $RuntimeModel->find('list', [
                'conditions' => $conditions,
                'fields' => [
                    $RuntimeModel->alias . '.locale',
                    $RuntimeModel->alias . '.id',
                ],
            ]);
            foreach ($value as $_locale => $_value) {
                $RuntimeModel->create();
                $conditions['locale'] = $_locale;
                $conditions['content'] = $_value;
                if (array_key_exists($_locale, $translations)) {
                    $RuntimeModel->save([
                        $RuntimeModel->alias => array_merge(
                            $conditions,
                            ['id' => $translations[$_locale]],
                        ),
                        $atomic,
                    ]);
                } else {
                    $RuntimeModel->save([$RuntimeModel->alias => $conditions], $atomic);
                }
            }
        }

        return null;
    }

    /**
     * Prepares the data to be saved for translated records.
     * Add blank fields, and populates data for multi-locale saves.
     *
     * @param Model $model Model using this behavior
     * @param array $data The sparse data that was provided.
     * @return array The fully populated data to save.
     */
    protected function _prepareTranslations(Model $model, $data)
    {
        $fields = array_merge($this->settings[$model->alias], $this->runtime[$model->alias]['fields']);
        $locales = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $locales = array_merge($locales, array_keys($value));
            }
        }
        $locales = array_unique($locales);
        $hasLocales = count($locales) > 0;

        foreach ($fields as $key => $field) {
            if (!is_numeric($key)) {
                $field = $key;
            }
            if ($hasLocales && !isset($data[$field])) {
                $data[$field] = array_fill_keys($locales, '');
            } elseif (!isset($data[$field])) {
                $data[$field] = '';
            }
        }

        return $data;
    }

    /**
     * afterDelete Callback
     *
     * @param Model $model Model the callback was run on.
     * @return bool|null
     */
    public function afterDelete(Model $model): ?bool
    {
        $RuntimeModel = $this->translateModel($model);
        $conditions = ['model' => $model->name, 'foreign_key' => $model->id];
        $RuntimeModel->deleteAll($conditions);

        return null;
    }

    /**
     * Get selected locale for model
     *
     * @param Model $model Model the locale needs to be set/get on.
     * @return mixed string or false
     */
    protected function _getLocale(Model $model)
    {
        if (!isset($model->locale) || $model->locale === null) {
            $I18n = I18n::getInstance();
            $I18n->l10n->get(Configure::read('Config.language'));
            $model->locale = $I18n->l10n->locale;
        }

        return $model->locale;
    }

    /**
     * Get instance of model for translations.
     *
     * If the model has a translateModel property set, this will be used as the class
     * name to find/use. If no translateModel property is found 'I18nModel' will be used.
     *
     * @param Model $model Model to get a translatemodel for.
     * @return Model
     */
    public function translateModel(Model $model)
    {
        if (!isset($this->runtime[$model->alias]['model'])) {
            if (!isset($model->translateModel) || empty($model->translateModel)) {
                $className = 'I18nModel';
            } else {
                $className = $model->translateModel;
            }

            $this->runtime[$model->alias]['model'] = ClassRegistry::init($className);
        }
        if (!empty($model->translateTable) && $model->translateTable !== $this->runtime[$model->alias]['model']->useTable) {
            $this->runtime[$model->alias]['model']->setSource($model->translateTable);
        } elseif (empty($model->translateTable) && empty($model->translateModel)) {
            $this->runtime[$model->alias]['model']->setSource('i18n');
        }

        return $this->runtime[$model->alias]['model'];
    }

    /**
     * Bind translation for fields, optionally with hasMany association for
     * fake field.
     *
     * *Note* You should avoid binding translations that overlap existing model properties.
     * This can cause un-expected and un-desirable behavior.
     *
     * @param Model $model using this behavior of model
     * @param array|string $fields string with field or array(field1, field2=>AssocName, field3)
     * @param bool $reset Leave true to have the fields only modified for the next operation.
     *   if false the field will be added for all future queries.
     * @return bool
     * @throws CakeException when attempting to bind a translating called name. This is not allowed
     *   as it shadows Model::$name.
     */
    public function bindTranslation(Model $model, array|string $fields, bool $reset = true): bool
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }
        $associations = [];
        $RuntimeModel = $this->translateModel($model);
        $default = [
            'className' => $RuntimeModel->alias,
            'foreignKey' => 'foreign_key',
            'order' => 'id',
        ];

        foreach ($fields as $key => $value) {
            if (is_numeric($key)) {
                $field = $value;
                $association = null;
            } else {
                $field = $key;
                $association = $value;
            }
            if ($association === 'name') {
                throw new CakeException(
                    __d('cake_dev', 'You cannot bind a translation named "name".'),
                );
            }
            $this->_removeField($model, $field);

            if ($association === null) {
                if ($reset) {
                    $this->runtime[$model->alias]['fields'][] = $field;
                } else {
                    $this->settings[$model->alias][] = $field;
                }
            } else {
                if ($reset) {
                    $this->runtime[$model->alias]['fields'][$field] = $association;
                    $this->runtime[$model->alias]['restoreFields'][] = $field;
                } else {
                    $this->settings[$model->alias][$field] = $association;
                }

                foreach (['hasOne', 'hasMany', 'belongsTo', 'hasAndBelongsToMany'] as $type) {
                    if (isset($model->{$type}[$association]) || isset($model->__backAssociation[$type][$association])) {
                        trigger_error(
                            __d('cake_dev', 'Association %s is already bound to model %s', $association, $model->alias),
                            E_USER_ERROR,
                        );

                        return false;
                    }
                }
                $associations[$association] = array_merge($default, ['conditions' => [
                    'model' => $model->name,
                    $RuntimeModel->displayField => $field,
                ]]);
            }
        }

        if (!empty($associations)) {
            $model->bindModel(['hasMany' => $associations], $reset);
        }

        return true;
    }

    /**
     * Update runtime setting for a given field.
     *
     * @param Model $model Model using this behavior
     * @param string $field The field to update.
     * @return void
     */
    protected function _removeField(Model $model, $field)
    {
        if (array_key_exists($field, $this->settings[$model->alias])) {
            unset($this->settings[$model->alias][$field]);
        } elseif (in_array($field, $this->settings[$model->alias])) {
            $this->settings[$model->alias] = array_merge(array_diff($this->settings[$model->alias], [$field]));
        }

        if (array_key_exists($field, $this->runtime[$model->alias]['fields'])) {
            unset($this->runtime[$model->alias]['fields'][$field]);
        } elseif (in_array($field, $this->runtime[$model->alias]['fields'])) {
            $this->runtime[$model->alias]['fields'] = array_merge(array_diff($this->runtime[$model->alias]['fields'], [$field]));
        }
    }

    /**
     * Unbind translation for fields, optionally unbinds hasMany association for
     * fake field
     *
     * @param Model $model using this behavior of model
     * @param array|string $fields string with field, or array(field1, field2=>AssocName, field3), or null for
     *    unbind all original translations
     * @return bool
     */
    public function unbindTranslation(Model $model, $fields = null)
    {
        if (empty($fields) && empty($this->settings[$model->alias])) {
            return false;
        }
        if (empty($fields)) {
            return $this->unbindTranslation($model, $this->settings[$model->alias]);
        }

        if (is_string($fields)) {
            $fields = [$fields];
        }
        $associations = [];

        foreach ($fields as $key => $value) {
            if (is_numeric($key)) {
                $field = $value;
                $association = null;
            } else {
                $field = $key;
                $association = $value;
            }

            $this->_removeField($model, $field);

            if ($association !== null && (isset($model->hasMany[$association]) || isset($model->__backAssociation['hasMany'][$association]))) {
                $associations[] = $association;
            }
        }

        if (!empty($associations)) {
            $model->unbindModel(['hasMany' => $associations], false);
        }

        return true;
    }
}
