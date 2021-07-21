<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity;

interface Translatable
{
    public function getLang(): string;
}
