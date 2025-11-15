<?php
/**
 * AclComponentTest file
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
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 1.2.0.5435
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\Component\Acl\AclInterface;
use Cake\Controller\Component\AclComponent;
use Cake\Controller\ComponentCollection;
use Cake\Core\Configure;
use Cake\Error\CakeException;
use Cake\TestSuite\CakeTestCase;
use stdClass;
use TypeError;

/**
 * Test Case for AclComponent
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class AclComponentTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        if (!class_exists('MockAclImplementation', false)) {
            $this->getMock(AclInterface::class, [], [], 'MockAclImplementation');
        }
        Configure::write('Acl.classname', 'MockAclImplementation');
        $Collection = new ComponentCollection();
        $this->Acl = new AclComponent($Collection);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Acl);
        parent::tearDown();
    }

    /**
     * test that constructor throws an exception when Acl.classname is a
     * non-existent class
     *
     * @return void
     */
    public function testConstrutorException()
    {
        $this->expectException(CakeException::class);
        Configure::write('Acl.classname', 'AclClassNameThatDoesNotExist');
        $Collection = new ComponentCollection();
        new AclComponent($Collection);
    }

    /**
     * test that adapter() allows control of the internal implementation AclComponent uses.
     *
     * @return void
     */
    public function testAdapter()
    {
        $Adapter = $this->getMock(AclInterface::class);
        $Adapter->expects($this->once())->method('initialize')->with($this->Acl);

        $this->assertNull($this->Acl->adapter($Adapter));
        $this->assertEquals($this->Acl->adapter(), $Adapter, 'Returned object is different %s');
    }

    /**
     * test that adapter() whines when the class does not implement AclInterface
     *
     * @return void
     */
    public function testAdapterException()
    {
        $this->expectException(TypeError::class);

        $thing = new stdClass();
        $this->Acl->adapter($thing);
    }
}
