<?php
/**
 * Test App TranslatedArticle Model
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
 * @package       Cake.Test.TestApp.Model
 * @since         CakePHP(tm) v 1.2.0.6464
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TranslatedArticle class.
 *
 * @package       Cake.Test.TestApp.Model
 */
class TranslatedArticle extends CakeTestModel
{
    /**
     * name property
     *
     * @var string
     */
    public $name = 'TranslatedArticle';

    /**
     * cacheQueries property
     *
     * @var bool
     */
    public $cacheQueries = false;

    /**
     * actsAs property
     *
     * @var array
     */
    public $actsAs = ['Translate' => ['title', 'body']];

    /**
     * translateModel property
     *
     * @var string
     */
    public $translateModel = 'TranslateArticleModel';

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = ['User'];

    /**
     * hasMany property
     *
     * @var array
     */
    public $hasMany = ['TranslatedItem'];
}
