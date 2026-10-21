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

namespace TestApp\Model\Datasource\Database;

use Cake\Model\Datasource\Database\Sqlserver;
use Cake\Model\Model;
use PDOStatement;

/**
 * SqlserverTestDb class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class SqlserverTestDb extends Sqlserver
{
    /**
     * simulated property
     *
     * @var array
     */
    public $simulated = [];

    /**
     * execute results stack
     *
     * @var array
     */
    public $executeResultsStack = [];

    /**
     * @var array|null
     */
    public ?array $describe = null;

    /**
     * execute method
     *
     * @param string $sql
     * @param array $params
     * @param array $prepareOptions
     * @return PDOStatement|bool
     */
    protected function _execute($sql, $params = [], $prepareOptions = []): mixed
    {
        $this->simulated[] = $sql;

        return empty($this->executeResultsStack) ? false : array_pop($this->executeResultsStack);
    }

    /**
     * fetchAll method
     *
     * @param Model $model
     * @param mixed $conditions
     * @return string
     */
    protected function _matchRecords(
        Model $model,
        $conditions = null,
    ): string {
        return $this->conditions(['id' => [1, 2]]);
    }

    /**
     * getLastQuery method
     *
     * @return string
     */
    public function getLastQuery(): string
    {
        return $this->simulated[count($this->simulated) - 1];
    }

    /**
     * getPrimaryKey method
     *
     * @param mixed $model
     * @return string
     */
    public function getPrimaryKey($model): mixed
    {
        return parent::_getPrimaryKey($model);
    }

    /**
     * clearFieldMappings method
     *
     * @return void
     */
    public function clearFieldMappings(): void
    {
        $this->_fieldMappings = [];
    }

    /**
     * describe method
     *
     * @param string|Model $model
     * @return array|false|null
     */
    public function describe($model): array
    {
        return empty($this->describe) ? parent::describe($model) : $this->describe;
    }
}
