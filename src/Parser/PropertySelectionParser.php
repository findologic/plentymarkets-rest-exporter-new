<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Parser;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Collection\PropertySelectionResponse;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Property\Selection;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Response;
use Psr\Http\Message\ResponseInterface;

class PropertySelectionParser extends Parser
{
    /**
     * @return PropertySelectionResponse
     */
    public static function parse(ResponseInterface $rawResponse): Response
    {
        $response = self::unserializeJsonResponse($rawResponse);

        $selections = [];
        foreach ($response['entries'] as $selection) {
            $selections[] = new Selection($selection);
        }

        return new PropertySelectionResponse(
            $response['page'],
            (int)$response['totalsCount'],
            $response['isLastPage'],
            $selections,
            (int)$response['lastPageNumber'],
            (int)$response['firstOnPage'],
            (int)$response['lastOnPage'],
            (int)$response['itemsPerPage']
        );
    }
}
