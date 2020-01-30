# Таблица для хранения данных в древовидной структуре
CREATE TABLE `TreeData` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,

  # Позиция текущего элемента в текущей ветке дерева (например для полного пути 1.3.2 позиция будет 2)
  `position` INT NOT NULL,

  # Ссылка на родительский элемент
  `parent` INT,

  # Полный путь позиционировая элемента, включая родительские элементы (например '1.3.2')
  `fullPosition` VARCHAR(255) NOT NULL,

  # Заголовок
  `title` NVARCHAR(5000),

  # Значение
  `value` NVARCHAR(5000),

  CONSTRAINT `TreeData_self_parent` FOREIGN KEY (parent) REFERENCES `TreeData` (id)
)