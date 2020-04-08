<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter;

use FINDOLOGIC\PlentyMarketsRestExporter\Exception\AuthorizationException;
use FINDOLOGIC\PlentyMarketsRestExporter\Exception\CriticalException;
use FINDOLOGIC\PlentyMarketsRestExporter\Exception\CustomerException;
use FINDOLOGIC\PlentyMarketsRestExporter\Exception\ThrottlingException;
use FINDOLOGIC\PlentyMarketsRestExporter\Request\Request;
use FINDOLOGIC\PlentyMarketsRestExporter\Request\WebStoreRequest;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Response;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\WebStoreResponse;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Uri;
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

    private const
        REST_PATH = 'rest';

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

    /** @var ResponseInterface Used for rate limiting. */
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

    public function send(GuzzleRequest $request): ResponseInterface
    {
        $this->handleRateLimit();
        $this->handleLogin();

        $request = $request->withUri($this->buildRequestUri($request->getUri()->__toString()));
        $response = $this->sendRequest($request);
        $this->handleResponse($request, $response);

        return $response;
    }

    private function sendRequest(RequestInterface $request, array $params = null): ResponseInterface
    {
        $this->customerLogger->info(sprintf(
            'Getting data from: %s',
            $request->getUri()->__toString()
        ));

        if ($this->accessToken) {
            $request = $request->withAddedHeader('Authorization', 'Bearer ' . $this->accessToken);
        }

        $response = $this->client->send(
            $request,
            [
                RequestOptions::HTTP_ERRORS => false,
                RequestOptions::FORM_PARAMS => $params,
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
                if (empty($response->getBody()->__toString())) {
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
        $this->customerLogger->info('Trying to log into the Plentymarkets REST API...');

        $request = new GuzzleRequest(
            'POST',
            $this->buildRequestUri('login'),
        );
        $params = [
            'username' => $this->config->getUsername(),
            'password' => $this->config->getPassword()
        ];

        $response = $this->sendRequest($request, $params);
        if ($response->getStatusCode() >= 301 && $response->getStatusCode() <= 404) {
            $this->protocol = self::PROTOCOL_HTTP;

            $response = $this->sendRequest($request, $params);
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

        $data = json_decode($response->getBody()->__toString());
        if (!$data || !property_exists($data, 'accessToken')) {
            throw new CriticalException(
                'Wrong username or password. The response does not contain an access token.'
            );
        }

        $this->customerLogger->info('Login to the REST API was successful!');
        $this->accessToken = $data->accessToken;
        $this->refreshToken = $data->refreshToken;
    }

    private function refreshLogin(): void
    {
        // TODO: Login sessions are typically 24 hours, but we want to refresh the login session anyway, if
        //  the login session time is exceeded.
    }

    private function handleRateLimit(): void
    {
        if ($this->lastResponse && $this->isRateLimited()) {
            $waitTimeInSeconds = $this->getRateLimitWaitTimeInSeconds();
            $this->customerLogger->info(sprintf(
                'Waiting for %d seconds, due to rate limiting...',
                $waitTimeInSeconds
            ));

            sleep($waitTimeInSeconds);
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

    private function buildRequestUri(string $endpoint): Uri
    {
        return new Uri(sprintf(
            '%s://%s/%s/%s',
            $this->protocol,
            $this->config->getDomain(),
            self::REST_PATH,
            $endpoint
        ));
    }
}
