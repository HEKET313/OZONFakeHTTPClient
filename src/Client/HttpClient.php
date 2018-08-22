<?php

namespace App\Client;

use PhpExtended\HttpMessage\Request;
use PhpExtended\HttpMessage\Response;
use PhpExtended\HttpMessage\StringStream;
use PhpExtended\HttpMessage\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * Class HttpClient
 * @package App\Client
 *
 * @method ResponseInterface get(string | UriInterface $uri, string | StreamInterface $body = '', array $headers = [])
 * @method ResponseInterface post(string | UriInterface $uri, string | StreamInterface $body = '', array $headers = [])
 * @method ResponseInterface delete(string | UriInterface $uri, string | StreamInterface $body = '', array $headers = [])
 * @method ResponseInterface update(string | UriInterface $uri, string | StreamInterface $body = '', array $headers = [])
 */
class HttpClient
{
    private const ACCEPTED_METHODS = ['GET', 'POST', 'DELETE', 'UPDATE'];

    private $host;
    private $port;
    private $timeout;
    private $threshold;
    private $logger;

    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger = $logger;
        $this->host = $config['host'] ?? '';
        $this->port = $config['port'] ?? '';
        $this->timeout = $config['timeout'] ?? 30;
        $this->threshold = $config['threshold'] ?? 5;
    }

    /**
     * @param $name
     * @param $arguments
     * @return ResponseInterface
     * @throws \Exception
     */
    public function __call($name, $arguments): ResponseInterface
    {
        $name = strtoupper($name);
        if (!in_array($name, self::ACCEPTED_METHODS)) {
            throw new \Exception('Method is not supported');
        }
        $uri = $arguments[0];
        $body = $arguments[1] ?? '';
        $headers = $arguments[2] ?? [];

        if (!$uri instanceof UriInterface) {
            $uri = Uri::parseFromString($uri);
        }

        $request = (new Request())->withMethod($name)->withUri($uri);

        if ($body && !$body instanceof StreamInterface) {
            $body = new StringStream($body);
            $request = $request->withBody($body);
        }

        foreach ($headers as $name => $values) {
            $request = $request->withHeader($name, $values);
        }
        return $this->execute($request);
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function execute(RequestInterface $request): ResponseInterface
    {
        $uri = $request->getUri();
        if ($this->host) {
            $uri = $uri->withHost($this->host);
        }
        if ($this->port) {
            $uri = $uri->withPort($this->port);
        }
        $curl = curl_init((string)$uri);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_POSTFIELDS => $request->getBody()->getContents(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 0,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => array_map(function (string $name) use ($request) {
                return $request->getHeaderLine($name);
            }, array_keys($request->getHeaders()))
        ]);
        $startTime = time();
        $result = curl_exec($curl);
        curl_close($curl);
        if (time() - $startTime > $this->threshold) {
            $this->logger->warning('Slow HTTP request', ['request' => $request]);
        }
        if (curl_errno($curl)) {
            $this->logger->error('Error while HTTP request', ['request' => $request, 'error' => curl_error($curl)]);
            throw new \Exception(curl_error($curl));
        }
        return (new Response())
            ->withBody(new StringStream($result))
            ->withStatus(curl_getinfo($curl, CURLINFO_HTTP_CODE));
    }
}
