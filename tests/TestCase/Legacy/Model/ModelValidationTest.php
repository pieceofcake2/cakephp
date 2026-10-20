<?php
/**
 * ModelValidationTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Model;

use Cake\Core\App;
use Cake\Core\Configure;
use ValidationTest1;

App::uses('ValidationTest1', 'Model');

/**
 * ModelValidationTest
 *
 * @package       Cake.Test.Case.Model
 */
class ModelValidationTest extends BaseModelTest
{
    /**
     * override locale to the default (eng).
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('Config.language', 'eng');
    }

    /**
     * Test that if a behavior modifies the model's whitelist validation gets triggered
     * properly for those fields.
     *
     * @return void
     */
    public function testValidateWithFieldListAndBehavior(): void
    {
        $TestModel = new ValidationTest1();
        $TestModel->validate = [
            'title' => [
                'rule' => 'notBlank',
            ],
            'name' => [
                'rule' => 'notBlank',
            ]];
        $TestModel->Behaviors->attach('ValidationRule', ['fields' => ['name']]);

        $data = [
            'title' => '',
            'name' => '',
        ];
        $result = $TestModel->save($data, ['fieldList' => ['title']]);
        $this->assertFalse($result);

        $expected = ['title' => ['This field cannot be left blank'], 'name' => ['This field cannot be left blank']];
        $this->assertEquals($expected, $TestModel->validationErrors);
    }

    /**
     * Test validation message translation
     *
     * @return void
     */
    public function testValidationMessageTranslation(): void
    {
        $lang = Configure::read('Config.language');
        Configure::write('Config.language', 'en');
        App::build([
            'Locale' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Locale' . DS],
        ], App::RESET);

        $TestModel = new ValidationTest1();
        $TestModel->validationDomain = 'validation_messages';
        $TestModel->validate = [
            'title' => [
                [
                    'rule' => ['customValidationMethod', 'arg1'],
                    'required' => true,
                    'message' => 'Validation failed: %s',
                ],
            ],
        ];

        $TestModel->create();
        $expected = [
            'title' => [
                'Translated validation failed: Translated arg1',
            ],
        ];
        $TestModel->invalidFields();
        $this->assertEquals($expected, $TestModel->validationErrors);

        $TestModel->validationDomain = 'default';
        Configure::write('Config.language', $lang);
        App::build();
    }
}
