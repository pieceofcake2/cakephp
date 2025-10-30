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

namespace Cake\Model;

use AppModel;
use Cake\Core\App;

App::uses('AppModel', 'Model');

/**
 * A model used by TranslateBehavior to access the translation tables.
 *
 * @package Cake.Model
 */
class I18nModel extends AppModel
{
    /**
     * Model name
     *
     * @var string|null
     */
    public ?string $name = 'I18nModel';

    /**
     * Table name
     *
     * @var string|bool|null
     */
    public string|bool|null $useTable = 'i18n';

    /**
     * Display field
     *
     * @var string|bool|null
     */
    public string|bool|null $displayField = 'field';
}
