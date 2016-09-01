<?php

namespace ManaPHP\Image\Adapter;

use ManaPHP\Image\AdapterInterface;
use ManaPHP\Image\Adapter\Imagick\Exception as ImagickException;

class Imagick implements AdapterInterface
{
    /**
     * @var string
     */
    protected $_file;

    /**
     * @var string
     */
    protected $_real_path;

    /**
     * @var \Imagick
     */
    protected $_image;

    /**
     * @var int
     */
    protected $_width;

    /**
     * @var int
     */
    protected $_height;

    /**
     * @param string $file
     *
     * @throws \ManaPHP\Image\Adapter\Exception
     */
    public function __construct($file)
    {
        if (!extension_loaded('imagick')) {
            throw new ImagickException('Imagick is not installed, or the extension is not loaded'/**m08adb1315d01ac35d*/);
        }

        $this->_file = $file;
        $this->_image = new \Imagick();
        $real_path = realpath($this->_file);
        if ($real_path) {
            $this->_real_path = $real_path;
            if (!$this->_image->readImage($this->_real_path)) {
                throw new ImagickException('Imagick::readImage `:file` failed'/**m0bde8a84f102e2334*/, ['file' => $this->_file]);
            }

            if ($this->_image->getNumberImages() !== 1) {
                throw new ImagickException('not support multiple iterations: `:file`'/**m02c9881cd81a06a01*/, ['file' => $this->_file]);
            }

            if (!$this->_image->getImageAlphaChannel()) {
                $this->_image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_SET);
            }
        } else {
            throw new ImagickException('`:file` file is not exists'/**m03d72c93d7f919633*/, ['file' => $file]);
        }

        $this->_width = $this->_image->getImageWidth();
        $this->_height = $this->_image->getImageHeight();
    }

    /**
     * Image width
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->_width;
    }

    /**
     * Image height
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->_height;
    }

    public function getInternalHandle()
    {
        return $this->_image;
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return static
     */
    public function resize($width, $height)
    {
        $this->_image->scaleImage($width, $height);

        $this->_width = $this->_image->getImageWidth();
        $this->_height = $this->_image->getImageHeight();

        return $this;
    }

    /**
     * Rotate the image by a given degrees
     *
     * @param int   $degrees
     * @param int   $background
     * @param float $alpha
     *
     * @return static
     */
    public function rotate($degrees, $background = 0xffffff, $alpha = 1.0)
    {
        $backgroundColor = sprintf('rgba(%u,%u,%u,%f)', ($background >> 16) & 0xFF, ($background >> 8) & 0xFF,
            $background & 0xFF, $alpha);
        $this->_image->rotateImage(new \ImagickPixel($backgroundColor), $degrees);
        $this->_image->setImagePage($this->_width, $this->_height, 0, 0);

        $this->_width = $this->_image->getImageWidth();
        $this->_height = $this->_image->getImageHeight();

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @param int $offsetX
     * @param int $offsetY
     *
     * @return static
     */
    public function crop($width, $height, $offsetX = 0, $offsetY = 0)
    {
        $this->_image->cropImage($width, $height, $offsetX, $offsetY);
        $this->_image->setImagePage($width, $height, 0, 0);

        $this->_width = $this->_image->getImageWidth();
        $this->_height = $this->_image->getImageHeight();

        return $this;
    }

    /**
     * Execute a text
     *
     * @param string $text
     * @param int    $offsetX
     * @param int    $offsetY
     * @param float  $opacity
     * @param int    $color
     * @param int    $size
     * @param string $font_file
     *
     * @return static
     */
    public function text(
        $text,
        $offsetX = 0,
        $offsetY = 0,
        $opacity = 1.0,
        $color = 0x000000,
        $size = 12,
        $font_file = null
    ) {
        $draw = new \ImagickDraw();
        $textColor = sprintf('rgb(%u,%u,%u)', ($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF);
        $draw->setFillColor(new \ImagickPixel($textColor));
        if ($font_file) {
            $draw->setFont($font_file);
        }
        $draw->setFontSize($size);
        $draw->setFillOpacity($opacity);
        $draw->setGravity(\Imagick::GRAVITY_NORTHWEST);
        $this->_image->annotateImage($draw, $offsetX, $offsetY, 0, $text);
        $draw->destroy();

        return $this;
    }

    /**
     * @param string $file
     * @param int    $offsetX
     * @param int    $offsetY
     * @param float  $opacity
     *
     * @return static
     * @throws \ManaPHP\Image\Adapter\Exception
     */
    public function watermark($file, $offsetX = 0, $offsetY = 0, $opacity = 1.0)
    {
        $watermark = new \Imagick($file);

        if ($watermark->getImageAlphaChannel() === \Imagick::ALPHACHANNEL_UNDEFINED) {
            $watermark->setImageOpacity($opacity);
        }

        if ($watermark->getNumberImages() !== 1) {
            throw new ImagickException('not support multiple iterations: `:file`'/**m091516b22452f192b*/, ['file' => $file]);
        }

        if (!$this->_image->compositeImage($watermark, \Imagick::COMPOSITE_OVER, $offsetX, $offsetY)) {
            throw new ImagickException('Imagick::compositeImage Failed'/**m0143717a75e945e37*/);
        }

        $watermark->clear();
        $watermark->destroy();

        return $this;
    }

    /**
     * @param string|null $file
     * @param int         $quality
     *
     * @throws \ManaPHP\Image\Adapter\Exception
     */
    public function save($file = null, $quality = 80)
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        $this->_image->setFormat($ext);

        if ($ext === 'gif') {
            $this->_image->optimizeImageLayers();
        } else {
            if ($ext === 'jpg' || $ext === 'jpeg') {
                $this->_image->setImageCompression(\Imagick::COMPRESSION_JPEG);
                $this->_image->setImageCompressionQuality($quality);
            }
        }

        if ($file === null) {
            $file = $this->_file;
        } else {
            $dir = dirname($file);
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new ImagickException('create `:dir` image directory failed: :message'/**m0798bf2f57ec615b2*/, ['dir' => $dir, 'message' => error_get_last()['message']]);
            }
        }

        if (!$this->_image->writeImage($file)) {
            throw new ImagickException('save `:file` image file failed'/**m03102b42157ab9467*/, ['file' => $file]);
        }
    }

    public function __destruct()
    {
        $this->_image->clear();
        $this->_image->destroy();
    }
}