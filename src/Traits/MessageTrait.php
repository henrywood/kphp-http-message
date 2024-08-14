<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 下午12:44
 */

namespace PhpPkg\Http\Message\Traits;

/**
 * Trait MessageTrait
 * @package PhpPkg\Http\Message\Traits
 */
trait MessageTrait
{
	/**
	 * protocol/schema
	 *
	 * @var string
	 */
	protected string $protocol = '';

	/**
	 * @var string
	 */
	protected string $protocolVersion = '1.1';

	/**
	 * @var \PhpPkg\Http\Message\Headers|null
	 */
	protected ?\PhpPkg\Http\Message\Headers $headers = null;

	/**
	 * Body object
	 *
	 * @var \Psr\Http\Message\StreamInterface
	 */
	protected \Psr\Http\Message\StreamInterface $body;

	/**
	 * A map of valid protocol versions
	 * @var array
	 */
	protected static array $validProtocolVersions = [
		'1.0' => true,
		'1.1' => true,
		'2.0' => true,
	];

	/**
	 * BaseMessage constructor.
	 *
	 * @param string									$protocol
	 * @param string									$protocolVersion
	 * @param ?\PhpPkg\Http\Message\Headers				$headers
	 * @param ?\Psr\Http\Message\StreamInterface		$body
	 *
	 * @throws \InvalidArgumentException
	 */
	public function initialize(
		string $protocol = 'http',
		string $protocolVersion = '1.1',
		?\PhpPkg\Http\Message\Headers $headers = null,
		?\Psr\Http\Message\StreamInterface $body = null
	): void {
		$this->protocol        = $protocol ?: 'http';
		$this->protocolVersion = $protocolVersion ?: '1.1';

		if (is_null($headers)) {
			$this->headers = new \PhpPkg\Http\Message\Headers([]);
		} else {
			$this->headers = $headers;
		}

		$this->body = $body;
	}

	/**
	 * BaseMessage constructor.
	 *
	 * @param string                                                                        $protocol
	 * @param string                                                                        $protocolVersion
	 * @param ?array                                                                        $headers
	 * @param string																		$body
	 *
	 * @throws \InvalidArgumentException
	 */
	public function initializeWithBodyFromStreamIdentifier(
		string $protocol = 'http',
		string $protocolVersion = '1.1',
		array|\PhpPkg\Http\Message\Headers|null $headers = null,
		string $body = 'php://memory'
	): void {
		$this->protocol        = $protocol ?: 'http';
		$this->protocolVersion = $protocolVersion ?: '1.1';

		if (is_null($headers)) {
			$this->headers = new \PhpPkg\Http\Message\Headers([]);
		} else {
			$this->headers = new \PhpPkg\Http\Message\Headers($headers);
		}

		$this->body = $this->createBodyStreamFromStreamIdentifier($body);
	}

	public function initializeWithExistingBodyResource(
		array|\PhpPkg\Http\Message\Headers|null $headers,
		mixed $body,
		string $protocol = 'http',
		string $protocolVersion = '1.1'
	): void {

		if (is_null($headers)) {
			$this->headers = new \PhpPkg\Http\Message\Headers([]);
		} else {
			$this->headers = new \PhpPkg\Http\Message\Headers($headers);
		}

		if ($this->is_resource($body)) {
			$this->body = new \PhpPkg\Http\Message\Stream($body);
		} else {
			throw new \InvalidArgumentException('Body is not a resource');
		}
	}

	/*******************************************************************************
	 * Protocol
	 ******************************************************************************/

	/**
	 * @return string
	 */
	public function getProtocol(): string
	{
		if (!$this->protocol) {
			$this->protocol = 'HTTP';
		}

		return $this->protocol;
	}

	/**
	 * @param string $protocol
	 */
	public function setProtocol(string $protocol): void
	{
		$this->protocol = $protocol;
	}

	/**
	 * @return string
	 */
	public function getProtocolVersion(): string
	{
		if (!$this->protocolVersion) {
			$this->protocolVersion = '1.1';
		}

		return $this->protocolVersion;
	}

	/**
	 * @param string $protocolVersion
	 */
	public function setProtocolVersion(string $protocolVersion): void
	{
		$this->protocolVersion = $protocolVersion;
	}

	/**
	 * @param $version
	 * @return static
	 * @throws \InvalidArgumentException
	 */
	public function withProtocolVersion($version): static
	{
		if (!isset(self::$validProtocolVersions[$version])) {
			throw new \InvalidArgumentException(
				'Invalid HTTP version. Must be one of: '
				. implode(', ', array_keys(self::$validProtocolVersions))
			);
		}

		$clone                  = clone $this;
		$clone->protocolVersion = $version;

		return $clone;
	}

	/*******************************************************************************
	 * Headers
	 ******************************************************************************/

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasHeader($name): bool
	{
		return $this->headers->has($name);
	}

	/**
	 * @param string $name
	 * @return array
	 */
	public function getHeader($name)
	{
		return (array)$this->headers->get($name, []);
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function getHeaderLine($name): string
	{
		return implode(',', $this->headers->get($name, []));
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return static
	 */
	public function setHeader(string $name, mixed $value): static
	{
		$this->headers->set($name, (string)$value);

		return $this;
	}

	/**
	 * PSR 7 method
	 * @param string $name
	 * @param        $value
	 * @return self
	 */
	public function withHeader($name, $value): self
	{
		$clone = clone $this;
		$clone->headers->set($name, $value);

		return $clone;
	}

	/**
	 * PSR 7 method
	 *
	 * @param string $name
	 * @return static
	 */
	public function withoutHeader($name): static
	{
		$clone = clone $this;
		$clone->headers->remove($name);

		return $clone;
	}

	/**
	 * PSR 7 method
	 * @param string $name
	 * @param mixed $value
	 * @return static
	 */
	public function withAddedHeader($name, $value): static
	{
		$clone = clone $this;
		$clone->headers->add($name, $value);

		return $clone;
	}

	/**
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->headers->all();
	}

	/**
	 * @return \PhpPkg\Http\Message\Headers
	 */
	public function getHeadersObject(): \PhpPkg\Http\Message\Headers
	{
		return $this->headers;
	}

	/**
	 * @param array $headers
	 * @return static
	 */
	public function setHeaders(array $headers): static
	{
		$this->headers->sets($headers);

		return $this;
	}

	/*******************************************************************************
	 * Body
	 ******************************************************************************/

	private function is_resource(mixed $a) : bool {
		if (!is_null($a) && ! is_object($a) && ! is_numeric($a) && ! is_string($a) && ! is_bool($a) && ! is_array($a)) return TRUE;
		return FALSE;
	} 


	/**
	 * @param string $body
	 * @param string $mode
	 *
	 * @return \Psr\Http\Message\StreamInterface                                                                          
	 * @throws \InvalidArgumentException
	 */
	protected function createBodyStreamFromStreamIdentifier($body, string $mode = 'rb'): \Psr\Http\Message\StreamInterface
	{
		$resource = fopen($body, $mode);

		if ($resource === false) {
			throw new \InvalidArgumentException('Unable to open the file.');
		}

		return new \PhpPkg\Http\Message\Stream($resource);
	}

	/**
	 * @return \Psr\Http\Message\StreamInterface
	 */
	public function getBody(): \Psr\Http\Message\StreamInterface
	{
		return $this->body;
	}

	/**
	 * @param \Psr\Http\Message\StreamInterface $body
	 * @return static
	 */
	public function setBody(\Psr\Http\Message\StreamInterface $body): static
	{
		$this->body = $body;

		return $this;
	}

	/**
	 * @param \Psr\Http\Message\StreamInterface $body
	 * @return static
	 */
	public function withBody(\Psr\Http\Message\StreamInterface $body): static
	{
		// TODO: Test for invalid body?
		$clone       = clone $this;
		$clone->body = $body;

		return $clone;
	}

	/**
	 * @param string $content
	 * @return static
	 */
	public function addContent(string $content): static
	{
		$this->body->write($content);
		return $this;
	}

	/**
	 * @param string $content
	 * @return static
	 */
	public function write(string $content): static
	{
		$this->body->write($content);
		return $this;
	}
}
