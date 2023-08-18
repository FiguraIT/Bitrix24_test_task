<?php

namespace Line\Helper;

/**
 * Простейший логер
 * Собирает данные и пишет в указанный файл
 *
 * @author  ЛАЙН — Автоматизация бизнеса <sales@line-corp.ru>
 * @version 1.1
 * @created 11.06.2019
 */
class Logger
{

    /**
     * @var string Хранилище данных
     */
    private $log = '';

    /**
     * @var string Путь к файлу
     */
    private $logfile;

    /**
     * @var bool Разрешение на запись
     */
    private $write;

    /**
     * Инициализация логера
     *
     * @param string $logfile Путь к файлу
     * @param bool   $write   Разрешение на запись
     */
    public function __construct($logfile, $write = true)
    {
        $this->logfile = $logfile;
        $this->write   = $write;
    }

    /**
     * Добавляем данные
     *
     * @param $input
     */
    public function add($input)
    {
        if (is_array($input))
            $input = json_encode($input);

        $this->log .= "\n{$input}\n";
    }

    /**
     * Пишем лог в файл
     */
    public function flush()
    {
        if ($this->canWrite()) {
            file_put_contents($this->logfile, $this->log, FILE_APPEND);
        }
    }

    /**
     * Проверяем разрешение на запись
     * Например, если нам не нужно логирование на продакшене,
     * можем установить свойство "write" как FALSE
     *
     * @return bool
     */
    private function canWrite()
    {
        return $this->write;
    }

}
