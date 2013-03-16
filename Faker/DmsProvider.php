<?php

namespace Erichard\DmsBundle\Faker;

use Gedmo\Sluggable\Util\Urlizer;

class DmsProvider
{
    const IMAGE_PROVIDER = "http://lorempixel.com/%d/%d";

    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public static function slug($text, $glue = '-')
    {
        return Urlizer::urlize($text, $glue);
    }

    public static function imageLink($width, $height)
    {
        return sprintf(self::IMAGE_PROVIDER, $width, $height);
    }

    public function image($dir, $width = null, $height = null)
    {
        $width = $width ?: rand(100,300);
        $height = $height ?: rand(100,300);

        $imageName = sprintf('%s/%s/%s.png', $this->container->getParameter('dms.storage.path'), $dir, uniqid("image_{$width}x{$height}_"));
        $image = self::imageLink($width, $height);

        if (!is_dir(dirname($imageName))) {
            mkdir(dirname($imageName), 0777, true);
        }

        file_put_contents($imageName, file_get_contents($image));

        $imageName =  str_replace($this->container->getParameter('dms.storage.path'), '', $imageName);
        $imageName = trim($imageName, '/');

        return $imageName;
    }
}
