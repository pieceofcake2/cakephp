<?php
/**
 * Test App DeviceType Model
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
 * DeviceType class
 *
 * @package       Cake.Test.TestApp.Model
 */
class DeviceType extends CakeTestModel
{
    /**
     * name property
     *
     * @var string
     */
    public $name = 'DeviceType';

    /**
     * order property
     *
     * @var array
     */
    public $order = ['DeviceType.order' => 'ASC'];

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = [
        'DeviceTypeCategory', 'FeatureSet', 'ExteriorTypeCategory',
        'Image' => ['className' => 'Document'],
        'Extra1' => ['className' => 'Document'],
        'Extra2' => ['className' => 'Document']];

    /**
     * hasMany property
     *
     * @var array
     */
    public $hasMany = ['Device' => ['order' => ['Device.id' => 'ASC']]];
}
