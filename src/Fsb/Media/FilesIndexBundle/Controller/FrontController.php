<?php

namespace Fsb\Media\FilesIndexBundle\Controller;

use DateTime;
use SplFileInfo;
use SplFileObject;

use Symfony\Component\HttpFoundation\Request;
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
	protected $temporaryFolder = 'temp/';

	protected function sendFile($filepath, $filename = null)
	{
		return $this->prepareBinaryFileResponse($filepath, ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename, true);
	}

	protected function forceDownloadFile($filepath, $filename = null)
	{
		return $this->prepareBinaryFileResponse($filepath, ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
	}

	protected function displayFile($filepath, $filename = null)
	{
		return $this->prepareBinaryFileResponse($filepath, ResponseHeaderBag::DISPOSITION_INLINE, $filename);
	}

	protected function prepareBinaryFileResponse($filepath, $attachment = ResponseHeaderBag::DISPOSITION_INLINE, $filename = null, $fromHttpServer = false)
	{
		$file = new SplFileInfo($filepath);

		if (!$filename) {
			$filename = $this->sanitise($file->getFilename());
		}

		$fileSystem = new Filesystem();

		if (!$fileSystem->exists($filepath)) {
			throw $this->createNotFoundException(sprintf($this->get('translator')->trans('exceptions.files.404', array(), 'files'), $filename));
		}

		$temporaryFilepath = $this->temporaryFolder . $filename;
		$fileSystem->copy($filepath, $temporaryFilepath);

		session_write_close();

		$response = new BinaryFileResponse($temporaryFilepath);
		$response->setContentDisposition($attachment, $filename);

		if ($fromHttpServer) {
			$response->headers->set('X-Sendfile', $filepath);
			$response->trustXSendfileTypeHeader();
		} else {
			$response->deleteFileAfterSend(true);
		}

		return $response;
	}

	protected function streamFile(Request $request, $filepath, $filename = null)
	{
		$file = new SplFileObject($filepath);

		if (!$filename) {
			$filename = $this->sanitise($file->getFilename());
		}

		if (!$this->checkFilePath($filepath)) {
			throw $this->createNotFoundException(sprintf($this->get('translator')->trans('exceptions.files.404', array(), 'files'), $filename));
		}

		session_write_close();

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

	protected function sanitise($string)
	{
		if (!empty($characters)) {
			$string = str_replace((array) $characters, ' ', $string);
		}

		$sanitisedString = trim($string);

		$sanitisedString = iconv('UTF-8', 'ASCII//TRANSLIT', $sanitisedString);
		$sanitisedString = str_replace("'", ' ', $sanitisedString);

		$sanitisedString = preg_replace('/[áàâäāãå]/mi', 'a', $sanitisedString);
		$sanitisedString = preg_replace('/[éèêëēėę]/mi', 'e', $sanitisedString);
		$sanitisedString = preg_replace('/[íìîïī]/mi', 'i', $sanitisedString);
		$sanitisedString = preg_replace('/[óòôöōø]/mi', 'o', $sanitisedString);
		$sanitisedString = preg_replace('/[úùûüū]/mi', 'u', $sanitisedString);
		$sanitisedString = preg_replace('/[çćč]/mi', 'u', $sanitisedString);
		$sanitisedString = preg_replace('/[ñń]/mi', 'n', $sanitisedString);
		$sanitisedString = preg_replace('/[ÿ]/mi', 'y', $sanitisedString);
		$sanitisedString = preg_replace('/[\/_|+ -*\`\"]/mi', '-', $sanitisedString);
		$sanitisedString = preg_replace('/[^a-zA-Z0-9\/_|+.-]/mi', '', $sanitisedString);

		$sanitisedString = strtolower(filter_var($sanitisedString, FILTER_SANITIZE_URL));

		return $sanitisedString;
	}
}
