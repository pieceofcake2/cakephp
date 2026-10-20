<?php
/**
 * SchemaShellTestSchema file
 *
 * Use for testing the loading of schema files by the schema shell.
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
 * @package       Cake.Test.TestApp.Config.Schema
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Test for Schema database management
 *
 * @package       Cake.Test.TestApp.Config.Schema
 */
class SchemaShellTestSchema extends CakeSchema
{
    /**
     * connection property
     *
     * @var string
     */
    public $connection = 'test';

    /**
     * comments property
     *
     * @var array
     */
    public $comments = [
        'id' => ['type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'],
        'post_id' => ['type' => 'integer', 'null' => false, 'default' => 0],
        'user_id' => ['type' => 'integer', 'null' => false],
        'title' => ['type' => 'string', 'null' => false, 'length' => 100],
        'comment' => ['type' => 'text', 'null' => false, 'default' => null],
        'published' => ['type' => 'string', 'null' => true, 'default' => 'N', 'length' => 1],
        'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'updated' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'indexes' => ['PRIMARY' => ['column' => 'id', 'unique' => true]],
    ];

    /**
     * posts property
     *
     * @var array
     */
    public $articles = [
        'id' => ['type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'],
        'user_id' => ['type' => 'integer', 'null' => true, 'default' => ''],
        'title' => ['type' => 'string', 'null' => false, 'default' => 'Title'],
        'body' => ['type' => 'text', 'null' => true, 'default' => null],
        'summary' => ['type' => 'text', 'null' => true],
        'published' => ['type' => 'string', 'null' => true, 'default' => 'Y', 'length' => 1],
        'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'updated' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'indexes' => ['PRIMARY' => ['column' => 'id', 'unique' => true]],
    ];

    public $newone = [
        'id' => ['type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'],
        'testit' => ['type' => 'string', 'null' => false, 'default' => 'Title'],
        'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'updated' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'indexes' => ['PRIMARY' => ['column' => 'id', 'unique' => true]],
    ];
}
