<?php
/**
 * @var array $treeData
 * К сожалению, в последний момент я выяснила, что у меня почему-то не работают короткие тэги. РАзбираться сил нет. Поэтому в шаблоне так.
 */
?>
<table>
    <tr>
        <th>Позиция</th>
        <th>Заголовок</th>
        <th>Значение</th>
    </tr>
    <?php foreach ($treeData as $row) {?>
        <tr>
            <td><?php echo $row['fullPosition'];?></td>
            <td><?php echo $row['title'];?></td>
            <td><?php echo $row['value'];?></td>
        </tr>
    <?php }?>
</table>