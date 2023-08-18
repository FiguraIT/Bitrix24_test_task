<?php
require_once __DIR__.'/../autoload.php';

Line\Bitrix\CRest::callBatch([
    [
        'method' => 'crm.invoice.update',
        'params' => [
            'id'     => intval($_REQUEST['properties']['invoice_id']),
            'fields' => ['STATUS_ID' => $_REQUEST['properties']['invoice_status']],
        ],
    ],
    [
        'method' => 'bizproc.event.send',
        'params' => ['event_token' => $_REQUEST['event_token']],
    ],
]);
