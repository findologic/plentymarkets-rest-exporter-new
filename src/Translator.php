<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Translatable;

class Translator
{
    /**
     * @param Translatable[] $entities
     */
    public static function translate(array $entities, string $lang): ?Translatable
    {
        foreach ($entities as $entity) {
            if (strtoupper($entity->getLang()) === strtoupper($lang)) {
                return $entity;
            }
        }

        return null;
    }

    /**
     * @param Translatable[] $entities
     * @return Translatable[]
     */
    public static function translateMultiple(array $entities, string $lang): array
    {
        $results = [];

        foreach ($entities as $entity) {
            if (strtoupper($entity->getLang()) === strtoupper($lang)) {
                $results[] = $entity;
            }
        }

        return $results;
    }
}
