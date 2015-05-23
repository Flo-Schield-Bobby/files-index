<?php

namespace Fsb\Media\FilesIndexBundle\Controller\Admin\Security;

use Symfony\Component\Security\Core\SecurityContext;

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

        return $this->render('FsbMediaFilesIndexBundle:Admin/Security/Authentication:login.html.twig', array(
            'last_username' => $session->get(SecurityContext::LAST_USERNAME),
            'error'         => $error,
        ));
    }
}
