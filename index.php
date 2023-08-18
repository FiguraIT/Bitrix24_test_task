<?php
// Раскомментируйте, если нужен вывод ошибок в скрипте
//require_once 'error_reporting.php';

use Line\Activity;

// Проверяем, что запуск приложения идет из фрейма внутри Битрикс24
require_once 'check_location.php';

// Включаем автозагрузчик классов
require_once 'autoload.php';

// Веб путь к директории /handler
$handlerPathUrl = 'https://site.ru/handler/';

// Активити
$arActivities = [];

// Подгружаем список активити бизнес-процессов
foreach (glob(__DIR__.'/activity/*.php') as $filePath) {
    $data                = include_once($filePath);
    $arFilePath          = explode('/', $filePath);
    $fileName            = array_pop($arFilePath);
    $code                = strtr($fileName, ['.php' => '']);
    $handler             = $handlerPathUrl.$fileName;
    $arActivities[$code] = array_merge(['CODE' => $code, 'HANDLER' => $handler], $data);
}

// Установка/удаление активити
$delete  = false;
$install = false;
if (isset($_GET['action']) && isset($_GET['code'])) {
    if ($_GET['action'] === 'delete') {
        Activity::delete($_GET['code']);
        $delete = $arActivities[$_GET['code']];
    }
    else if ($_GET['action'] === 'install') {
        Activity::create($arActivities[$_GET['code']]);
        $install = $arActivities[$_GET['code']];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php
    // Дополнительные штуки для упрощения жизни, часто нужны
    //Asset::getJquery();
    //Asset::getBootstrap();
    //Asset::getFontawesome();
    //Asset::getJS('script.js');
    //Asset::getCSS('default.css');
    ?>
</head>
<body>
<h1>Тестовое задание "Приложение бизнес-процессы"</h1>
<p><b>Разработчик:</b> <a href="https://line-corp.ru" target="_blank">ЛАЙН &mdash; Автоматизация бизнеса</a>

<?php // Уведомления об установке/удалении ?>
<?php if ($delete) { ?>
    <div class="alert alert-danger">
        Активити <b>"<?= $delete['NAME'] ?>"</b> успешно удалено.
    </div>
<?php } else if ($install) { ?>
    <div class="alert alert-success">
        Активити <b>"<?= $install['NAME'] ?>"</b> успешно установлено.
    </div>
<?php } ?>
<?php // EOF: Уведомления об установке/удалении ?>


<table class="table table-striped">
    <?php foreach ($arActivities as $item) { ?>
        <tr>
            <td>
                <h4><?= $item['NAME'] ?></h4>
                <p><?= $item['DESCRIPTION'] ?></p>
                <p><small><b>Код:</b> <?= $item['CODE'] ?></small></p>
            </td>
            <td style="text-align:right;">
                <?php if (Activity::exists($item['CODE'])) { ?>
                    <?php
                    $_GET['action'] = 'delete';
                    $_GET['code']   = $item['CODE'];
                    ?>
                    <a href="?<?= http_build_query($_GET) ?>" class="btn btn-danger">Удалить</a>
                <?php } else { ?>
                    <?php
                    $_GET['action'] = 'install';
                    $_GET['code']   = $item['CODE'];
                    ?>
                    <a href="?<?= http_build_query($_GET) ?>" class="btn btn-primary">Установить</a>
                <?php } ?>
            </td>
        </tr>
    <?php } ?>
</table>

</body>
</html>