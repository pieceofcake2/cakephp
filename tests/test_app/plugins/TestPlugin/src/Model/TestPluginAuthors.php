<?php
/**
 * Test App Comment Model
 *
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package       Cake.Test.TestApp.Plugin.TestPlugin.Model
 * @since         CakePHP v 1.2.0.7726
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace TestPlugin\Model;

/**
 * TestPluginAuthors
 *
 * @package       Cake.Test.TestApp.Plugin.TestPlugin.Model
 */
class TestPluginAuthors extends TestPluginAppModel
{
    public string|bool|null $useTable = 'authors';

    public ?string $name = 'TestPluginAuthors';

    public array $validate = [
        'field' => [
            'notBlank' => [
                'rule' => 'notBlank',
                'message' => 'I can haz plugin model validation message',
            ],
        ],
    ];
}
