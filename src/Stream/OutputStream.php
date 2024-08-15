<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 下午12:29
 */

namespace PhpPkg\Http\Message\Stream;

#ifndef KPHP
const _KPHP_VERSION = 0;
if (false)
	#endif
	const _KPHP_VERSION = 1;

use PhpPkg\Http\Message\Stream;
use PhpPkg\Http\Message\Stream\InMemoryStream;

/**
 * Class TempStream
 * @package PhpPkg\Http\Message\Stream
 */
class OutputStream extends Stream
{
	private InMemoryStream $memory;

	/**
	 * OutputStream constructor.
	 * @param string $mode
	 * @throws \InvalidArgumentException
	 */
	public function __construct(string $mode = 'wb+')
	{
		if (_KPHP_VERSION) {
			$this->memory = new InMemoryStream();
		} else {
			$stream = \fopen('php://memory', $mode);
			parent::__construct($stream);
		}
	}

	public function isWritable(): bool
	{
		$this->writable = TRUE;
		return $this->writable;
	}

	public function __toString() : string {

		if (_KPHP_VERSION) {

			$this->memory->rewind();

			$output = fopen('php://stdout', 'w');

			while (! $this->memory->eof()) {

				$chunk = $this->memory->read(8192);
				fwrite($output, $chunk);							   	
			}

		} else {

			\rewind($this->stream);

			$output = fopen('php://stdout', 'w');

			while (!\feof($this->stream)) {

				$chunk = \fread($this->stream, 8192);
				\fwrite($output, $chunk);
			}
		}

		return '';
	}

	/*
	 * @return void
	 */
	public function close() : void {
		if (! _KPHP_VERSION) parent::close();
	}

	/*
	 * @return mixed|null
	 */
	public function detach() {
		return (_KPHP_VERSION) ? null : parent::detach();
	}

	/* 
	 * @return int|null
	 */
	public function getSize() : ?int {
		return (_KPHP_VERSION) ? $this->memory->getSize() : parent::getSize();
	}

	/**
	 * @return int Position of the file pointer
	 */
	public function tell() : int {
		return (_KPHP_VERSION) ? $this->memory->tell() : parent::tell();
	}

	/**
	 * @return bool
	 */
	public function eof() : bool {
		return (_KPHP_VERSION) ? $this->memory->eof() : parent::eof();
	}

	/**
	 * @return bool
	 */
	public function isSeekable() : bool {
		return TRUE;
	}

	/**
	 * @param string $string
	 * @return int
	 */
	public function write($string) : int {
		if (_KPHP_VERSION) {
			return $this->memory->write($string);
		} else {
			return parent::write($string);
		}
	}

	/**
	 * @param int $length 
	 * @return string
	 */
	public function read($length) : string {
		return (_KPHP_VERSION) ? $this->memory->read($length) : parent::read($length);
	}

	/**
	 * @return string 
	 */
	public function getContents() : string {
		return (_KPHP_VERSION) ? $this->memory->getContents() : parent::getContents();
	}

	/**
	 * @param string|null $key 
	 */
	public function getMetadata(?string $key = null) : ?array {
		return (_KPHP_VERSION) ? $this->memory->getMetadata($key) : parent::getMetadata($key);
	}
}

