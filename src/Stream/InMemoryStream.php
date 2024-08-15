<?php
namespace PhpPkg\Http\Message\Stream;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

class InMemoryStream implements StreamInterface {

	private string $buffer = '';
	private int $position = 0;

	// Write data to the "stream"
	public function write($data) : int {
		$this->buffer .= $data;
		$this->position += strlen($data);
		return strlen($data);
	}

	// Constructor   
	public function __construct(string $data = '') {

		$this->buffer = $data;
		$this->position = 0;
	}

	public function close() {

		$this->buffer = '';
		$this->position = 0;
	}

	public function detach() {

		$this->close();
		return null;  // No underlying resource to return
	}

	public function isSeekable() : bool {
		return TRUE;
	}

	public function isWritable() : bool {
		return TRUE;
	}

	public function seek($offset, $whence = SEEK_SET) {

		$length = strlen($this->buffer);

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

		// Ensure the position is within the bounds of the data
		if ($this->position < 0 || $this->position > $length) {
			throw new RuntimeException('Invalid seek position');
		}
	}

	// Read data from the "stream"
	public function read($length) {
		$data = (string)substr($this->buffer, $this->position, $length);
		$this->position += strlen($data);
		return $data;
	}

	// Rewind to the beginning of the "stream"
	public function rewind() {
		$this->position = 0;
	}

	// Get the current position in the stream
	public function tell() {
		return $this->position;
	}

	// Check if we are at the end of the stream
	public function eof() {
		return $this->position >= strlen($this->buffer);
	}

	public function __toString()
	{
		return $this->buffer;
	}

	public function isReadable() {
		return TRUE;
	}

	// Get size
	public function getSize() : int {
		return strlen($this->buffer);
	}

	// Metadata
	public function getMetadata(string $key = null) : ?array {
		return null;
	}

	// Get contents
	public function getContents() : string {

		$result = (string)substr($this->buffer, $this->position);
		$this->position = strlen($this->buffer);  // Move to end
		return $result;
	}
}

