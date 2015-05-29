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

    protected function sendFile($filepath, $notFoundMessage = 'File not found')
    {
        if (!$this->checkFilePath($filepath)) {
            throw $this->createNotFoundException($notFoundMessage);
        }

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

    protected function downloadFile($filepath, $filename = null, $notFoundMessage = 'File not found')
    {
        if (!$this->checkFilePath($filepath)) {
            throw $this->createNotFoundException($notFoundMessage);
        }

        session_write_close();

        $file = new SplFileInfo($filepath);

        if (is_null($filename)) {
            $filename = $file->getFilename();
        }

        $response = new BinaryFileResponse($filepath);
        $response->setStatusCode(200);

        // Set Response public
        $response->setPublic();

        // Manage Response headers
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Length', $file->getSize());

        return $response;
    }

    protected function serveFile($filepath, $notFoundMessage = 'File not found')
    {
        if (!$this->checkFilePath($filepath)) {
            throw $this->createNotFoundException($notFoundMessage);
        }

        session_write_close();

        $response = new BinaryFileResponse($filepath);
        $response->setPublic();

        return $response;
    }

    protected function streamFile($filepath, $filename = null, $notFoundMessage = 'File not found')
    {
        if (!$this->checkFilePath($filepath)) {
            throw $this->createNotFoundException($notFoundMessage);
        }

        session_write_close();

        $file = new SplFileObject($filepath);

        if (is_null($filename)) {
            $filename = $file->getFilename();
        }

        $request = $this->getRequest();

        $response = new StreamedResponse();
        $response->setStatusCode(200);

        // Set Response public
        $response->setPublic();

        // Manage Response headers
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->headers->set('Content-Disposition', $contentDisposition);
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Accept-Ranges', 'bytes');

        // Prepare range [default to whole content file]
        $rangeMin = 0;
        $rangeMax = $file->getSize() - 1;
        $rangeStart = $rangeMin;
        $rangeEnd = $rangeMax;

        $httpRange = $request->server->get('HTTP_RANGE');

        // Manage HTTP_RANGE if provided
        if ($httpRange) {
            $matches = array();
            $isStatisfiableRange = true;

            if (preg_match('/^bytes=((\d*-\d*,? ?)+)$/', $httpRange)) {
                list($size_unit, $range) = explode('=', $httpRange, 2);

                // Control size_unit
                if ($size_unit === 'bytes') {
                    // Unvalid multiple ranges
                    if (strpos($range, ',') === false) {
                        list($rangeStart, $rangeEnd) = explode('-', $range, 2);

                        $rangeEnd = min($rangeEnd, $rangeMax);

                        if ($rangeStart <= $rangeEnd) {
                            // Manage Range
                            if ($file->fseek($rangeStart) < 0) {
                                $response = new Response();
                                $response->setPublic();

                                $response->setStatusCode(500);
                                $response->setContent('An error has occured, the file cannot be seeked.');

                                return $response;
                            }
                            
                            $response->setStatusCode(206);
                            $response->headers->set('Content-Range', 'bytes ' . $rangeStart . '-' . $rangeEnd . '/' . $file->getSize());
                            $response->headers->set('Content-Length', $rangeEnd + 1 - $rangeStart);
                        } else {
                            $isStatisfiableRange = false;
                        }
                    } else {
                        $isStatisfiableRange = false;
                    }
                } else {
                    $isStatisfiableRange = false;
                }
            } else {
                $isStatisfiableRange = false;
            }

            if (!$isStatisfiableRange) {
                $response = new Response();
                $response->setPublic();

                $response->setStatusCode(416);
                $response->headers->set('Content-Range', 'bytes */' . $file->getSize());

                return $response;
            }
        } else {
            $response->headers->set('Content-Length', $file->getSize());
        }

        // Prepare Response then send headers
        $response->prepare($request);
        $response->sendHeaders();

        $chunkSize = $this->chunkSize;

        $response->setCallback(function () use ($file, $chunkSize, $rangeEnd) {

            while (!($file->eof()) && (($offset = $file->ftell()) < $rangeEnd)) {
                set_time_limit(0);

                if ($offset + $chunkSize > $rangeEnd) {
                    $chunkSize = $rangeEnd + 1 - $offset;
                }

                echo $file->fread($chunkSize);
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
