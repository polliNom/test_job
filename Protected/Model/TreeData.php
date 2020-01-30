<?php
namespace TreeDataManager\Model;

use TreeDataManager\Core\DB;
use TreeDataManager\Exception\IncorrectDataException;

/**
 * Модель дерева данных
 * Принимает на вход данные в исходном виде, сохраняет их в нужной структуре
 */
class TreeData
{
    /**
     * Сохранить данные в таблицу.
     * Если будет добавлена запись для корня, которого ещё нет - будет создан пустой элемент. Например таблица пуста,
     * идёт попытка создать элемент 1.4.2, будет создано:
     * id | position | parent | fullPosition | title
     * 1  | 1        | NULL   | "1"          | NULL
     * 2  | 4        | 1      | "1.4"        | NULL
     * 3  | 2        | 2      | "1.4.2"      | "some value"
     *
     * Если потом будет попытка добавить корень 1, то заголовок и значение будут обновлены (если они были пусты)
     *
     * Таким образом обеспечивается целостность данных в БД и возможность писать в таблицу неупорядоченные данные,
     * но ценой нескольких лишних update в базе, что не критично.
     *
     * @param string $position Позиция, например "1.4.2"
     * @param string $title Заголовок
     * @param string $value Значение
     * @throws IncorrectDataException
     *
     * @todo Сложная логика, нужны тесты для метода и подметодов
     */
    public function insert(string $position, string $title, string $value): void
    {
        $posList = explode('.', trim($position, '.'));
        $currentPos = null;
        $parent = null;
        foreach ($posList as $pos) {
            $pos = (int)$pos; // @todo Кинуть Exception, если $pos не чистый Int
            if (is_null($currentPos)) {
                $currentPos = (string)$pos;
            } else {
                $currentPos .= ".$pos";
            }

            if ($currentPos === $position) { // Добавляем основной элемент
                $parent = $this->insertElementWithParent($pos, $parent, $currentPos, $title, $value);
            } else { // Добавляем промежуточные звенья, если их нет
                $parent = $this->insertNodeIfNotExists($pos, $parent, $currentPos);
            }
        }
    }

    /**
     * Взять строки, отсортированные в пригодном для вывода виде
     * @return array[]
     */
    public function getFullTree(): array
    {
        $query = DB::connection()->query('SELECT * FROM `TreeData` ORDER BY `fullPosition`');
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Взять срез строк дерева по родителю
     * @param int|null $parent
     * @return array[]
     */
    public function getRecursiveTree(?int $parent = null): array
    {
        $sql = 'SELECT p.*, (SELECT count(*) FROM `TreeData` c WHERE c.`parent` = p.`id`) as childs
            FROM `TreeData` p WHERE `parent` %s ORDER BY `fullPosition`';
        if (is_null($parent)) {
            $query = DB::connection()->query(sprintf($sql, 'IS NULL'));
        } else {
            $query = DB::connection()->prepare(sprintf($sql, '= :parent'));
            $query->execute([':parent' => $parent]);
        }
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     *
     * @param int $position
     * @param int|null $parent
     * @param string $fullPosition
     * @param string|null $title
     * @param string|null $value
     * @return int Идентификатор вставленной/обновлённой записи
     * @throws IncorrectDataException
     */
    private function insertElementWithParent(
        int $position,
        ?int $parent,
        string $fullPosition,
        ?string $title,
        ?string $value
    ): int
    {
        $currentRow = $this->getElementByFullPosition($fullPosition);
        if ($currentRow === false) { // Такого элемента нет
            return $this->insertRow($position, $parent, $fullPosition, $title, $value);
        } elseif (is_null($currentRow->title) && is_null($currentRow->value)) { // Элемент есть, но пуст
            $this->updateRow($currentRow->id, $title, $value);
            return $currentRow->id;
        } else { // Элемент есть и не пуст
            throw new IncorrectDataException(
                sprintf('Строка %s не может быть добавлена, так как на этой позици уже есть непустой элемент', $fullPosition)
            );
        }
    }

    /**
     * Добавить пустой элемент, если его нет
     * @param int $position
     * @param int|null $parent
     * @param string $fullPosition
     * @return int идентификатор добавленного элемента
     */
    private function insertNodeIfNotExists(int $position, ?int $parent, string $fullPosition): int
    {
        $currentRow = $this->getElementByFullPosition($fullPosition);
        if ($currentRow === false) {
            return $this->insertRow($position, $parent, $fullPosition);
        }
        return $currentRow['id'];
    }

    /**
     * Взять элемент по полной позиции
     * @param string $fullPosition
     * @return bool|\stdClass возврат \PDO::fetch
     */
    public function getElementByFullPosition(string $fullPosition)
    {
        $query = DB::connection()->prepare('SELECT * FROM `TreeData` WHERE `fullPosition` = :fullPosition');
        $query->execute([':fullPosition' => $fullPosition]);
        return $query->fetch(\PDO::FETCH_LAZY);
    }

    /**
     * Добавляет строку в таблицу как есть
     * @param int $position
     * @param int|null $parent
     * @param string $fullPosition
     * @param string|null $title
     * @param string|null $value
     * @return int Идентификатор вставленной записи
     */
    protected function insertRow(int $position, ?int $parent, string $fullPosition, ?string $title = null, ?string $value = null): int
    {
        $query = DB::connection()->prepare(
            'INSERT INTO `TreeData` (`position`, `parent`, `fullPosition`, `title`, `value`)
            VALUES (:position, :parent, :fullPosition, :title, :value)'
        );
        $query->execute(
            [
                ':position'     => $position,
                ':parent'       => $parent,
                ':fullPosition' => $fullPosition,
                ':title'        => $title,
                ':value'        => $value
            ]
        );
        return DB::connection()->lastInsertId();
    }

    /**
     * Обновить строку, которая является пустой
     * @param int $id
     * @param string|null $title
     * @param string|null $value
     */
    protected function updateRow(int $id, ?string $title, ?string $value): void
    {
        $query = DB::connection()->prepare(
            'UPDATE `TreeData`  SET `title` = :title, `value` = :value
            WHERE `id` = :id AND `title` IS NULL AND `value` IS NULL' // Перестраховка для того, чтобы данные не перезаписывались
        );
        $query->execute(
            [
                ':id'    => $id,
                ':title' => $title,
                ':value' => $value
            ]
        );
    }
}