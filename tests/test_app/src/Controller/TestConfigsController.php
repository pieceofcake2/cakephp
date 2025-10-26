<?php

namespace TestApp\Controller;

use Cake\Controller\CakeErrorController;

class TestConfigsController extends CakeErrorController
{
    public array $components = [
        'RequestHandler' => [
            'some' => 'config',
        ],
    ];
}
