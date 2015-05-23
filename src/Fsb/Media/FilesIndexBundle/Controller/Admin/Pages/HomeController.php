<?php

namespace Fsb\Media\FilesIndexBundle\Controller\Admin\Pages;

use Fsb\Media\FilesIndexBundle\Controller\FrontController;

class HomeController extends FrontController
{
    public function indexAction()
    {
        return $this->render('FsbMediaFilesIndexBundle:Admin/Pages/Home:index.html.twig');
    }
}
