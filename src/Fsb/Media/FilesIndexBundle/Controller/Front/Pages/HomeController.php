<?php

namespace Fsb\Media\FilesIndexBundle\Controller\Front\Pages;

use Symfony\Component\Finder\Finder;

use Fsb\Media\FilesIndexBundle\Controller\FrontController;

class HomeController extends FrontController
{
    public function indexAction()
    {
        $user = $this->getUser();
        $finder = new Finder();

        $rootFolder = 'files' . $user->getRootFolder();
        $finder->files()->in($rootFolder);
        $error = null;

        if (!$finder) {
            $error = 'Nous avons un petit problème ici. Une équipe de ninjas expérimentés arrive à votre secours !';
        }

        return $this->render('FsbMediaFilesIndexBundle:Front/Pages/Home:index.html.twig', array(
            'error' => $error,
            'files' => $finder
        ));
    }

    public function downloadAction($file)
    {

        $user = $this->getUser();
        $finder = new Finder();

        $rootFolder = 'files' . $user->getRootFolder() . '/';

        return $this->downloadFile($rootFolder, $file, 'Le fichier demandé n\'existe pas...');
    }

    public function serveAction($file)
    {

        $user = $this->getUser();
        $finder = new Finder();

        $filepath = 'files' . $user->getRootFolder() . '/' . $file;

        return $this->serveFile($filepath);
    }
}
