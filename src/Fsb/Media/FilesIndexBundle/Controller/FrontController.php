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

    /**
     * Renders a view.
     *
     * @param string   $view       The view name
     * @param array    $parameters An array of parameters to pass to the view
     * @param Response $response   A response instance
     *
     * @return Response A Response instance
     */
    public function render($view, array $parameters = array(), Response $response = null)
    {
        $request = $this->getRequest();
        $shouldCacheResponse = true;

        // Only cache GET and HEAD Responses, and if the Response object has not yet been built.
        // Because it might be already cached in a better way, otherwise.
        if (!(is_null($response)) || !(in_array($request->getMethod(), array('GET', 'HEAD')))) {
            $shouldCacheResponse = false;
        }

        if ($shouldCacheResponse) {
            $response = new Response();

            // Expiration Date
            $expiresAt = new DateTime();
            $expiresAt->modify('+' . $this->cacheValidity . ' seconds');
            $response->setExpires($expiresAt);

            // Response Max Age
            $response->setMaxAge($this->cacheValidity);
            $response->setSharedMaxAge($this->cacheValidity);
        }

        // It takes actually a while to calculate all the rendering before beeing able to check the MD5 checksum
        // However, for pages with several queries and static content such as home page, I can't figure out anything faster at the moment
        // This method can (and should!) be improved for "data-based" pages.
        $response = $this->container->get('templating')->renderResponse($view, $parameters, $response);

        if ($shouldCacheResponse) {
            $response->setPublic();
            $response->setETag(md5($response->getContent()));
            $response->isNotModified($this->getRequest());
        }

        return $response;
    }

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
