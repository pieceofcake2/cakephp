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

namespace TestApp\Model;

use Cake\Model\ConnectionManager;
use Cake\TestSuite\Fixture\CakeTestModel;

/**
 * NumberTree class
 *
 * @package       Cake.Test.Case.Model
 */
class NumberTree extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'NumberTree';

    /**
     * actsAs property
     *
     * @var array
     */
    public $actsAs = ['Tree'];

    /**
     * initialize method
     *
     * @param int $levelLimit
     * @param int $childLimit
     * @param mixed $currentLevel
     * @param mixed $parent_id
     * @param string $prefix
     * @param bool $hierarchal
     * @return void
     */
    public function initialize($levelLimit = 3, $childLimit = 3, $currentLevel = null, $parentId = null, $prefix = '1', $hierarchal = true): void
    {
        if (!$parentId) {
            $db = ConnectionManager::getDataSource($this->useDbConfig);
            $db->truncate($this->table);
            $this->save([$this->name => ['name' => '1. Root']]);
            $this->initialize($levelLimit, $childLimit, 1, $this->id, '1', $hierarchal);
            $this->create([]);
        }

        if (!$currentLevel || $currentLevel > $levelLimit) {
            return;
        }

        for ($i = 1; $i <= $childLimit; $i++) {
            $name = $prefix . '.' . $i;
            $data = [$this->name => ['name' => $name]];
            $this->create($data);

            if ($hierarchal) {
                if ($this->name === 'UnconventionalTree') {
                    $data[$this->name]['join'] = $parentId;
                } else {
                    $data[$this->name]['parent_id'] = $parentId;
                }
            }
            $this->save($data);
            $this->initialize($levelLimit, $childLimit, $currentLevel + 1, $this->id, $name, $hierarchal);
        }
    }
}
