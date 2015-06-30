<?php

namespace Fsb\Media\FilesIndexBundle\Controller\Front\Pages;

use SplFileObject;

use Symfony\Component\HttpFoundation\Request;
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
			$error = $this->get('translator')->trans('errors.finder.404', array(), 'files');
		}

		return $this->render('FsbMediaFilesIndexBundle:Front/Pages/Home:index.html.twig', array(
			'error' => $error,
			'files' => $finder
		));
	}

	public function sendAction($filename)
	{
		return $this->sendFile($this->getFilePath($filename));
	}

	public function forceDownloadAction($filename)
	{
		return $this->forceDownloadFile($this->getFilePath($filename));
	}

	public function displayAction($filename)
	{
		return $this->displayFile($this->getFilePath($filename));
	}

	public function streamAction($filename, Request $request)
	{
		return $this->streamFile($request, $this->getFilePath($filename));
	}

	protected function getFilePath($filename)
	{
		$user = $this->getUser();
		$filepath = $this->rootFolder . $user->getRootFolder() . '/' . $filename;

		return $filepath;
	}
}
