<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 下午12:29
 */

namespace PhpPkg\Http\Message\Stream;

use PhpPkg\Http\Message\Stream;

/**
 * Class TempStream
 * @package PhpPkg\Http\Message\Stream
 */
class OutputStream extends Stream
{
	/**
	 * TempStream constructor.
	 * @param string $mode
	 * @throws \InvalidArgumentException
	 */
	public function __construct(string $mode = 'wb+')
	{
		$stream = \fopen('php://memory', $mode);

		parent::__construct($stream);
	}

	public function isWritable(): bool
	{
		$this->writable = TRUE;
		return $this->writable;
	}

	public function __toString() : string {

		// Rewind the memory stream to the beginning
		\rewind($this->stream);

		// Initialize an empty string to store the content
		$content = '';

		// Read the stream in chunks until the end of the stream
		$output = fopen('php://output', 'wb');

		while (!\feof($this->stream)) {
			// Read a chunk of data (e.g., 8192 bytes)
			$chunk = \fread($this->stream, 8192);

			// Write the chunk to stdout
			\fwrite($output, $chunk);
		}

		return '';
	}
}
