<?php

namespace Fsb\Media\FilesIndexBundle\Controller;

use DateTime;
use SplFileInfo;
use SplFileObject;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $response = new Response();

        if (!$this->checkFilePath($filepath)) {
            throw $this->createNotFoundException($notFoundMessage);
        }

        $infos = new SplFileInfo($filepath);
        $file = $infos->openFile('r');

        $filesize = $infos->getSize();
        $downloadedName = $filename;

        session_write_close();

        $response->setStatusCode(200);
        $response->setPublic();

        // Expiration Date
        $expiresAt = new DateTime();
        $expiresAt->modify('+' . $this->cacheValidity . ' seconds');
        $response->setExpires($expiresAt);

        // Response Max Age
        $response->setMaxAge($this->cacheValidity);
        $response->setSharedMaxAge($this->cacheValidity);

        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $downloadedName);

        $response->headers->set('Content-Description', 'File Transfer');
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Transfer-Encoding', 'binary;');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Length', $filesize);

        $response->sendHeaders();

        // Set Content --- Memory Leak here...
        $content = '';
        while (!$file->eof()) {
            $content .= $file->fread($this->chunkSize);
        }
        $response->setContent($content);

        // Close the file handler
        $file = null;

        // ETag
        $response->setETag(md5($response->getContent()));
        $response->isNotModified($this->getRequest());

        $response->sendContent();
    }

    protected function serveFile($filepath)
    {
        if (!$this->checkFilePath($filepath)) {
            throw $this->createNotFoundException($notFoundMessage);
        }

        $response = new BinaryFileResponse($filepath);
        // Apache X-Sendfile header
        $response->trustXSendfileTypeHeader();

        return $response;
    }

    protected function streamFile($file)
    {
        $request = $this->getRequest();
        $response = new StreamedResponse();

        $response->setCallback(function () use ($file) {
            $chunkSize = 64;
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
