<?php

namespace TestApp\View\Helper;

use Cake\View\Helper;

class BananaHelper extends Helper
{
    public function peel()
    {
        return '<b>peeled</b>';
    }
}
