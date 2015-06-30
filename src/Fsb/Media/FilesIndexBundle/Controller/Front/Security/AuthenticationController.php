<?php

namespace Fsb\Media\FilesIndexBundle\Controller\Front\Security;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\User\User;

use Fsb\Media\FilesIndexBundle\Controller\FrontController;

class AuthenticationController extends FrontController
{
	public function loginAction()
	{
		$request = $this->getRequest();
		$session = $request->getSession();

		// get the login error if there is one
		if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
			$error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
		} else {
			$error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
			$session->remove(SecurityContext::AUTHENTICATION_ERROR);
		}

		return $this->render('FsbMediaFilesIndexBundle:Front/Security/Authentication:login.html.twig', array(
			'last_username' => $session->get(SecurityContext::LAST_USERNAME),
			'error'         => $error,
		));
	}

	public function hashAction($password)
	{
		$user = new User('anonymous', $password);

		$factory = $this->get('security.encoder_factory');

		$encoder = $factory->getEncoder($user);
		$password = $encoder->encodePassword($user->getPassword(), $user->getSalt());

		exit(var_dump($password));
	}
}
