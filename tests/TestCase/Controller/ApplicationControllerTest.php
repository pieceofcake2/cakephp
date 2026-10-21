<?php

namespace Cake\Test\TestCase\Controller;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Model\Datasource\CakeSession;
use Cake\TestSuite\ControllerTestCase;

App::uses('AppController', 'Controller');

/**
 * ApplicationControllerTest class for testing controllers by using ControllerTestCase.
 *
 * ApplicationControllerTest extends ControllerTestCase in contrast
 * with ControllerTest that extends CakeTestCase.
 *
 * @package       Cake.Test.Case.Controller
 */
class ApplicationControllerTest extends ControllerTestCase
{
    /**
     * setupDown method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');

        CakeSession::destroy();

        Configure::write('Security.salt', 'YJfIxfs2guVoUubWDYhG93b0qyJfIxfs2guwvniR2G0FgaC9mi');
        Configure::write('Security.cipherSeed', 770011223369876);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        try {
            CakeSession::destroy();
        } finally {
            parent::tearDown();
        }
    }

    /**
     * Tests the redirect and session config without cookies.
     *
     * @return void
     */
    public function testRedirect(): void
    {
        // Use the default cache limiter of CakeSession, not the one of the application skeleton
        Configure::delete('Session.cacheLimiter');

        $level = ob_get_level();
        $this->testAction('/trans_session_id/next', [
            'method' => 'GET',
            'return' => 'vars',
        ]);
        while (ob_get_level() > $level) {
            ob_end_clean();
        }

        $this->assertStringContainsString('/trans_session_id/next_step?CAKEPHP=' . CakeSession::id(), $this->headers['Location']);

        $expectedConfig = [
            'cookie' => 'CAKEPHP',
            'timeout' => 240,
            'ini' => [
                'session.cookie_path' => '/',
                'session.cookie_lifetime' => 14400,
                'session.name' => 'CAKEPHP',
                'session.gc_maxlifetime' => 14400,
                'session.cookie_httponly' => 1,
                'session.use_cookies' => 0,
                'session.use_trans_sid' => 0,
            ],
            'defaults' => 'php',
            'cookieTimeout' => 240,
            'cacheLimiter' => 'must-revalidate',
        ];
        $actualConfig = Configure::read('Session');
        $this->assertEquals($expectedConfig, $actualConfig);
    }

    /**
     * Tests the redirect and session config with use_trans_sid=1.
     *
     * @return void
     */
    public function testRedirectWithTransSid(): void
    {
        $this->skipIf(PHP_VERSION_ID >= 80400, 'PHP 8.4 deprecates session.use_trans_sid and disabling session.use_only_cookies.');

        // Use the default cache limiter of CakeSession, not the one of the application skeleton
        Configure::delete('Session.cacheLimiter');
        Configure::write('Session.ini', [
            'session.use_only_cookies' => 0,
            'session.use_trans_sid' => 1,
        ]);
        $sessionId = 'o7k64tlhil9pakp89j6d8ovlqk';

        $level = ob_get_level();
        $this->testAction('/trans_session_id/next?CAKEPHP=' . $sessionId, [
            'method' => 'GET',
            'return' => 'vars',
        ]);
        while (ob_get_level() > $level) {
            ob_end_clean();
        }

        // With session.use_strict_mode (the default since PHP 8.6), an unknown session ID is
        // replaced with a new one, so check that the ID actually used is passed to the redirect
        if (!ini_get('session.use_strict_mode')) {
            $this->assertSame($sessionId, CakeSession::id());
        }
        $this->assertStringContainsString('/trans_session_id/next_step?CAKEPHP=' . CakeSession::id(), $this->headers['Location']);

        $expectedConfig = [
            'cookie' => 'CAKEPHP',
            'timeout' => 240,
            'ini' => [
                'session.use_trans_sid' => 1,
                'session.cookie_path' => '/',
                'session.cookie_lifetime' => 14400,
                'session.name' => 'CAKEPHP',
                'session.gc_maxlifetime' => 14400,
                'session.cookie_httponly' => 1,
                'session.use_cookies' => 0,
                'session.use_only_cookies' => 0,
            ],
            'defaults' => 'php',
            'cookieTimeout' => 240,
            'cacheLimiter' => 'must-revalidate',
        ];
        $actualConfig = Configure::read('Session');
        $this->assertEquals($expectedConfig, $actualConfig);
    }
}
