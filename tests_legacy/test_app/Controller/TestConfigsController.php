<?php

class TestConfigsController extends CakeErrorController
{
    public $components = [
        'RequestHandler' => [
            'some' => 'config',
        ],
    ];
}
