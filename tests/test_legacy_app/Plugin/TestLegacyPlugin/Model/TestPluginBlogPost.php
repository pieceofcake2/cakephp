<?php
/**
 * Test Plugin Blog Post Model
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
 * @package       Cake.Test.TestApp.Plugin.TestLegacyPlugin.Model
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppModel', 'Model');

/**
 * TestPluginBlogPost
 *
 * @package       Cake.Test.TestApp.Plugin.TestLegacyPlugin.Model
 */
class TestPluginBlogPost extends AppModel
{
    public $useTable = 'posts';

    public $name = 'TestPluginBlogPost';
}
