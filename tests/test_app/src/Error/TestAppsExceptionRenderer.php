<?php

namespace TestApp\Error;

use Cake\Controller\Controller;
use Cake\Error\ExceptionRenderer;
use Cake\Network\CakeRequest;
use Cake\Network\CakeResponse;
use Cake\Routing\Router;
use Exception;
use TestApp\Controller\TestAppsErrorController;

class TestAppsExceptionRenderer extends ExceptionRenderer
{
    protected function _getController($exception): Controller
    {
        if (!$request = Router::getRequest(true)) {
            $request = new CakeRequest();
        }
        $response = new CakeResponse();
        try {
            $controller = new TestAppsErrorController($request, $response);
            $controller->layout = 'banana';
        } catch (Exception) {
            $controller = new Controller($request, $response);
            $controller->viewPath = 'Errors';
        }

        return $controller;
    }
}
