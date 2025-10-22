<?php

namespace TestApp\Error;

use Cake\Error\ExceptionRenderer;
use Cake\Network\Request as CakeRequest;
use Cake\Network\Response as CakeResponse;
use Cake\Routing\Router;
use Cake\Controller\Controller;
use TestApp\Controller\TestAppsErrorController;
use Exception;

class TestAppsExceptionRenderer extends ExceptionRenderer
{
    protected function _getController($exception)
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
