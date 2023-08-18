<?php

namespace Line\Helper;

/**
 * Дампер
 *
 * @author  ЛАЙН — Автоматизация бизнеса <sales@line-corp.ru>
 * @version 1.0.0
 */
class Dumper
{

    /**
     * Пишет var_dump с pre форматированием
     *
     * @param $data
     */
    public static function dump($data)
    {
        echo '<pre style="background:#fefefe;border:1px solid #efefef;padding:15px;">';
        var_dump($data);
        echo '</pre>';
    }

}
