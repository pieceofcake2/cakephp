<?php

/**
 * Controller with SecurityComponent used by IntegrationTestTraitTest.
 *
 * @package       Cake.Test.TestApp.Controller
 */
class IntegrationSecurityController extends AppController
{
    /**
     * @var array
     */
    public $uses = [];

    /**
     * @var array
     */
    public $components = ['Session', 'Security'];

    /**
     * @var array
     */
    public $helpers = ['Html', 'Form'];

    /**
     * Configures SecurityComponent.
     *
     * @return void
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        if ($this->request->params['action'] === 'csrf_only') {
            $this->Security->validatePost = false;
        }
        if ($this->request->params['action'] === 'unlocked') {
            $this->Security->unlockedFields = ['Article.preview'];
        }
    }

    /**
     * Renders a secured form, or echoes the posted data.
     *
     * @return string|null
     */
    public function add()
    {
        if ($this->request->is('post')) {
            $this->autoRender = false;

            return json_encode($this->request->data);
        }

        return null;
    }

    /**
     * Echoes the posted data (validatePost is disabled).
     *
     * @return string
     */
    public function csrf_only()
    {
        $this->autoRender = false;

        return json_encode($this->request->data);
    }

    /**
     * Echoes the posted data (Article.preview is unlocked).
     *
     * @return string
     */
    public function unlocked()
    {
        $this->autoRender = false;

        return json_encode($this->request->data);
    }
}
