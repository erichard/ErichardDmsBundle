<?php

namespace Erichard\DmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('ErichardDmsBundle:Default:index.html.twig', array('name' => $name));
    }
}
