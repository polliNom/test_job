<?php


namespace TreeDataManager\Controller;

use TreeDataManager\Exception\TreeDataManagerException;
use TreeDataManager\Model\TreeData;

/**
 * Основной контроллер
 */
class Main
{
    /**
     * "Роутинг"
     */
    public function Run(): void
    {
        require_once('Protected/Templates/Main.php');
        if (isset($_GET['load'])) {
            $this->LoadData();
        } else {
            $this->showData();
        }
    }

    /**
     * Загрузить данные в таблицу из некого источника (файла)
     */
    private function LoadData(): void
    {
        $t = new TreeData();
        $data = require('Files/dataForInsert.php');
        foreach ($data as $row) {
            try {
                $t->insert($row['position'], $row['title'], $row['value']);
            } catch (TreeDataManagerException $e) {
                echo 'Ошибка: '. $e->getMessage() . '<br>';
            }
        }
    }

    /**
     * Показать данные
     */
    private function showData(): void
    {
        $t = new TreeData();
        $treeData = $t->getFullTree();
        require_once('Protected/Templates/Tree.php');
    }
}