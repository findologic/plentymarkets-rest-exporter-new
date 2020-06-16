<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Parser;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Collection\VatResponse;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\VatConfiguration;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Response;
use Psr\Http\Message\ResponseInterface;

class VatParser extends Parser
{
    public static function parse(ResponseInterface $rawResponse): VatResponse
    {
        $response = self::unserializeJsonResponse($rawResponse);

        $vatConfigurations = [];
        foreach ($response['entries'] as $vatConfiguration) {
            $vatConfigurations[] = new VatConfiguration($vatConfiguration);
        }

        return new VatResponse(
            $response['page'],
            $response['totalsCount'],
            $response['isLastPage'],
            $vatConfigurations,
            $response['lastPageNumber'],
            $response['firstOnPage'],
            $response['lastOnPage'],
            (int)$response['itemsPerPage']
        );
    }
}
