<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-20
 * Time: 13:20
 */

namespace PhpPkg\Http\Message;

use InvalidArgumentException;
use PhpPkg\Http\Message\Collection as MessageCollection;
use PhpPkg\Http\Message\Util\Collection;
use PhpPkg\Http\Message\Request\RequestBody;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

/**
 * Class HttpFactory
 * @package PhpPkg\Http\Message
 * @link  https://github.com/php-fig/fig-standards/blob/master/proposed/http-factory/http-factory.md
 */
class HttpFactory
{
	const CASE_LOWER = 0;
	const CASE_UPPER = 1;

	/**
	 * Special HTTP headers that do not have the "HTTP_" prefix
	 * @var array
	 */
	protected static array $special = [
		'CONTENT_TYPE'    => 1,
		'CONTENT_LENGTH'  => 1,
		'PHP_AUTH_USER'   => 1,
		'PHP_AUTH_PW'     => 1,
		'PHP_AUTH_DIGEST' => 1,
		'AUTH_TYPE'       => 1,
	];

	/**
	 * RequestFactoryInterface
	 */

	/**
	 * Create a new request.
	 *
	 * @param string              $method
	 * @param string|UriInterface $uri
	 *
	 * @return RequestInterface
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 */
	public static function createRequest(string $method, UriInterface|string $uri): RequestInterface
	{
		if (is_string($uri)) {
			$uri = Uri::createFromString($uri);
		}

		return new Request($method, $uri);
	}

	/**
	 * ResponseFactoryInterface
	 */

	/**
	 * Create a new response.
	 * @param integer $code HTTP status code
	 * @return ResponseInterface
	 * @throws InvalidArgumentException
	 */
	public static function createResponse(int $code = 200): ResponseInterface
	{
		return new Response($code);
	}

	/*****************************************************
	 * ServerRequestFactoryInterface
	 ****************************************************/

	/**
	 * Create a new server request.
	 *
	 * @param string $method
	 * @param string|UriInterface $uri
	 *
	 * @return ServerRequestInterface
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 */
	public static function createServerRequest(string $method, UriInterface|string $uri): ServerRequestInterface
	{
		if (is_string($uri)) {
			$uri = Uri::createFromString($uri);
		}

		return new ServerRequest($method, $uri);
	}

	/**
	 * Create a new server request from server variables
	 * @param mixed $server Typically $_SERVER or similar structure.
	 * @param ?string $class
	 * @return ServerRequestInterface
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 *  If no valid method or URI can be determined.
	 */
	public static function createServerRequestFromArray(mixed $server, string $class = null): ServerRequestInterface
	{
		$env = new Collection($server);
		$uri = static::createUriFromArray($server);

		$body          = new RequestBody();
		$method        = $env->get('REQUEST_METHOD', 'GET');
		/* @var Headers $headers*/
		/* @var string[][] $server */
		$headers       = static::createHeadersFromArray($server);
		$cookies       = Cookies::parseFromRawHeader($headers->get('Cookie', []));
		$serverParams  = $env->all();
		$uploadedFiles = UploadedFile::createFromFILES();

		//$class = $class ?: ServerRequest::class;

		/** @var ServerRequest $request */
		$request = new ServerRequest((string)$method, $uri, $headers, $cookies, $serverParams, $body, $uploadedFiles);

		if (
			$method === 'POST' &&
			in_array($request->getMediaType(), ['application/x-www-form-urlencoded', 'multipart/form-data'], true)
		) {
			// parsed body must be $_POST
			$request = $request->withParsedBody($_POST);
		}

		return $request;
	}

	/**
	 * @param string $rawData
	 * @return ServerRequestInterface
	 */
	public static function createServerRequestFromRaw(string $rawData): ServerRequestInterface
	{
		if (!$rawData) {
			return new ServerRequest('GET', Uri::createFromString('/'));
		}

		// $rawData = trim($rawData);
		// split head and body
		$two = explode("\r\n\r\n", $rawData, 2);

		if (!$rawHeader = $two[0] ?? '') {
			return new ServerRequest('GET', Uri::createFromString('/'));
		}

		$body = $two[1] ? new RequestBody($two[1]) : null;

		/** @var array $list */
		$list = explode("\n", trim($rawHeader));

		// e.g: `GET / HTTP/1.1`
		$first = \array_shift($list);
		// parse
		[$method, $uri, $protoStr] = \array_map('trim', explode(' ', \trim($first)));
		[$protocol, $protocolVersion] = explode('/', $protoStr);

		// other header info
		$headers = [];
		foreach ($list as $item) {
			if ($item) {
				[$name, $value] = explode(': ', \trim($item));
				$headers[$name] = \trim($value);
			}
		}

		$cookies = [];
		if (isset($headers['Cookie'])) {
			$cookies    = Cookies::parseFromRawHeader($headers['Cookie']);
		}

		$port = 80;
		$host = '';
		if ($val = $headers['Host'] ?? '') {
			[$host, $port] = strpos($val, ':') ? explode(':', $val) : [$val, 80];
		}

		$path  = $uri;
		$query = $fragment = '';
		if (strlen($uri) > 1) {
			$parts    = parse_url($uri);
			$path     = $parts['path'] ?? '';
			$query    = $parts['query'] ?? '';
			$fragment = $parts['fragment'] ?? '';
		}

		$uri = new Uri($protocol, $host, (int)$port, $path, $query, $fragment);
		return new ServerRequest($method, $uri, $headers, $cookies, [], $body, [], $protocol, $protocolVersion);
	}

	/*****************************************************
	 * StreamFactoryInterface
	 ****************************************************/

	/**
	 * Create a new stream from a string.
	 * The stream SHOULD be created with a temporary resource.
	 * @param string $content
	 * @return StreamInterface
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 */
	public static function createStream(string $content = ''): StreamInterface
	{
		return new RequestBody($content);
	}

	/**
	 * Create a stream from an existing file.
	 * The file MUST be opened using the given mode, which may be any mode
	 * supported by the `fopen` function.
	 * The `$filename` MAY be any string supported by `fopen()`.
	 * @param string $filename
	 * @param string $mode
	 * @return StreamInterface
	 * @throws InvalidArgumentException
	 */
	public static function createStreamFromFile(string $filename, string $mode = 'rb'): StreamInterface
	{
		// $stream = fopen('php://temp', $mode);
		$stream = fopen($filename, $mode);

		return new Stream($stream);
	}

	/**
	 * Create a new stream from an existing resource.
	 * The stream MUST be readable and may be writable.
	 * @param resource $resource e.g `$resource = fopen('php://temp', 'r+');`
	 * @return StreamInterface
	 * @throws InvalidArgumentException
	 */
	public static function createStreamFromResource($resource): StreamInterface
	{
		return new Stream($resource);
	}

	/**
	 * UploadedFileFactoryInterface
	 */

	/**
	 * Create a new uploaded file.
	 * If a string is used to create the file, a temporary resource will be
	 * created with the content of the string.
	 * If a size is not provided it will be determined by checking the size of
	 * the file.
	 *
	 * @see http://php.net/manual/features.file-upload.post-method.php
	 * @see http://php.net/manual/features.file-upload.errors.php
	 *
	 * @param string $file
	 * @param integer         $size in bytes
	 * @param integer         $error PHP file upload error
	 * @param string          $clientFilename
	 * @param string          $clientMediaType
	 *
	 * @return UploadedFileInterface
	 * @throws InvalidArgumentException If the file resource is not readable.
	 */
	public static function createUploadedFile(
		string $file,
		int $size = null,
		int $error = \UPLOAD_ERR_OK,
		string $clientFilename = null,
		string $clientMediaType = null
	): UploadedFileInterface {
		return new UploadedFile($file, $clientFilename, $clientMediaType, $size, $error);
	}

	/*****************************************************
	 * UriFactoryInterface
	 ****************************************************/

	/**
	 * Create a new URI.
	 * @param string $uri
	 * @return UriInterface
	 * @throws InvalidArgumentException If the given URI cannot be parsed.
	 */
	public static function createUri(string $uri = ''): UriInterface
	{
		return Uri::createFromString($uri);
	}

	/**
	 * @param mixed $array
	 *
	 * @return Uri
	 * @throws InvalidArgumentException
	 */
	public static function createUriFromArray(mixed $array): Uri
	{

		$envColl = new Collection($array);

		// Scheme
		$isSecure = $envColl->get('HTTPS');
		$scheme   = (empty($isSecure) || $isSecure === 'off') ? 'http' : 'https';

		// Authority: Username and password
		$username = $envColl->get('PHP_AUTH_USER', '');
		$password = $envColl->get('PHP_AUTH_PW', '');

		// Authority: Host
		if ($envColl->has('HTTP_HOST')) {
			$host = $envColl->get('HTTP_HOST');
		} else {
			$host = $envColl->get('SERVER_NAME');
		}

		// Authority: Port
		$port = (int)$envColl->get('SERVER_PORT', '80');
		if (preg_match('/^(\[[a-fA-F0-9:.]+\])(:\d+)?\z/', $host, $matches)) {
			$host = $matches[1];

			if ($matches[2]) {
				$port = (int)substr($matches[2], 1);
			}
		} else {
			$pos = strpos($host, ':');
			if ($pos !== false) {
				$port = (int)substr($host, $pos + 1);
				$host = strstr($host, ':', true);
			}
		}

		// Path
		// $requestScriptName = parse_url($env->get('SCRIPT_NAME'), PHP_URL_PATH);
		// $requestScriptDir = \dirname($requestScriptName);

		// parse_url() requires a full URL. As we don't extract the domain name or scheme,
		// we use a stand-in.
		$uriPath = parse_url('http://abc.com' . $envColl->get('REQUEST_URI'), PHP_URL_PATH);

		// Query string
		$queryString = $envColl->get('QUERY_STRING', '');
		if ($queryString === '') {
			$queryString = parse_url('http://abc.com' . $envColl->get('REQUEST_URI'), PHP_URL_QUERY);
		}

		// Fragment
		$fragment = '';

		// Build Uri
		$uri = new Uri((string)$scheme, (string)$host, (int)$port, (string)$uriPath, (string)$queryString ?? '', (string)$fragment ?? '', (string)$username ?? '', (string)$password ?? '');

		return $uri;
	}

	/*******************************************************************************
	 * extended factory methods
	 ******************************************************************************/

	/**
	 * @param mixed $array
	 *
	 * @return Headers
	 */
	public static function createHeadersFromArray(mixed $array = []): Headers
	{
		$data = [];
		$envColl  = new Collection($array);
		$envColl  = self::determineAuthorization($envColl);

		foreach ($envColl->toArray() as $key => $value) {
			$key = strtoupper($key);

			if (isset(static::$special[$key]) || str_starts_with($key, 'HTTP_')) {
				if ($key !== 'HTTP_CONTENT_LENGTH') {
					$data[$key] = $value;
				}
			}
		}

		return new Headers($data);
	}

	private static function array_change_key_case(array $arr, int $c = self::CASE_LOWER) {

		$ret = $arr;

		foreach ($arr as $k => $v) {

			if ($c === self::CASE_LOWER) {
				$ret[mb_strtolower($k)] = $v;
			}

			if ($c === self::CASE_UPPER) {
				$ret[mb_strtoupper($k)] = $v;
			}
		}

		return $ret;
	}

	/**
	 * If HTTP_AUTHORIZATION does not exist tries to get it from
	 * getallheaders() when available.
	 *
	 * @param Collection $env The application Collection
	 *
	 * @return Collection
	 */
	public static function determineAuthorization(Collection $env): Collection
	{
		$authorization = $env->get('HTTP_AUTHORIZATION');

		if (! defined('CASE_LOWER')) define('CASE_LOWER', 0);

		if (null === $authorization) {
			$headers = self::getallheaders($env);
			$headers = self::array_change_key_case($headers, CASE_LOWER);

			if (isset($headers['authorization'])) {
				$env->set('HTTP_AUTHORIZATION', $headers['authorization']);
			}
		}

		return $env;
	}

	private static function getallheaders(Collection $source) : array {

		$headerArray = $source->toArray();

		$headers = [];

		foreach ($headerArray as $name => $value) {
			if (substr($name, 0, 5) == 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		return $headers;
	}
}
