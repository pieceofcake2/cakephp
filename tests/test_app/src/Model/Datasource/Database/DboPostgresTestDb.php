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

use Cake\Model\Datasource\Database\Postgres;
use PDOStatement;

/**
 * DboPostgresTestDb class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class DboPostgresTestDb extends Postgres
{
    /**
     * simulated property
     *
     * @var array
     */
    public $simulated = [];

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

        return false;
    }

    /**
     * getLastQuery method
     *
     * @return void
     */
    public function getLastQuery(): mixed
    {
        return $this->simulated[count($this->simulated) - 1];
    }
}
