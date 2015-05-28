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

    protected function sendFile($filepath)
    {
        session_write_close();

        $response = new BinaryFileResponse($filepath);

        // Set Response public
        $response->setPublic();

        //
        // Apache X-Sendfile header
        // This line should be removed in case the app is :
        //  - Not running on an apache server
        //  - Running on an apache server without mod_xsendfile enabled
        //
        $response->headers->set('X-Sendfile', $filepath);
        $response->trustXSendfileTypeHeader();

        return $response;
    }

    protected function downloadFile($filepath, $filename, $notFoundMessage = 'File not found')
    {
        if (!$this->checkFilePath($filepath)) {
            throw $this->createNotFoundException($notFoundMessage);
        }

        session_write_close();

        $infos = new SplFileInfo($filepath);

        $response = new BinaryFileResponse($filepath);
        $response->setStatusCode(200);

        // Set Response public
        $response->setPublic();

        // Manage Response headers
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Length', $infos->getSize());

        return $response;
    }

    protected function serveFile($filepath)
    {
        if (!$this->checkFilePath($filepath)) {
            throw $this->createNotFoundException($notFoundMessage);
        }

        session_write_close();

        $response = new BinaryFileResponse($filepath);
        $response->setPublic();

        return $response;
    }

    protected function streamFile($filepath)
    {
        if (!$this->checkFilePath($filepath)) {
            throw $this->createNotFoundException($notFoundMessage);
        }

        $file = new SplFileObject($filepath);

        session_write_close();

        $request = $this->getRequest();

        $response = new StreamedResponse();

        // Set Response public
        $response->setPublic();

        // Manage Response headers
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->getFilename());
        $response->headers->set('Content-Disposition', $contentDisposition);
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Length', $file->getSize());

        // Prepare Response then send headers
        $response->prepare($request);
        $response->sendHeaders();

        $chunkSize = $this->chunkSize;

        $response->setCallback(function () use ($file, $chunkSize) {
            while (!$file->eof()) {
                echo base64_decode($file->fread($chunkSize));
            }

            // Close the file handler
            $file = null;
        });

        $response->sendContent();
    }

    protected function checkFilePath($filepath)
    {
        $fs = new Filesystem();

        return $fs->exists($filepath);
    }
}
