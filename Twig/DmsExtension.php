<?php

namespace Erichard\DmsBundle\Twig;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Erichard\DmsBundle\DocumentInterface;
use Symfony\Component\Routing\RouterInterface;

class DmsExtension extends \Twig_Extension
{
    protected $router;

    public function __construct(RouterInterface $router, Registry $registry)
    {
        $this->router = $router;
        $this->registry = $registry;
    }

    public function getFilters()
    {
        return array(
            'filesize' => new \Twig_Filter_Method($this, 'getFileSize'),
            'shorten'  => new \Twig_Filter_Method($this, 'shorten', array(
                'is_safe' => array('html')
            ))
        );
    }

    public function getFunctions()
    {
        return array(
            'thumbUrl' => new \Twig_Function_Method($this, 'getThumbUrl'),
            'roots'    => new \Twig_Function_Method($this, 'getRoots')
        );
    }

    public function getFileSize($sizeInBytes)
    {
        $unit = array('b','kb','mb','gb','tb','pb');

        if (null === $sizeInBytes) {
            return '';
        }

        return @round($sizeInBytes/pow(1024,($i=floor(log($sizeInBytes,1024)))),2).' '.$unit[$i];
    }

    public function getThumbUrl(DocumentInterface $document, $dimension, $absolute = false)
    {
        return $this->router->generate('erichard_dms_document_preview', array(
            'document'    => $document->getSlug(),
            'node'        => $document->getNode()->getSlug(),
            'dimension'   => $dimension,
        ), $absolute);
    }

    public function getRoots()
    {
        return $this
            ->registry
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
            ->getRoots()
        ;
    }

    /**
     * Reduce a string by the middle
     */
    public function shorten($string, $max = 100, $append = '&hellip;')
    {
        if (strlen($string) <= $length) {
            $result = $string;
        } else {
            $offset = floor($length / 2) - 1;
            $result = mb_substr($string, 0, $offset) . $append . mb_substr($string,strlen($string)-$offset);
        }

        return $result;
    }

    public function getName()
    {
        return "dms_extension";
    }
}
