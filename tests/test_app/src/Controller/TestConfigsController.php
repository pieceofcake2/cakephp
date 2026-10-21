<?php

namespace TestApp\Controller;

use Cake\Controller\CakeErrorController;

class TestConfigsController extends CakeErrorController
{
    public $components = [
        'RequestHandler' => [
            'some' => 'config',
        ],
    ];
}
