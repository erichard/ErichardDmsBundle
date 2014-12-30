<?php

namespace Erichard\DmsBundle;

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
        return pathinfo($filename, PATHINFO_EXTENSION);
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

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $extensionMap = array(
            'eps'  => 'image-x-eps',
            'psd'  => 'image-x-psd',
            'gif'  => 'image-gif',
            'bmp'  => 'gnome-mime-image-x-portable-bitmap',
            'doc'  => 'application-msword',
            'docx' => 'application-msword',
            'xls'  => 'spreadsheet',
            'xlt'  => 'spreadsheet',
            'xlsx' => 'spreadsheet',
            'dot'  => 'spreadsheet',
            'gzip'  => 'gnome-mime-application-x-compressed-tar',
            'docx' => 'application-msword',
            'txt'  => 'txt',
            'avi'  => 'video',
            'm4v'  => 'video',
            'mov'  => 'video',
            'mp4'  => 'video',
            'mp3'  => 'video',
            'html' => 'html',
            'csv'  => 'spreadsheet',
            'flv'  => 'video',
            'rar'  => 'gnome-mime-application-x-rar',
        );

        if (isset($extensionMap[$extension])) {
            try {
                $icon = $this
                    ->kernel
                    ->locateResource('@ErichardDmsBundle/Resources/public/img/mimetypes/'.$iconSize.'/'.$extensionMap[$extension].'.png')
                ;
            } catch (\InvalidArgumentException $e) {}
        } elseif (null !== $mimetype) {

            $mimetypes = array(
                str_replace('/', '-', $mimetype),
                explode('/',$mimetype)[0]
            );

            foreach ($mimetypes as $mimetype) {
                try {
                    $icon = $this
                        ->kernel
                        ->locateResource('@ErichardDmsBundle/Resources/public/img/mimetypes/'.$iconSize.'/'.$mimetype.'.png')
                    ;
                    break;
                } catch (\InvalidArgumentException $e) {}
            }
        }

        if (null === $icon) {
            try {
                $icon = $this
                    ->kernel
                    ->locateResource('@ErichardDmsBundle/Resources/public/img/mimetypes/'.$iconSize.'/unknown.png')
                ;
            } catch (\InvalidArgumentException $e) {}
        }

        return $icon;
    }
}
