<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-31
 * Time: 13:42
 * @from Slim-Http
 */

namespace PhpPkg\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Represents a data stream as defined in PSR-7.
 *
 * @link https://github.com/php-fig/http-message/blob/master/src/StreamInterface.php
 */
class Stream implements StreamInterface
{
	/**
	 * Bit mask to determine if the stream is a pipe
	 *
	 * This is octal as per header stat.h
	 */
	public const FSTAT_MODE_S_IFIFO = 0010000;

	/**
	 * Resource modes
	 *
	 * @var  array[]
	 * @link http://php.net/manual/function.fopen.php
	 */
	protected static array $modes = [
		'readable' => ['r', 'r+', 'w+', 'a+', 'x+', 'c+'],
		'writable' => ['r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'],
	];

	/**
	 * The underlying stream resource
	 *
	 * @var mixed
	 */
	protected $stream;

	/**
	 * Stream metadata
	 *
	 * @var array
	 */
	protected array $meta;

	/**
	 * Is this stream readable?
	 *
	 * @var bool
	 */
	protected ?bool $readable = FALSE;

	/**
	 * Is this stream writable?
	 *
	 * @var bool
	 */
	protected bool $writable = FALSE;

	/**
	 * Is this stream seekable?
	 *
	 * @var bool
	 */
	protected bool $seekable = FALSE;

	/**
	 * The size of the stream if known
	 *
	 * @var null|int
	 */
	protected ?int $size;

	/**
	 * Is this stream a pipe?
	 *
	 * @var bool
	 */
	protected bool $isPipe = FALSE;


	/**
	 * Create a new Stream.
	 *
	 * @param  mixed $stream A PHP resource handle.
	 *
	 * @throws InvalidArgumentException If argument is not a resource.
	 */
	public function __construct($stream)
	{
		$this->attach($stream);
	}

	/**
	 * Get stream metadata as an associative array or retrieve a specific key.
	 *
	 * The keys returned are identical to the keys returned from PHP's
	 * stream_get_meta_data() function.
	 *
	 * @link http://php.net/manual/en/function.stream-get-meta-data.php
	 *
	 * @param string|null $key Specific metadata to retrieve.
	 */
	public function getMetadata(?string $key = null) : ?array
	{
		//$this->meta = \stream_get_meta_data($this->stream);
		//if (null === $key) {
		//	return $this->meta;
		//}

		//return $this->meta[$key] ?? null;

		return NULL;
	}

	/**
	 * Is a resource attached to this stream?
	 *
	 * Note: This method is not part of the PSR-7 standard.
	 *
	 * @return bool
	 */
	protected function isAttached(): bool
	{
		return $this->is_resource($this->stream);
	}

	protected function is_resource(mixed $a) : bool {

		if (!is_null($a) && ! is_object($a) && ! is_numeric($a) && ! is_string($a) && ! is_bool($a) && ! is_array($a)) return TRUE;
		return FALSE;
	}


	/**
	 * Attach new resource to this object.
	 *
	 * Note: This method is not part of the PSR-7 standard.
	 *
	 * @param mixed $newStream A PHP resource handle.
	 *
	 * @throws InvalidArgumentException If argument is not a valid PHP resource.
	 */
	protected function attach($newStream): void
	{
		if ($this->is_resource($newStream) === false) {
			throw new InvalidArgumentException(__METHOD__ . ' argument must be a valid PHP resource');
		}

		if ($this->isAttached() === true) {
			$this->detach();
		}

		$this->stream = $newStream;
	}

	/**
	 * Separates any underlying resources from the stream.
	 *
	 * After the stream has been detached, the stream is in an unusable state.
	 *
	 * @return mixed|null Underlying PHP stream, if any
	 */
	public function detach()
	{
		$oldResource    = $this->stream;
		$this->stream   = null;
		$this->meta     = [];
		$this->readable = FALSE;
		$this->writable = FALSE;
		$this->seekable = FALSE;
		$this->size     = null;
		$this->isPipe   = FALSE;

		return $oldResource;
	}

	/**
	 * Reads all data from the stream into a string, from the beginning to end.
	 *
	 * This method MUST attempt to seek to the beginning of the stream before
	 * reading data and read the stream until the end is reached.
	 *
	 * Warning: This could attempt to load a large amount of data into memory.
	 *
	 * This method MUST NOT raise an exception in order to conform with PHP's
	 * string casting operations.
	 *
	 * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
	 * @return string
	 */
	public function __toString()
	{
		if (!$this->isAttached()) {
			return '';
		}

		try {
			$this->rewind();
			return $this->getContents();
		} catch (RuntimeException $e) {
			return '';
		}
	}

	/**
	 * Closes the stream and any underlying resources.
	 */
	public function close(): void
	{
		if ($this->isAttached() === true) {
			if ($this->isPipe()) {
				//pclose($this->stream);
				unset($this->stream);
			} else {
				fclose($this->stream);
			}
		}

		$this->detach();
	}

	/**
	 * Get the size of the stream if known.
	 *
	 * @return int|null Returns the size in bytes if known, or null if unknown.
	 */
	public function getSize(): ?int
	{

		return NULL;
	}

	/**
	 * Returns the current position of the file read/write pointer
	 *
	 * @return int Position of the file pointer
	 *
	 * @throws RuntimeException on error.
	 */
	public function tell(): int
	{
		if (!$this->isAttached() || ($position = ftell($this->stream)) === false || $this->isPipe()) {
			throw new RuntimeException('Could not get the position of the pointer in stream');
		}

		return $position;
	}

	/**
	 * Returns true if the stream is at the end of the stream.
	 *
	 * @return bool
	 */
	public function eof(): bool
	{
		return $this->isAttached() ? \feof($this->stream) : true;
	}

	/**
	 * Returns whether or not the stream is readable.
	 *
	 * @return bool
	 */
	public function isReadable(): bool
	{
		if ($this->readable === null) {
			if ($this->isPipe()) {
				$this->readable = true;
			} else {
				$this->readable = false;

				if ($this->isAttached()) {

					$pos = ftell($this->stream);
					if ($pos === false) {
						$this->readable = FALSE;
					}
					$result = @fread($this->stream, 1);
					fseek($this->stream, $pos); // Reset to original position
					$this->readable = ($result !== false && strlen($result) > 0);
				}
			}
		}

		return $this->readable;
	}

	/**
	 * Returns whether or not the stream is writable.
	 *
	 * @return bool
	 */
	public function isWritable(): bool
	{
		if ($this->writable === null) {
			$this->writable = false;

			if ($this->isAttached()) {

				$pos = ftell($this->stream);
				if ($pos === false) {
					$this->writable = false;
				} else {

					$result = @fwrite($this->stream, '');
					fseek($this->stream, $pos); // Reset to original position
					$this->writable = $result !== false;
				}
			}
		}

		return $this->writable;
	}

	/**
	 * Returns whether or not the stream is seekable.
	 *
	 * @return bool
	 */
	public function isSeekable(): bool
	{
		if ($this->seekable === null) {
			$this->seekable = false;

			if ($this->isAttached()) {
				$result = @fseek($this->stream, 0, SEEK_CUR);
				$this->seekable = ($result === 0);
			}
		}

		return $this->seekable;
	}

	/**
	 * Seek to a position in the stream.
	 *
	 * @link http://www.php.net/manual/en/function.fseek.php
	 *
	 * @param int $offset Stream offset
	 * @param int $whence Specifies how the cursor position will be calculated
	 *     based on the seek offset. Valid values are identical to the built-in
	 *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
	 *     offset bytes SEEK_CUR: Set position to current location plus offset
	 *     SEEK_END: Set position to end-of-stream plus offset.
	 *
	 * @throws RuntimeException on failure.
	 */
	public function seek($offset, $whence = \SEEK_SET)
	{
		// Note that fseek returns 0 on success!
		if (!$this->isSeekable() || fseek($this->stream, $offset, $whence) === -1) {
			throw new RuntimeException('Could not seek in stream');
		}
	}

	/**
	 * Seek to the beginning of the stream.
	 *
	 * If the stream is not seekable, this method will raise an exception;
	 * otherwise, it will perform a seek(0).
	 *
	 * @see seek()
	 *
	 * @link http://www.php.net/manual/en/function.fseek.php
	 *
	 * @throws RuntimeException on failure.
	 */
	public function rewind()
	{
		if (!$this->isSeekable() || rewind($this->stream) === false) {
			throw new RuntimeException('Could not rewind stream');
		}
	}

	/**
	 * Read data from the stream.
	 *
	 * @param int $length Read up to $length bytes from the object and return
	 *     them. Fewer than $length bytes may be returned if underlying stream
	 *     call returns fewer bytes.
	 *
	 * @return string Returns the data read from the stream, or an empty string
	 *     if no bytes are available.
	 *
	 * @throws RuntimeException if an error occurs.
	 */
	public function read($length): string
	{
		if (!$this->isReadable() || ($data = fread($this->stream, $length)) === false) {
			throw new RuntimeException('Could not read from stream');
		}

		return $data;
	}

	/**
	 * Write data to the stream.
	 *
	 * @param string $string The string that is to be written.
	 *
	 * @return int Returns the number of bytes written to the stream.
	 *
	 * @throws RuntimeException on failure.
	 */
	public function write($string): int
	{
		if (!$this->isWritable() || ($written = fwrite($this->stream, $string)) === false) {
			throw new RuntimeException('Could not write to stream');
		}

		// reset size so that it will be recalculated on next call to getSize()
		$this->size = null;

		return (int)$written;
	}

	/**
	 * Returns the remaining contents in a string
	 *
	 * @return string
	 *
	 * @throws RuntimeException if unable to read or an error occurs while
	 *     reading.
	 */
	public function getContents(): string
	{
		if (!$this->isReadable()) {
			throw new RuntimeException('Stream is not readable');
		}

		$contents = '';

		while (!feof($this->stream)) {
			$chunk = fread($this->stream, 8192);
			if ($chunk === false) {
				throw new RuntimeException('Could not read from stream');
			}
			$contents .= $chunk;
		}

		return $contents;
	}

	/**
	 * Returns whether or not the stream is a pipe.
	 *
	 * @return bool
	 */
	public function isPipe(): bool
	{
		if ($this->isPipe === null) {
			$this->isPipe = false;

			// FIXME
			/*
			if ($this->isAttached()) {
				$mode         = fstat($this->stream)['mode'];
				$this->isPipe = ($mode & self::FSTAT_MODE_S_IFIFO) !== 0;
			}
			 */
		}

		return $this->isPipe;
	}
}
