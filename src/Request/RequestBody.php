<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-28
 * Time: 14:31
 */

namespace PhpPkg\Http\Message\Request;

use PhpPkg\Http\Message\Stream;
use RuntimeException;

/**
 * Class RequestBody
 *   Provides a PSR-7 implementation of a reusable raw request body
 * @package PhpPkg\Http\Message\Request
 */
class RequestBody extends Stream
{
	/**
	 * Create a new RequestBody.
	 *
	 * @param string|null $content
	 */
	public function __construct(string $content = null)
	{
		$stream = fopen('php://temp', 'wb+');
		if ($stream === false) {
			throw new RuntimeException('Unable to open a stream');
		}

		// Copy data from php://input to the new stream
		$inputStream = fopen('php://input', 'rb');
		if ($inputStream === false) {
			fclose($stream);
			throw new RuntimeException('Unable to open php://input stream');
		}

		$this->copyStream($inputStream, $stream);

		fclose($inputStream);
		rewind($stream);

		parent::__construct($stream);

		if ($content !== null) {
			$this->write($content);
		}
	}

	/**
	 * Copy data from one stream to another.
	 *
	 */
	private function copyStream($source, $destination, int $chunkSize = 8192): void
	{
		while (!feof($source)) {
			$chunk = fread($source, $chunkSize);
			if ($chunk === false) {
				throw new RuntimeException('Error reading from source stream');
			}
			$written = fwrite($destination, $chunk);
			if ($written === false) {
				throw new RuntimeException('Error writing to destination stream');
			}
		}
	}
}


