<?php

namespace Line\Helper;

/**
 * Класс для подключения часто используемых библиотек по CDN
 *
 * @author  ЛАЙН — Автоматизация бизнеса <sales@line-corp.ru>
 * @verison 1.1.1
 */
class Asset
{

    /**
     * Get latest Bootstrap CSS and JS
     */
    public static function getBootstrap()
    {
        echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">';
        echo '<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>';
    }

    /**
     * Get latest jQuery
     */
    public static function getJquery()
    {
        echo '<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>';
    }

    /**
     * Get Fontawesome
     */
    public static function getFontawesome()
    {
        echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">';
    }

    /**
     * Include Javascript file
     *
     * @param string $path
     */
    public static function getJS($path)
    {
        $path = static::preparePath($path, 'js');
        echo "<script src=\"{$path}\"></script>";
    }

    /**
     * Include CSS file
     *
     * @param string $path
     */
    public static function getCSS($path)
    {
        $path = static::preparePath($path, 'css');
        echo "<link type=\"text/css\" rel=\"stylesheet\" href=\"{$path}\">";
    }

    /**
     * Get application assets directory
     *
     * @return string
     */
    private static function getAssetsDir()
    {
        $documentUri = $_SERVER['DOCUMENT_URI'];

        if (strpos($documentUri, '.php') !== false) {
            $documentUri = preg_replace('#\/[^/]*$#', '', $documentUri);
        }

        return $documentUri.'/assets';
    }

    /**
     * Prepare requested asset path
     *
     * @param string $path
     * @param string $folder
     *
     * @return string
     */
    private static function preparePath($path, $folder)
    {
        $path = htmlspecialchars($path);
        $folder = htmlspecialchars($folder);

        return static::getAssetsDir().'/'.$folder.'/'.$path;
    }

}
