<?php

namespace Line\Helper;

/**
 * Дебаггер
 * @author  ЛАЙН — Автоматизация бизнеса <sales@line-corp.ru>
 * @version 1.0.0
 */
class Debugger
{

    private static $instance;
    private $timer = [];

    /**
     * Получаем инстанцию класса дебагера
     *
     * @return static
     */
    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Время начала таймера - текущее
     */
    public function startTimer()
    {
        $this->timer['start'] = microtime(true);
    }

    /**
     * Время окончания таймера - текущее
     */
    public function stopTimer()
    {
        $this->timer['stop'] = microtime(true);
    }

    /**
     * Разницу между временем начала и окончания таймера
     *
     * @return string
     */
    public function getTimer()
    {
        if (!isset($this->timer['start'])) {
            return 'ОШИБКА ТАЙМЕРА: Используйте startTimer() перед getTimer()';
        }

        if (!isset($this->timer['stop'])) {
            return 'ОШИБКА ТАЙМЕРА: Используйте stopTimer() перед getTimer()';
        }

        $diff = $this->timer['stop'] - $this->timer['start'];

        return "Затрачено времени: {$diff} сек";
    }

}