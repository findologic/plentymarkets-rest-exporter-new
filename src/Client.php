<?php

namespace FINDOLOGIC\PlentyMarketsRestExporter;

use FINDOLOGIC\PlentyMarketsRestExporter\Exception\AuthorizationException;
use FINDOLOGIC\PlentyMarketsRestExporter\Exception\CriticalException;
use FINDOLOGIC\PlentyMarketsRestExporter\Exception\CustomerException;
use FINDOLOGIC\PlentyMarketsRestExporter\Exception\ThrottlingException;
use FINDOLOGIC\PlentyMarketsRestExporter\Request\Request;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Response;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\WebstoreResponse;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\RequestOptions;
use Log4Php\Logger;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Client
{
    private const
        PLENTY_SHORT_PERIOD_CALLS_HEADER = 'X-Plenty-Global-Short-Period-Calls-Left',
        PLENTY_SHORT_PERIOD_DECAY_HEADER = 'X-Plenty-Global-Short-Period-Decay';

    private const
        PROTOCOL_HTTP = 'http',
        PROTOCOL_HTTPS = 'https';

    /** @var GuzzleClient */
    private $client;

    /** @var Config */
    private $config;

    /** @var Logger */
    private $internalLogger;

    /** @var Logger */
    private $customerLogger;

    /** @var string */
    private $protocol = self::PROTOCOL_HTTPS;

    /** @var ResponseInterface */
    private $lastResponse;

    /** @var string */
    private $accessToken;

    /** @var string */
    private $refreshToken;

    public function __construct(
        GuzzleClient $httpClient,
        Config $config,
        ?Logger $internalLogger = null,
        ?Logger $customerLogger = null
    ) {
        $this->client = $httpClient;
        $this->config = $config;
        $this->internalLogger = $internalLogger;
        $this->customerLogger = $customerLogger;
    }

    private function doRequest(Request $request): Response
    {
        $this->handleRateLimit();
        $this->handleLogin();

        $guzzleRequest = new GuzzleRequest(
            $request->getMethod(),
            $this->buildRequestUri($request->getEndpoint()),
            $request->getHeaders(),
            $request->getBody()
        );

        $response = $this->sendRequest($guzzleRequest);

        $this->handleResponse($guzzleRequest, $response);

        // TODO: json_encode the value and cast it to the respective response class.
        $responseClass = $request->getResponseClass($response);
        return new $responseClass();
    }

    private function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = $this->client->send(
            $request,
            [
                RequestOptions::HTTP_ERRORS => false,
                RequestOptions::ALLOW_REDIRECTS => true
            ]
        );
        $this->lastResponse = $response;

        return $response;
    }

    private function handleResponse(RequestInterface $request, ResponseInterface $response): void
    {
        switch ($response->getStatusCode()) {
            case 401:
                throw new AuthorizationException('The REST client is not logged in.');
            case 403:
                throw new CustomerException(sprintf(
                    'The REST client does not have access rights for method with URI "%s"',
                    $request->getUri()->__toString()
                ));
            case 429:
                throw new ThrottlingException('Throttling limit reached.');
            case 200:
                if (empty($response->getBody()->getContents())) {
                    throw new CustomerException(sprintf(
                        'The API for URI "%s" responded with status code %d',
                        $request->getUri()->__toString(),
                        $response->getStatusCode()
                    ));
                }
                break;
            default:
                throw new CustomerException(sprintf(
                    'Could not reach API method with URI "%s"',
                    $request->getUri()->__toString()
                ));
        }
    }

    private function handleLogin(): void
    {
        if (!$this->refreshToken) {
            $this->doLogin();
        }
    }

    private function doLogin(): void
    {
        $request = new GuzzleRequest(
            'POST',
            $this->buildRequestUri('login'),
            [
                'username' => $this->config->getUsername(),
                'password' => $this->config->getPassword(),
            ]
        );

        $response = $this->sendRequest($request);
        if ($response->getStatusCode() >= 301 && $response->getStatusCode() <= 404) {
            $this->protocol = self::PROTOCOL_HTTP;

            $response = $this->sendRequest($request);
        }

        $this->handleLoginResponse($request, $response);
    }

    private function handleLoginResponse(RequestInterface $request, ResponseInterface $response): void
    {
        if ($response->getStatusCode() !== 200) {
            throw new CriticalException(sprintf(
                'Unable to connect to the REST API via "%s".',
                $request->getUri()->__toString()
            ));
        }

        $data = json_decode($response);
        if (!property_exists($data, 'accessToken')) {
            throw new CriticalException(
                'Wrong username or password. The response does not contain an access token.'
            );
        }

        $this->accessToken = $data->accessToken;
        $this->refreshToken = $data->refreshToken;
    }

    private function refreshLogin(): void
    {

    }

    private function handleRateLimit(): void
    {
        if ($this->lastResponse && $this->isRateLimited()) {
            sleep($this->getRateLimitWaitTimeInSeconds());
        }
    }

    private function isRateLimited(): bool
    {
        $requestsLeft = (int)$this->lastResponse->getHeaderLine(self::PLENTY_SHORT_PERIOD_CALLS_HEADER);

        return $requestsLeft <= 1;
    }

    private function getRateLimitWaitTimeInSeconds(): int
    {
        return (int)$this->lastResponse->getHeaderLine(self::PLENTY_SHORT_PERIOD_DECAY_HEADER);
    }

    private function buildRequestUri(string $endpoint): string
    {
        return sprintf('%s://%s/%s', $this->protocol, $this->config->getDomain(), $endpoint);
    }

    public function getWebStores(): WebstoreResponse
    {

    }
}
