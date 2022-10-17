<?php

namespace App;

/**
 * NOTE: Предполагаю, что взял библиотеку Антона
 * https://github.com/davydovanton/event_schema_registry
 * Но так как не руби, тут пустой класс с пометкой
 * Будет в каждом проекте копипастой - предполагаю, что подключил как либу
 *
 * TODO: Реализовать логику SchemaRegistry
 */
class SchemaRegistry
{
    public static function validateEvent($data, $type, $version = 1): bool
    {
        return true;
    }
}
