<?php
declare(strict_types=1);
namespace Karthus\Http\Message;

use Psr\Http\Message\UploadedFileInterface;

class UploadFile implements UploadedFileInterface {
    private $tempName;
    private $stream;
    private $size;
    private $error;
    private $clientFileName;
    private $clientMediaType;

    /**
     * UploadFile constructor.
     *
     * @param      $tempName
     * @param      $size
     * @param      $errorStatus
     * @param null $clientFilename
     * @param null $clientMediaType
     */
    public function __construct( $tempName,$size, $errorStatus, $clientFilename = null, $clientMediaType = null) {
        $this->tempName     = $tempName;
        $this->stream       = new Stream(fopen($tempName, 'rb+'));
        $this->error        = $errorStatus;
        $this->size         = $size;
        $this->clientFileName   = $clientFilename;
        $this->clientMediaType  = $clientMediaType;
    }


    /**
     * @inheritDoc
     */
    public function getStream() {
        return $this->stream;
    }

    /**
     * @inheritDoc
     */
    public function moveTo($targetPath) {
        return file_put_contents($targetPath, $this->stream) ? true : false;
    }

    /**
     * @inheritDoc
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * @inheritDoc
     */
    public function getError() {
        return $this->error;
    }

    /**
     * @inheritDoc
     */
    public function getClientFilename() {
        return $this->clientFileName;
    }

    /**
     * @inheritDoc
     */
    public function getClientMediaType() {
        return $this->clientMediaType;
    }
}
