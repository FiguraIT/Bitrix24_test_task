<?php

namespace Line;

use Line\Bitrix\CRest;

/**
 * Класс для работы с активити бизнес-процессов
 *
 * @author ЛАЙН — Автоматизация бизнеса <sales@line-corp.ru>
 * @version 1.1
 */
class Activity
{

    /**
     * Проверяем установлено ли активити
     *
     * @param $code
     *
     * @return bool
     */
    public static function exists($code)
    {
        $listActivities = CRest::call('bizproc.activity.list');

        return array_search($code, $listActivities['result']) !== false;
    }

    /**
     * Установка активити
     *
     * @param array $params
     *
     * @return array|bool
     */
    public static function create($params = [])
    {
        return (!self::exists($params['CODE']))
            ? CRest::call('bizproc.activity.add', $params)
            : false;
    }

    /**
     * Удаление активити
     *
     * @param $code
     *
     * @return array|bool
     */
    public static function delete($code)
    {
        return (self::exists($code))
            ? CRest::call('bizproc.activity.delete', ['CODE' => $code])
            : false;
    }

}
