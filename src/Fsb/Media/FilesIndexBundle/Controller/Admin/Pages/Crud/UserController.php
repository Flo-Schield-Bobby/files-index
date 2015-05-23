<?php

namespace Fsb\Media\FilesIndexBundle\Controller\Admin\Pages\Crud;

use Symfony\Component\HttpFoundation\Request;

use Fsb\Media\FilesIndexBundle\Entity\User;
use Fsb\Media\FilesIndexBundle\Form\UserType;

/**
 * User controller.
 *
 */
class UserController extends EntityController
{
    /**
     * Lists all User entities.
     *
     */
    public function indexAction()
    {
        return $this->listEntities('User', 'FsbMediaFilesIndexBundle', 'FsbMediaFilesIndexBundle');
    }

    /**
     * Displays a form to create a new User entity.
     *
     */
    public function newAction()
    {
        return $this->renderCreationForm(new User(), new UserType(), 'User', 'FsbMediaFilesIndexBundle');
    }

    /**
     * Creates a new User entity.
     *
     */
    public function createAction(Request $request)
    {
        return $this->createEntity($request, new User(), new UserType(), 'User', 'FsbMediaFilesIndexBundle', 'fsb_media_files_index_admin_crud_user_show', array(), function ($user) {
            $factory = $this->get('security.encoder_factory');

            $encoder = $factory->getEncoder($user);
            $password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
            $user->setPassword($password);
        });
    }

    /**
     * Finds and displays a User entity.
     *
     */
    public function showAction($id)
    {
        return $this->showEntity($id, 'User', 'FsbMediaFilesIndexBundle', 'FsbMediaFilesIndexBundle');
    }

    /**
     * Enable any User entity to be displayed in front-end.
     *
     */
    public function activateAction($id)
    {
        return $this->activateEntity($id, 'User', 'FsbMediaFilesIndexBundle', 'fsb_media_files_index_admin_crud_users');
    }

    /**
     * Disable any User entity to be displayed in front-end.
     *
     */
    public function unactivateAction($id)
    {
        return $this->unactivateEntity($id, 'User', 'FsbMediaFilesIndexBundle', 'fsb_media_files_index_admin_crud_users');
    }

    /**
     * Displays a form to edit an existing User entity.
     *
     */
    public function editAction($id)
    {
        return $this->renderEditForm($id, new UserType(), 'User', 'FsbMediaFilesIndexBundle', 'FsbMediaFilesIndexBundle');
    }

    /**
     * Edits an existing User entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        return $this->updateEntity($request, $id, new UserType(), 'User', 'FsbMediaFilesIndexBundle', 'FsbMediaFilesIndexBundle', 'fsb_media_files_index_admin_crud_users');
    }

    /**
     * Removes a User entity.
     *
     */
    public function removeAction(Request $request, $id)
    {
        return $this->removeEntity($request, $id, 'User', 'FsbMediaFilesIndexBundle', 'fsb_media_files_index_admin_crud_users');
    }
}
