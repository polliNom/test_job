<?php
spl_autoload_register(function ($className)
{
    if (strpos($className, 'TreeDataManager') === 0) {
        $className = substr($className, strlen('TreeDataManager') + 1);
    }
    include_once 'Protected/' . str_replace('\\' , '/', $className) . '.php';
});

use TreeDataManager\Controller\Main;
(new Main())->Run();