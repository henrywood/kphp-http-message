<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-28
 * Time: 14:31
 */

namespace PhpPkg\Http\Message\Request;

use PhpPkg\Http\Message\Stream;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Class RequestBody
 *   Provides a PSR-7 implementation of a reusable raw request body
 * @package PhpPkg\Http\Message\Request
 */
class RequestBody extends Stream  implements StreamInterface
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

		stream_copy_to_stream($inputStream, $stream);

		fclose($inputStream);
		rewind($stream);

		parent::__construct($stream);

		if ($content !== null) {
			$this->write($content);
		}
	}
}


