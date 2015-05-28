<?php

namespace Fsb\Media\FilesIndexBundle\Controller\Front\Pages;

use SplFileObject;

use Symfony\Component\Finder\Finder;

use Fsb\Media\FilesIndexBundle\Controller\FrontController;

class HomeController extends FrontController
{
    protected $rootFolder = 'files';

    public function indexAction()
    {
        $user = $this->getUser();
        $finder = new Finder();

        $rootFolder = $this->rootFolder . $user->getRootFolder();
        $finder->files()->in($rootFolder)->sortByModifiedTime();
        $error = null;

        if (!$finder) {
            $error = 'Nous avons un petit problème ici. Une équipe de ninjas expérimentés arrive à votre secours !';
        }

        return $this->render('FsbMediaFilesIndexBundle:Front/Pages/Home:index.html.twig', array(
            'error' => $error,
            'files' => $finder
        ));
    }

    public function downloadAction($filename)
    {
        return $this->downloadFile($this->getFilePath($filename), $filename, 'Le fichier demandé n\'existe pas...');
    }

    public function serveAction($filename)
    {
        return $this->serveFile($this->getFilePath($filename));
    }

    public function streamAction($filename)
    {
        $file = new SplFileObject($this->getFilePath($filename));

        return $this->streamFile($file);
    }

    protected function getFilePath($filename)
    {
        $user = $this->getUser();
        $filepath = $this->rootFolder . $user->getRootFolder() . '/' . $filename;

        return $filepath;
    }
}
