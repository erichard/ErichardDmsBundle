<?php

namespace Erichard\DmsBundle\Controller;


use Symfony\Component\HttpFoundation\Response;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Gd\Imagine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Basic controller for media preview
 */
class MediaController extends Controller
{

    /**
     * Show a media thumbnail
     */
    public function showAction(Request $request, $dimension, $path)
    {
        list($width, $height) = explode('x', $dimension);

        $imagine = new Imagine();
        $size    = new Box($width, $height);
        $mode    = ImageInterface::THUMBNAIL_INSET;

        while (false !== strpos($path, '%')) {
            $path = rawurldecode($path);
        }

        $absPath = $this->container->getParameter('dms.storage.path') . DIRECTORY_SEPARATOR . $path;

        if (!$absPath = realpath($absPath)) {
            $absPath = $this->container->getParameter('dms.storage.tmp_path') . DIRECTORY_SEPARATOR . $path;
            if (!$absPath = realpath($absPath)) {
                throw $this->createNotFoundException();
            }
        }

        $cacheFile = $this->get('kernel')->getRootDir() . '/../web' . $request->getRequestUri('SCRIPT_NAME');

        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0777, true);
        }

        try {
            $imagick = new \Imagick();
            $imagick->setResolution(72, 72);
            $imagick->readImage($absPath);
            $image = new \Imagine\Imagick\Image($imagick);
        } catch(\ImagickException $e) {
            $picture = $this->get('kernel')->locateResource('@ErichardDmsBundle/Resources/public/img/picture.png');
            $image = $imagine->open($picture);
        }

        $image
            ->thumbnail($size, $mode)
            ->save($cacheFile, array('quality' => 90))
        ;

        $expireDate = new \DateTime();
        $expireDate->modify('+10 years');

        $response = new Response();

        $response->setPublic();
        $response->setExpires($expireDate);
        $response->setContent(file_get_contents($cacheFile));

        $finfo = new \finfo(FILEINFO_MIME);

        $response->headers->set('Content-Type', $finfo->file($cacheFile));
        $response->setPublic();
        $response->setSharedMaxAge('3600');

        return $response;
    }
}
