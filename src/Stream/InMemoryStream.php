<?php
namespace PhpPkg\Http\Message\Stream;

class InMemoryStream {
	private $buffer = '';
	private $position = 0;

	// Write data to the "stream"
	public function write($data) : int {
		$this->buffer .= $data;
		$this->position += strlen($data);
		return strlen($data);
	}

	// Read data from the "stream"
	public function read($length) {
		$data = substr($this->buffer, $this->position, $length);
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

	// Get size
	public function getSize() : int {
		return strlen($this->buffer);
	}

	// Get contents
	public function getContents() : string {
		return $this->buffer;
	}

	// Metadata
	public function getMetadata(string $key = null) : ?array {
		return [];
	}
}

