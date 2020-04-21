<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Parser;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Collection\SalesPricesResponse;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\SalesPrice;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Response;
use Psr\Http\Message\ResponseInterface;

class SalesPricesParser extends Parser
{
    /**
     * @return SalesPricesResponse
     */
    public static function parse(ResponseInterface $rawResponse): Response
    {
        $response = self::unserializeJsonResponse($rawResponse);

        $salesPrices = [];
        foreach ($response['entries'] as $salesPrice) {
            $salesPrices[] = new SalesPrice($salesPrice);
        }

        return new SalesPricesResponse(
            $response['page'],
            $response['totalsCount'],
            $response['isLastPage'],
            $salesPrices,
            $response['lastPageNumber'],
            $response['firstOnPage'],
            $response['lastOnPage'],
            (int)$response['itemsPerPage']
        );
    }
}
