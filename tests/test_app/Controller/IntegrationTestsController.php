<?php

App::uses('CakeEmail', 'Network/Email');

/**
 * Controller used by IntegrationTestTraitTest.
 *
 * @package       Cake.Test.TestApp.Controller
 */
class IntegrationTestsController extends AppController
{
    /**
     * @var array
     */
    public $uses = [];

    /**
     * @var array
     */
    public $components = ['Session', 'Flash', 'Cookie', 'RequestHandler'];

    /**
     * @var array
     */
    public $helpers = ['Html', 'Flash'];

    /**
     * @var string
     */
    public $layout = 'integration';

    /**
     * Renders a view with a view variable.
     *
     * @return void
     */
    public function index()
    {
        $this->set('title', 'Integration index');
        $this->set('query', $this->request->query);
    }

    /**
     * Echoes the request.
     *
     * @return string
     */
    public function request_info()
    {
        $this->autoRender = false;
        $this->response->type('json');

        return json_encode([
            'method' => $this->request->method(),
            'here' => $this->request->here(),
            'query' => $this->request->query,
            'data' => $this->request->data,
            'input' => $this->request->input(),
            'cookies' => $_COOKIE,
            'referer' => env('HTTP_REFERER'),
            'accept' => $this->request->header('Accept'),
            'host' => env('HTTP_HOST'),
            'https' => $this->request->is('ssl'),
            'files' => $_FILES,
            'remember' => $this->Cookie->read('remember'),
            'user' => $this->Cookie->read('user'),
        ], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Sets a flash message and redirects.
     *
     * @return CakeResponse|null
     */
    public function flash_redirect()
    {
        $this->Flash->success('Saved!');
        $this->Flash->error('Second message', ['params' => ['class' => 'warn']]);

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Sets a flash message and renders it.
     *
     * @return void
     */
    public function flash_render()
    {
        $this->set('title', 'Flash render');
        $this->set('query', []);
        $this->Flash->set('Rendered flash');
        $this->Session->setFlash('Auth message', 'default', [], 'auth');
        $this->render('index');
    }

    /**
     * Redirects with a status code, without returning.
     *
     * @return void
     */
    public function redirect_status()
    {
        $this->redirect('/integration_tests/index', 301);
        $this->Session->write('Integration.afterRedirect', true);
    }

    /**
     * Redirects to the referer.
     *
     * @return CakeResponse|null
     */
    public function redirect_back()
    {
        return $this->redirect($this->referer());
    }

    /**
     * Writes and reads session data.
     *
     * @return string
     */
    public function session_data()
    {
        $this->autoRender = false;
        $this->Session->write('Integration.written', 'stored');

        return (string)$this->Session->read('Auth.User.name');
    }

    /**
     * Writes cookies.
     *
     * @return string
     */
    public function cookies()
    {
        $this->autoRender = false;
        $this->Cookie->write('remember', 'yes');
        $this->Cookie->write('user', ['id' => 1, 'name' => 'mark']);
        $this->Cookie->type('aes');
        $this->Cookie->write('secret', 'aes value');
        $this->Cookie->write('plain', 'not encrypted', false);
        $this->response->cookie(['name' => 'raw', 'value' => 'raw value']);
        $this->response->cookie(['name' => 'empty', 'value' => '']);

        return 'cookies written';
    }

    /**
     * Renders JSON with JsonView.
     *
     * @return void
     */
    public function json()
    {
        $this->viewClass = 'Json';
        $this->set('article', ['id' => 1, 'title' => 'First']);
        $this->set('_serialize', ['article']);
    }

    /**
     * Sets custom headers and a status code.
     *
     * @return string
     */
    public function headers()
    {
        $this->autoRender = false;
        $this->response->header('X-Custom', 'custom value');
        $this->response->header('X-Multiple', ['one', 'two']);
        $this->response->statusCode(201);

        return 'created';
    }

    /**
     * Returns an empty response.
     *
     * @return void
     */
    public function nothing()
    {
        $this->autoRender = false;
    }

    /**
     * Sends a file.
     *
     * @return CakeResponse
     */
    public function file()
    {
        $this->response->file(__FILE__);

        return $this->response;
    }

    /**
     * Throws a NotFoundException.
     *
     * @return void
     * @throws NotFoundException
     */
    public function not_found()
    {
        throw new NotFoundException('Missing integration record');
    }

    /**
     * Throws a RuntimeException.
     *
     * @return void
     * @throws RuntimeException
     */
    public function fail()
    {
        throw new RuntimeException('Integration failure', 0, new LogicException('Root cause'));
    }

    /**
     * Sends emails.
     *
     * @return string
     */
    public function email()
    {
        $this->autoRender = false;
        $email = new CakeEmail([
            'transport' => 'Mail',
            'from' => ['app@example.com' => 'Integration App'],
            'emailFormat' => 'both',
        ]);
        $email->to('alice@example.com')
            ->cc('boss@example.com')
            ->subject('Welcome Alice')
            ->attachments(['notes.txt' => ['data' => 'notes', 'mimetype' => 'text/plain']])
            ->viewVars(['name' => 'Alice'])
            ->template('integration', 'default')
            ->send();

        $second = new CakeEmail(['transport' => 'Mail']);
        $second->from('noreply@example.com')
            ->to(['bob@example.com' => 'Bob'])
            ->subject('Ünïcode subject for Bob')
            ->send('Plain message for Bob');

        return 'sent';
    }
}
