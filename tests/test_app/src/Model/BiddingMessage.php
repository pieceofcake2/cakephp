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

use Cake\TestSuite\Fixture\CakeTestModel;

/**
 * BiddingMessage class
 *
 * @package       Cake.Test.Case.Model
 */
class BiddingMessage extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'BiddingMessage';

    /**
     * primaryKey property
     *
     * @var string|null
     */
    public $primaryKey = 'bidding';

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = [
        'Bidding' => [
            'foreignKey' => false,
            'conditions' => ['BiddingMessage.bidding = Bidding.bid'],
        ],
    ];
}
