<?php
require_once 'autoload.php';

use Line\Bitrix\CRest;

$result = CRest::installApp();
?>

<?php if(!$result['rest_only']) { ?>
    <head>
        <script src="//api.bitrix24.com/api/v1/"></script>
        <script>BX24.init(function(){BX24.installFinish();});</script>
    </head>
<?php } ?>
<body>
    <?=($result['install']) ? 'Установка успешно завершена' : 'Ошибка при установке приложения'?>
</body>
