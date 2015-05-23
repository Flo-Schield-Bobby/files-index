<?php

namespace Fsb\Media\FilesIndexBundle\Controller;

use DateTime;
use SplFileInfo;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FrontController extends Controller
{
    protected $cacheValidity = 300;

    protected function downloadFile($folder, $filename, $notFoundMessage = 'File not found')
    {
        $fs = new Filesystem();

        if (!$fs->exists($folder . $filename)) {
            throw $this->createNotFoundException($notFoundMessage);
        }

        $infos = new SplFileInfo($folder . $filename);
        $file = $infos->openFile('r');

        $filesize = $infos->getSize();
        $downloadedName = $infos->getBasename();

        $response = new Response();

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

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $downloadedName
        );

        $response->headers->set('Content-Description', 'File Transfer');
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Transfer-Encoding', 'binary;');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Length', $filesize);

        // Set Content
        $response->setContent($file->fread($filesize));

        // ETag
        $response->setETag(md5($response->getContent()));
        $response->isNotModified($this->getRequest());

        return $response;
    }

    protected function serveFile($file)
    {
        $response = new BinaryFileResponse($file);

        return $response;
    }
}
