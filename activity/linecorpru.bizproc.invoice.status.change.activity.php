<?php
/**
 * Активити изменения статуса счета
 */

// Получаем статусы счетов
$arInvoiceStatus = \Line\Bitrix\CRest::call('crm.status.entity.items', ['entityId' => 'INVOICE_STATUS']);

$arInvoicesStatusFormatted = [];
foreach ($arInvoiceStatus['result'] as $arStatus) {
    $arInvoicesStatusFormatted[$arStatus['STATUS_ID']] = $arStatus['NAME'];
}

return [
    'NAME'        => 'Изменить статус счета',
    'DESCRIPTION' => 'Изменить статус счета',
    'PROPERTIES'  => [
        'invoice_id'     => [
            'Name'     => 'ID счета',
            'Type'     => 'string',
            'Required' => 'Y',
        ],
        'invoice_status' => [
            'Name'     => 'Новый статус счета',
            'Type'     => 'select',
            'Required' => 'Y',
            'Multiple' => 'N',
            'Options'  => $arInvoicesStatusFormatted,
        ],
    ],
];
