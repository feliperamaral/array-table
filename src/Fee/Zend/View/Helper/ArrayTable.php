<?php

namespace Fee\Zend\View\Helper;

use Zend\View\Helper\AbstractHelper;

class ArrayTable extends AbstractHelper
{

    public function __invoke($data, $nameCols = array(), $options = array())
    {
        return \Fee\ArrayTable::render($data, $nameCols, $options);
    }

}
