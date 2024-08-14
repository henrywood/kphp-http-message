<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-28
 * Time: 17:58
 */

namespace PhpPkg\Http\Message;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Error;

/**
 * Represents Uploaded Files.
 * It manages and normalizes uploaded files according to the PSR-7 standard.
 * @link https://github.com/php-fig/http-message/blob/master/src/UploadedFileInterface.php
 * @link https://github.com/php-fig/http-message/blob/master/src/StreamInterface.php
 */
class UploadedFile implements UploadedFileInterface
{
	/**
	 * The client-provided full path to the file
	 * @note this is public to maintain BC with 3.1.0 and earlier.
	 * @var string
	 */
	public string $file;

	/**
	 * The client-provided file name.
	 * @var string
	 */
	protected string $name;

	/**
	 * The client-provided media type of the file.
	 * @var ?string
	 */
	protected ?string $type;

	/**
	 * The size of the file in bytes.
	 * @var ?int
	 */
	protected ?int $size;

	/**
	 * A valid PHP UPLOAD_ERR_xxx code for the file upload.
	 * @var int
	 */
	protected int $error = \UPLOAD_ERR_OK;

	/**
	 * Indicates if the upload is from a SAPI environment.
	 * @var bool
	 */
	protected bool $sapi = false;

	/**
	 * An optional StreamInterface wrapping the file resource.
	 * @var StreamInterface
	 */
	protected StreamInterface $stream;

	/**
	 * Indicates if the uploaded file has already been moved.
	 * @var bool
	 */
	protected bool $moved = false;

	/**
	 * Create a normalized tree of UploadedFile instances from the Environment.
	 * @return array<string, UploadedFile> A normalized tree of UploadedFile instances or null if none are provided.
	 */
	public static function createFromFILES(): array
	{
		if (count($_FILES)) {
			return static::parseUploadedFiles($_FILES);
		}

		return [];
	}

	/**
	 * Parses an array of uploaded files into an associative array of UploadedFile instances.
	 *
	 * @param mixed $uploadedFiles The uploaded files data.
	 * @return  array<string,UploadedFile> associative array where keys are field names, and values are UploadedFile instances or arrays of UploadedFile instances.
	 */
	public static function parseUploadedFiles($uploadedFiles) : array
	{
		/* */
		$parsed = [];

		foreach ($uploadedFiles as $field => $uploadedFile) {

			$parsed[$field] = new self(
				(string)$uploadedFile['tmp_name'],
				(string)$uploadedFile['name'],
				(isset($uploadedFile['type'])) ? (string)$uploadedFile['type'] : NULL,
				(isset($uploadedFile['size'])) ? (int)$uploadedFile['size'] : NULL,
				(isset($uploadedFile['error'])) ? (int)$uploadedFile['error'] : 0,
				true
			);
		}

		return $parsed;
	}

	/**
	 * Construct a new UploadedFile instance.
	 *
	 * @param string      $file The full path to the uploaded file provided by the client.
	 * @param string      $name The file name.
	 * @param string|null $type The file media type.
	 * @param int|null    $size The file size in bytes.
	 * @param int         $error The UPLOAD_ERR_XXX code representing the status of the upload.
	 * @param boolean $sapi Indicates if the upload is in a SAPI environment.
	 */
	public function __construct(
		string $file,
		string $name,
		?string $type = null,
		?int $size = null,
		int $error = \UPLOAD_ERR_OK,
		bool $sapi = false
	) {
		$this->file  = $file;
		$this->name  = $name;
		$this->type  = $type;
		$this->size  = $size;
		$this->error = $error;
		$this->sapi  = $sapi;
	}

	/**
	 * Retrieve a stream representing the uploaded file.
	 * This method MUST return a StreamInterface instance, representing the
	 * uploaded file. The purpose of this method is to allow utilizing native PHP
	 * stream functionality to manipulate the file upload, such as
	 * stream_copy_to_stream() (though the result will need to be decorated in a
	 * native PHP stream wrapper to work with such functions).
	 * If the moveTo() method has been called previously, this method MUST raise
	 * an exception.
	 * @return StreamInterface Stream representation of the uploaded file.
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException in cases when no stream is available or can be
	 *     created.
	 */
	public function getStream(): StreamInterface
	{
		if ($this->moved) {
			throw new \RuntimeException(sprintf('Uploaded file %1s has already been moved', $this->name));
		}

		if ($this->stream === null) {
			$this->stream = new Stream(\fopen($this->file, 'rb'));
		}

		return $this->stream;
	}

	private function is_writable(string $path) : bool {

		if (str_ends_with($path, '/')) {
			return $this->is_writable($path.uniqid(mt_rand()).'.tmp');
		}

		if (file_exists($path)) {
			if (!($f = @fopen($path, 'r+'))) {
				return false;
			}
			fclose($f);

			return true;
		}

		if (!($f = @fopen($path, 'w'))) {
			return false;
		}

		fclose($f);
		unlink($path);
		return true;
	}

	/**
	 * Move the uploaded file to a new location.
	 * Use this method as an alternative to move_uploaded_file(). This method is
	 * guaranteed to work in both SAPI and non-SAPI environments.
	 * Implementations must determine which environment they are in, and use the
	 * appropriate method (move_uploaded_file(), rename(), or a stream
	 * operation) to perform the operation.
	 * $targetPath may be an absolute path, or a relative path. If it is a
	 * relative path, resolution should be the same as used by PHP's rename()
	 * function.
	 * The original file or stream MUST be removed on completion.
	 * If this method is called more than once, any subsequent calls MUST raise
	 * an exception.
	 * When used in an SAPI environment where $_FILES is populated, when writing
	 * files via moveTo(), is_uploaded_file() and move_uploaded_file() SHOULD be
	 * used to ensure permissions and upload status are verified correctly.
	 * If you wish to move to a stream, use getStream(), as SAPI operations
	 * cannot guarantee writing to stream destinations.
	 * @see http://php.net/is_uploaded_file
	 * @see http://php.net/move_uploaded_file
	 * @param string $targetPath Path to which to move the uploaded file.
	 * @throws \InvalidArgumentException if the $path specified is invalid.
	 * @throws \RuntimeException on any error during the move operation, or on
	 *     the second or subsequent call to the method.
	 */
	public function moveTo($targetPath)
	{
		if ($this->moved) {
			throw new \RuntimeException('Uploaded file already moved');
		}

		$targetIsStream = strpos($targetPath, '://') > 0;
		if (!$targetIsStream && ! $this->is_writable(\dirname($targetPath))) {
			throw new \InvalidArgumentException('Upload target path is not writable');
		}

		if ($targetIsStream) {
			if (!copy($this->file, $targetPath)) {
				throw new \RuntimeException(sprintf('Error moving uploaded file %1s to %2s', $this->name, $targetPath));
			}
			if (!unlink($this->file)) {
				throw new \RuntimeException(sprintf('Error removing uploaded file %1s', $this->name));
			}
		} elseif ($this->sapi) {
			if (!is_uploaded_file($this->file)) {
				throw new \RuntimeException(sprintf('%1s is not a valid uploaded file', $this->file));
			}

			if (!move_uploaded_file($this->file, $targetPath)) {
				throw new \RuntimeException(sprintf('Error moving uploaded file %1s to %2s', $this->name, $targetPath));
			}
		} else {
			if (!rename($this->file, $targetPath)) {
				throw new \RuntimeException(sprintf('Error moving uploaded file %1s to %2s', $this->name, $targetPath));
			}
		}

		$this->moved = true;
	}

	/**
	 * Retrieve the error associated with the uploaded file.
	 * The return value MUST be one of PHP's UPLOAD_ERR_XXX constants.
	 * If the file was uploaded successfully, this method MUST return
	 * UPLOAD_ERR_OK.
	 * Implementations SHOULD return the value stored in the "error" key of
	 * the file in the $_FILES array.
	 * @see http://php.net/manual/en/features.file-upload.errors.php
	 * @return int One of PHP's UPLOAD_ERR_XXX constants.
	 */
	public function getError(): int
	{
		return $this->error;
	}

	/**
	 * Retrieve the filename sent by the client.
	 * Do not trust the value returned by this method. A client could send
	 * a malicious filename with the intention to corrupt or hack your
	 * application.
	 * Implementations SHOULD return the value stored in the "name" key of
	 * the file in the $_FILES array.
	 * @return string|null The filename sent by the client or null if none
	 *     was provided.
	 */
	public function getClientFilename(): ?string
	{
		return $this->name;
	}

	/**
	 * Retrieve the media type sent by the client.
	 * Do not trust the value returned by this method. A client could send
	 * a malicious media type with the intention to corrupt or hack your
	 * application.
	 * Implementations SHOULD return the value stored in the "type" key of
	 * the file in the $_FILES array.
	 * @return string|null The media type sent by the client or null if none
	 *     was provided.
	 */
	public function getClientMediaType(): ?string
	{
		return $this->type;
	}

	/**
	 * Retrieve the file size.
	 * Implementations SHOULD return the value stored in the "size" key of
	 * the file in the $_FILES array if available, as PHP calculates this based
	 * on the actual size transmitted.
	 * @return int|null The file size in bytes or null if unknown.
	 */
	public function getSize(): ?int
	{
		return $this->size;
	}
}
