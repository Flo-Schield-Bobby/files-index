<?php

namespace Fsb\Media\FilesIndexBundle\Controller;

use DateTime;
use SplFileInfo;
use SplFileObject;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FrontController extends Controller
{
    protected $cacheValidity = 300;
    protected $chunkSize = 128;

    protected function downloadFile($filepath, $filename, $notFoundMessage = 'File not found')
    {
        if (!$this->checkFilePath($filepath)) {
            throw $this->createNotFoundException($notFoundMessage);
        }

        session_write_close();

        $infos = new SplFileInfo($filepath);
        $filesize = $infos->getSize();
        $downloadedName = $filename;

        $response = new BinaryFileResponse($filepath);
        $response->setStatusCode(200);

        // Set Response public
        $response->setPublic();

        //
        // Apache X-Sendfile header
        // This line should be removed in case the app is :
        //  - Not running on an apache server
        //  - Running on an apache server without mod_xsendfile enabled
        //
        $response->headers->set('X-SendFile', $filepath);
        $response->trustXSendfileTypeHeader();

        // Manager Response headers
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $downloadedName);

        $response->headers->set('Content-Description', 'File Transfer');
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Length', $filesize);

        return $response;
    }

    protected function serveFile($filepath)
    {
        if (!$this->checkFilePath($filepath)) {
            throw $this->createNotFoundException($notFoundMessage);
        }

        $response = new BinaryFileResponse($filepath);

        return $response;
    }

    protected function streamFile($file)
    {
        $request = $this->getRequest();
        $response = new StreamedResponse();

        $chunkSize = $this->chunkSize;

        $response->setCallback(function () use ($file, $chunkSize) {
            while (!$file->eof()) {
                echo base64_decode($file->fread($chunkSize));
            }

            // Close the file handler
            $file = null;
        });

        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->getFilename());

        $response->headers->set('Content-Description', 'File Transfer');
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Transfer-Encoding', 'binary;');
        $response->headers->set('Content-Disposition', $contentDisposition);
        $response->headers->set('Content-Length', $file->getSize());

        $response->prepare($request);

        return $response;
    }

    protected function checkFilePath($filepath)
    {
        $fs = new Filesystem();

        return $fs->exists($filepath);
    }
}
