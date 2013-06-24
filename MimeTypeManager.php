<?php

namespace Erichard\DmsBundle;

use GetId3\GetId3Core as GetId3;
use Symfony\Component\HttpKernel\KernelInterface;

class MimeTypeManager
{
    protected $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function getMimeType($filename)
    {
        if (!is_readable($filename)) {
            throw new \InvalidArgumentException($filename.' is not readable.');
        }

        $getID3 = new GetId3;
        $info = $getID3->analyze($filename);

        return isset($info['mime_type'])? $info['mime_type'] : null;
    }

    public function getMimetypeImage($filename, $size)
    {
        $mimetype = $this->getMimeType($filename);

        $sizes = array(16,22,24,32,48,64,96);

        $iconSize = null;
        foreach ($sizes as $allowedSize) {
            if ($size < $allowedSize) {
                $iconSize = $allowedSize;
                break;
            }
        }

        if (null === $iconSize) {
            $iconSize = max($sizes);
        }

        $icon = null;

        if (null === $mimetype) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);

            $extensionMap = array(
                'eps' => 'image-x-eps',
                'psd' => 'image-x-psd'
            );

            if (isset($extensionMap[$extension])) {
                try {
                    $icon = $this
                        ->kernel
                        ->locateResource('@ErichardDmsBundle/Resources/public/img/mimetypes/'.$size.'/'.$extensionMap[$extension].'.png')
                    ;
                } catch (\InvalidArgumentException $e) {}
            }

        } else {
            $mimetypes = array(
                str_replace('/', '-', $mimetype),
                explode('/',$mimetype)[0]
            );

            foreach ($mimetypes as $mimetype) {
                try {
                    $icon = $this
                        ->kernel
                        ->locateResource('@ErichardDmsBundle/Resources/public/img/mimetypes/'.$size.'/'.$mimetype.'.png')
                    ;
                    break;
                } catch (\InvalidArgumentException $e) {}
            }
        }

        if (null === $icon) {
            try {
                $icon = $this
                    ->kernel
                    ->locateResource('@ErichardDmsBundle/Resources/public/img/mimetypes/'.$size.'/unknown.png')
                ;
            } catch (\InvalidArgumentException $e) {}
        }

        return $icon;
    }
}
