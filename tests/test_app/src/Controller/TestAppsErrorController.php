<?php

namespace TestApp\Controller;

use Cake\Controller\CakeErrorController;

class TestAppsErrorController extends CakeErrorController
{
    public $helpers = [
        'Html',
        'Session',
        'Form',
        'Banana',
    ];
}
