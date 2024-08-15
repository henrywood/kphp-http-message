<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-28
 * Time: 14:31
 */

namespace PhpPkg\Http\Message\Request;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use FFI;

/**
 * Class RequestBody
 *   Provides a PSR-7 implementation of a reusable raw request body
 * @package PhpPkg\Http\Message\Request
 */
class KPHPRequestBody implements StreamInterface {

	private string $data;
	private int $position;

	public function __construct(string $content = null)
	{
		if (php_sapi_name() !== 'cli') { // Running SERVER
			$stdinContent = file_get_contents('php://input');
		} else {
			$stdinContent = '';
#ifndef KPHP
			stream_set_blocking(STDIN, FALSE);
#endif
			// Read from STDIN
			while (!feof(STDIN)) {
				$line = fgets(STDIN);
				if ($line === false) {
					break;
				}
				$stdinContent .= $line;
			}

			$stdinContent = rtrim($stdinContent);
		}

		$this->data = (string)$stdinContent;
		$this->position = 0;

		if ($content !== null) {
			$this->write($content);
		}
	}

	public function __toString() : string
	{
		return $this->data;
	}

	public function close()
	{
		$this->data = '';
		$this->position = 0;
	}

	public function detach()
	{
		$this->close();
		return null;  // No underlying resource to return
	}

	public function getSize() : ?int
	{
		return strlen($this->data);
	}

	public function tell() : int
	{
		return $this->position;
	}

	public function eof() : bool
	{
		return $this->position >= strlen($this->data);
	}

	public function isSeekable() : bool
	{
		return true;
	}

	public function seek($offset, $whence = SEEK_SET)
	{
		$length = strlen($this->data);

		switch ($whence) {
		case SEEK_SET:
			$this->position = $offset;
			break;
		case SEEK_CUR:
			$this->position += $offset;
			break;
		case SEEK_END:
			$this->position = $length + $offset;
			break;
		default:
			throw new RuntimeException('Invalid whence value');
		}

		if ($this->position < 0 || $this->position > $length) {
			throw new RuntimeException('Invalid seek position');
		}
	}

	public function rewind()
	{
		$this->position = 0;
	}

	public function isWritable() : bool
	{
		return true;
	}

	public function write($string) : int
	{
		$length = strlen($string);

		// Replace data at the current position with new data
		$this->data = substr_replace($this->data, $string, $this->position, $length);
		$this->position += $length;

		return $length;
	}

	public function isReadable() : bool
	{
		return true;
	}

	public function read($length) : string
	{
		$result = substr($this->data, $this->position, $length);
		$this->position += strlen($result);
		return $result;
	}

	public function getContents() : string
	{
		$result = substr($this->data, $this->position);
		$this->position = strlen($this->data);  // Move to end
		return $result;
	}

	public function getMetadata(?string $key = null): ?array
	{
		// No metadata for an in-memory stream
		return null;
	}
}


