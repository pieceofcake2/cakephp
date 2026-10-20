<?php
/**
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace TestApp\Model\Behavior;

use Cake\Model\Model;
use Cake\Model\ModelBehavior;
use Cake\Utility\Hash;

/**
 * TestBehavior class
 *
 * @package       Cake.Test.Case.Model
 */
class TestBehavior extends ModelBehavior
{
    /**
     * mapMethods property
     *
     * @var array
     */
    public $mapMethods = ['/test(\w+)/' => 'testMethod', '/look for\s+(.+)/' => 'speakEnglish'];

    /**
     * setup method
     *
     * @param Model $model
     * @param array $config
     * @return void
     */
    public function setup(Model $model, $config = []): void
    {
        parent::setup($model, $config);
        if (isset($config['mangle'])) {
            $config['mangle'] .= ' mangled';
        }
        $this->settings[$model->alias] = array_merge(['beforeFind' => 'on', 'afterFind' => 'off'], $config);
    }

    /**
     * beforeFind method
     *
     * @param Model $model
     * @param array $query
     * @return array|bool|null
     */
    public function beforeFind(Model $model, $query): mixed
    {
        $settings = $this->settings[$model->alias];
        if (!isset($settings['beforeFind']) || $settings['beforeFind'] === 'off') {
            return parent::beforeFind($model, $query);
        }
        switch ($settings['beforeFind']) {
            case 'on':
                return false;
            case 'test':
                return null;
            case 'modify':
                $query['fields'] = [$model->alias . '.id', $model->alias . '.name', $model->alias . '.mytime'];
                $query['recursive'] = -1;

                return $query;
        }

        return null;
    }

    /**
     * afterFind method
     *
     * @param Model $model
     * @param array $results
     * @param bool $primary
     * @return array|bool
     */
    public function afterFind(Model $model, $results, $primary = false): mixed
    {
        $settings = $this->settings[$model->alias];
        if (!isset($settings['afterFind']) || $settings['afterFind'] === 'off') {
            return parent::afterFind($model, $results, $primary);
        }
        switch ($settings['afterFind']) {
            case 'on':
                return [];
            case 'test':
                return true;
            case 'test2':
                return null;
            case 'modify':
                return Hash::extract($results, "{n}.{$model->alias}");
        }

        return null;
    }

    /**
     * beforeSave method
     *
     * @param Model $model Model using this behavior
     * @param array $options Options passed from Model::save().
     * @return bool|null False if the operation should abort. Any other result will continue.
     * @see Model::save()
     */
    public function beforeSave(Model $model, $options = []): mixed
    {
        $settings = $this->settings[$model->alias];
        if (!isset($settings['beforeSave']) || $settings['beforeSave'] === 'off') {
            return parent::beforeSave($model, $options);
        }
        switch ($settings['beforeSave']) {
            case 'on':
                return false;
            case 'test':
                return true;
            case 'modify':
                $model->data[$model->alias]['name'] .= ' modified before';

                return true;
        }

        return null;
    }

    /**
     * afterSave method
     *
     * @param Model $model
     * @param bool $created
     * @param array $options Options passed from Model::save().
     * @return bool|null
     */
    public function afterSave(Model $model, $created, $options = []): mixed
    {
        $settings = $this->settings[$model->alias];
        if (!isset($settings['afterSave']) || $settings['afterSave'] === 'off') {
            parent::afterSave($model, $created, $options);

            return null;
        }

        $string = 'modified after';
        if ($created) {
            $string .= ' on create';
        }
        switch ($settings['afterSave']) {
            case 'on':
                $model->data[$model->alias]['aftersave'] = $string;
                break;
            case 'test':
                unset($model->data[$model->alias]['name']);
                break;
            case 'test2':
                break;
            case 'modify':
                $model->data[$model->alias]['name'] .= ' ' . $string;
                break;
        }

        return null;
    }

    /**
     * beforeValidate Callback
     *
     * @param Model $model Model invalidFields was called on.
     * @param array $options Options passed from Model::save().
     * @return bool|null
     * @see Model::save()
     */
    public function beforeValidate(Model $model, $options = []): mixed
    {
        $settings = $this->settings[$model->alias];
        if (!isset($settings['validate']) || $settings['validate'] === 'off') {
            return parent::beforeValidate($model, $options);
        }
        switch ($settings['validate']) {
            case 'on':
                $model->invalidate('name');

                return true;
            case 'test':
                return null;
            case 'whitelist':
                $this->_addToWhitelist($model, ['name']);

                return true;
            case 'stop':
                $model->invalidate('name');

                return false;
        }

        return null;
    }

    /**
     * afterValidate method
     *
     * @param Model $model
     * @return bool|null
     */
    public function afterValidate(Model $model): mixed
    {
        $settings = $this->settings[$model->alias];
        if (!isset($settings['afterValidate']) || $settings['afterValidate'] === 'off') {
            return parent::afterValidate($model);
        }
        switch ($settings['afterValidate']) {
            case 'on':
                return false;
            case 'test':
                $model->data = ['foo'];

                return true;
        }

        return null;
    }

    /**
     * beforeDelete method
     *
     * @param Model $model
     * @param bool $cascade
     * @return bool|null
     */
    public function beforeDelete(Model $model, $cascade = true): mixed
    {
        $settings = $this->settings[$model->alias];
        if (!isset($settings['beforeDelete']) || $settings['beforeDelete'] === 'off') {
            return parent::beforeDelete($model, $cascade);
        }
        switch ($settings['beforeDelete']) {
            case 'on':
                return false;
            case 'test':
                return null;
            case 'test2':
                echo 'beforeDelete success';
                if ($cascade) {
                    echo ' (cascading) ';
                }

                return true;
        }

        return null;
    }

    /**
     * afterDelete method
     *
     * @param Model $model
     * @return bool|null
     */
    public function afterDelete(Model $model): mixed
    {
        $settings = $this->settings[$model->alias];
        if (!isset($settings['afterDelete']) || $settings['afterDelete'] === 'off') {
            parent::afterDelete($model);

            return null;
        }
        switch ($settings['afterDelete']) {
            case 'on':
                echo 'afterDelete success';
                break;
        }

        return null;
    }

    /**
     * onError method
     *
     * @param Model $model
     * @return void
     */
    public function onError(Model $model, $error): mixed
    {
        $settings = $this->settings[$model->alias];
        if (!isset($settings['onError']) || $settings['onError'] === 'off') {
            return parent::onError($model, $error);
        }
        echo 'onError trigger success';

        return null;
    }

    /**
     * beforeTest method
     *
     * @param Model $model
     * @return string
     */
    public function beforeTest(Model $model): mixed
    {
        if (!isset($model->beforeTestResult)) {
            $model->beforeTestResult = [];
        }
        $model->beforeTestResult[] = strtolower(static::class);

        return strtolower(static::class);
    }

    /**
     * testMethod method
     *
     * @param Model $model
     * @param bool $param
     * @return string|void
     */
    public function testMethod(Model $model, $param = true): mixed
    {
        if ($param === true) {
            return 'working';
        }

        return null;
    }

    /**
     * testData method
     *
     * @param Model $model
     * @return bool
     */
    public function testData(Model $model): mixed
    {
        if (!isset($model->data['Apple']['field'])) {
            return false;
        }
        $model->data['Apple']['field_2'] = true;

        return true;
    }

    /**
     * validateField method
     *
     * @param Model $model
     * @param array|string $field
     * @return bool
     */
    public function validateField(Model $model, $field): mixed
    {
        return current($field) === 'Orange';
    }

    /**
     * speakEnglish method
     *
     * @param Model $model
     * @param string $method
     * @param string $query
     * @return string
     */
    public function speakEnglish(Model $model, $method, $query): mixed
    {
        $method = preg_replace('/look for\s+/', 'Item.name = \'', $method);
        $query = preg_replace('/^in\s+/', 'Location.name = \'', $query);

        return $method . '\' AND ' . $query . '\'';
    }
}
