<?php

namespace TestApp\Controller;

use Cake\Controller\CakeErrorController;

class TestAppsErrorController extends CakeErrorController
{
    public array $helpers = [
        'Html',
        'Session',
        'Form',
        'Banana',
    ];
}
