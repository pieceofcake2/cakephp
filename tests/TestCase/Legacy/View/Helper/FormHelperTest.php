<?php
/**
 * FormHelperTest file
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
 * @package       Cake.Test.Case.View.Helper
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\View\Helper;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Network\CakeRequest;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use Cake\View\Helper\FormHelper;
use Cake\View\Helper\HtmlHelper;
use Cake\View\View;
use Contact;
use UserForm;

App::uses('Contact', 'Model');
App::uses('UserForm', 'Model');

/**
 * ContactTestController class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class ContactTestController extends Controller
{
    /**
     * uses property
     *
     * @var mixed
     */
    public $uses = null;
}

/**
 * FormHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 * @property FormHelper $Form
 */
class FormHelperTest extends CakeTestCase
{
    /**
     * Fixtures to be used
     *
     * @var array
     */
    public $fixtures = ['core.post'];

    /**
     * Do not load the fixtures by default
     *
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
        ]);

        Configure::write('Config.language', 'eng');
        Configure::write('App.base', '');
        Configure::delete('Asset');
        $this->Controller = new ContactTestController();
        $this->View = new View($this->Controller);

        $this->Form = new FormHelper($this->View);
        $this->Form->Html = new HtmlHelper($this->View);
        $this->Form->request = new CakeRequest('contacts/add', false);
        $this->Form->request->here = '/contacts/add';
        $this->Form->request['action'] = 'add';
        $this->Form->request->webroot = '';
        $this->Form->request->base = '';
        Router::setRequestInfo($this->Form->request);

        ClassRegistry::addObject('Contact', new Contact());
        ClassRegistry::addObject('User', new UserForm());

        $this->oldSalt = Configure::read('Security.salt');

        $this->dateRegex = [
            'daysRegex' => 'preg:/(?:<option value="0?([\d]+)">\\1<\/option>[\r\n]*)*/',
            'monthsRegex' => 'preg:/(?:<option value="[\d]+">[\w]+<\/option>[\r\n]*)*/',
            'yearsRegex' => 'preg:/(?:<option value="([\d]+)">\\1<\/option>[\r\n]*)*/',
            'hoursRegex' => 'preg:/(?:<option value="0?([\d]+)">\\1<\/option>[\r\n]*)*/',
            'minutesRegex' => 'preg:/(?:<option value="([\d]+)">0?\\1<\/option>[\r\n]*)*/',
            'meridianRegex' => 'preg:/(?:<option value="(am|pm)">\\1<\/option>[\r\n]*)*/',
        ];

        Configure::write('Security.salt', 'foo!');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Form->Html, $this->Form, $this->Controller, $this->View);
        Configure::write('Security.salt', $this->oldSalt);

        parent::tearDown();
    }

    /**
     * Tests inputs() works with plugin models
     *
     * @return void
     */
    public function testInputsPluginModel(): void
    {
        $this->loadFixtures('Post');
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestLegacyPlugin');
        $this->Form->request['models'] = [
            'TestPluginPost' => ['plugin' => 'TestLegacyPlugin', 'className' => 'TestPluginPost'],
        ];
        $this->Form->create('TestLegacyPlugin.TestPluginPost');
        $result = $this->Form->inputs();

        $this->assertStringContainsString('data[TestPluginPost][id]', $result);
        $this->assertStringContainsString('data[TestPluginPost][author_id]', $result);
        $this->assertStringContainsString('data[TestPluginPost][title]', $result);
        $this->assertTrue(ClassRegistry::isKeySet('TestPluginPost'));
        $this->assertFalse(ClassRegistry::isKeySet('TestLegacyPlugin'));
        $this->assertEquals('TestPluginPost', $this->Form->model());
    }

    /**
     * Tests that a model can be loaded from the model names passed in the request object
     *
     * @return void
     */
    public function testIntrospectModelFromRequest(): void
    {
        $this->loadFixtures('Post');
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestLegacyPlugin');
        $this->Form->request['models'] = ['TestPluginPost' => ['plugin' => 'TestLegacyPlugin', 'className' => 'TestPluginPost']];

        $this->assertFalse(ClassRegistry::isKeySet('TestPluginPost'));
        $this->Form->create('TestPluginPost');
        $this->assertTrue(ClassRegistry::isKeySet('TestPluginPost'));
        $this->assertInstanceOf('TestPluginPost', ClassRegistry::getObject('TestPluginPost'));

        CakePlugin::unload();
        App::build();
    }

    /**
     * testDbLessModel method
     *
     * @return void
     */
    public function testDbLessModel(): void
    {
        $this->Form->create('TestMail');

        $result = $this->Form->input('name');
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'TestMailName'],
            'Name',
            '/label',
            'input' => [
                'name' => 'data[TestMail][name]', 'type' => 'text',
                'id' => 'TestMailName',
            ],
            '/div',
        ];
        $this->assertTags($result, $expected);

        ClassRegistry::init('TestMail');
        $this->Form->create('TestMail');
        $result = $this->Form->input('name');
        $expected = [
            'div' => ['class' => 'input text'],
            'label' => ['for' => 'TestMailName'],
            'Name',
            '/label',
            'input' => [
                'name' => 'data[TestMail][name]', 'type' => 'text',
                'id' => 'TestMailName',
            ],
            '/div',
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * test generation of habtm select boxes.
     *
     * @return void
     */
    public function testHabtmSelectBox(): void
    {
        $this->View->viewVars['contactTags'] = [
            1 => 'blue',
            2 => 'red',
            3 => 'green',
        ];
        $this->Form->request->data = [
            'Contact' => [],
            'ContactTag' => [
                [
                    'id' => '1',
                    'name' => 'blue',
                ],
                [
                    'id' => 3,
                    'name' => 'green',
                ],
            ],
        ];
        $this->Form->create('Contact');
        $result = $this->Form->input('ContactTag', ['div' => false, 'label' => false]);
        $expected = [
            'input' => [
                'type' => 'hidden', 'name' => 'data[ContactTag][ContactTag]', 'value' => '', 'id' => 'ContactTagContactTag_',
            ],
            'select' => [
                'name' => 'data[ContactTag][ContactTag][]', 'id' => 'ContactTagContactTag',
                'multiple' => 'multiple',
            ],
            ['option' => ['value' => '1', 'selected' => 'selected']],
            'blue',
            '/option',
            ['option' => ['value' => '2']],
            'red',
            '/option',
            ['option' => ['value' => '3', 'selected' => 'selected']],
            'green',
            '/option',
            '/select',
        ];
        $this->assertTags($result, $expected);

        // make sure only 50 is selected, and not 50f5c0cf
        $this->View->viewVars['contactTags'] = [
            '1' => 'blue',
            '50f5c0cf' => 'red',
            '50' => 'green',
        ];
        $this->Form->request->data = [
            'Contact' => [],
            'ContactTag' => [
                [
                    'id' => 1,
                    'name' => 'blue',
                ],
                [
                    'id' => 50,
                    'name' => 'green',
                ],
            ],
        ];
        $this->Form->create('Contact');
        $result = $this->Form->input('ContactTag', ['div' => false, 'label' => false]);
        $expected = [
            'input' => [
                'type' => 'hidden', 'name' => 'data[ContactTag][ContactTag]', 'value' => '', 'id' => 'ContactTagContactTag_',
            ],
            'select' => [
                'name' => 'data[ContactTag][ContactTag][]', 'id' => 'ContactTagContactTag',
                'multiple' => 'multiple',
            ],
            ['option' => ['value' => '1', 'selected' => 'selected']],
            'blue',
            '/option',
            ['option' => ['value' => '50f5c0cf']],
            'red',
            '/option',
            ['option' => ['value' => '50', 'selected' => 'selected']],
            'green',
            '/option',
            '/select',
        ];
        $this->assertTags($result, $expected);
    }

    /**
     * Test that required fields are created when only using ModelValidator::add().
     *
     * @return void
     */
    public function testFormInputRequiredDetectionModelValidator(): void
    {
        ClassRegistry::getObject('ContactTag')->validator()->add('iwillberequired', 'required', ['rule' => 'notBlank']);

        $this->Form->create('ContactTag');
        $result = $this->Form->input('ContactTag.iwillberequired');
        $expected = [
            'div' => ['class' => 'input text required'],
            'label' => ['for' => 'ContactTagIwillberequired'],
            'Iwillberequired',
            '/label',
            'input' => [
                'name' => 'data[ContactTag][iwillberequired]',
                'type' => 'text',
                'id' => 'ContactTagIwillberequired',
                'required' => 'required',
            ],
            '/div',
        ];
        $this->assertTags($result, $expected);
    }
}
