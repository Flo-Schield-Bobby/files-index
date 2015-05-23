<?php

namespace Fsb\Media\FilesIndexBundle\Controller\Admin\Pages\Crud;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * News controller.
 *
 */
class EntityController extends Controller
{
    /*
     * Define Entity Template with namespace, entity and template name.
     */
    protected function getTemplate($namespace, $entityName, $templateName)
    {
        return $namespace . ':Admin/Pages/Crud:' . $entityName . '/' . $templateName;
    }

    //                                                                      //
    //                        Back-End Crud Methods                         //
    //                                                                      //

    /**
     * CRUD Listing page
     *
     * @param string    $entityName                 The Entity Name
     * @param string    $entityRepositoryNamespace  The EntityRepository Namespace
     * @param string    $entityViewNamespace        The Entity View Namespace
     * @return Response
     */
    public function listEntities($entityName, $entityRepositoryNamespace, $entityViewNamespace, $additionnalParameters = array())
    {
        $em = $this->getDoctrine()->getManager();

        $entities = array();
        $repository = $em->getRepository($entityRepositoryNamespace . ':' . $entityName);

        if (method_exists($repository, 'findAllSorted')) {
            $entities = $repository->findAllSorted();
        } else {
            $entities = $repository->findAll();
        }

        $parameters = array(
            'entities' => $entities
        );

        if ($additionnalParameters) {
            $parameters = array_merge($parameters, $additionnalParameters);
        }

        return $this->render($this->getTemplate($entityViewNamespace, $entityName, 'index.html.twig'), $parameters);
    }

    /**
     * CRUD Creation form page
     *
     * @param string    $entity                     The Entity instance
     * @param string    $form                       The Entity FormType
     * @param string    $entityName                 The Entity Name
     * @param string    $entityViewNamespace        The Entity View Namespace
     * @return Response
     */
    public function renderCreationForm($entity, $form, $entityName, $entityViewNamespace, $additionnalParameters = array())
    {
        $form = $this->createForm($form, $entity);

        $parameters = array(
            'entity' => $entity,
            'form' => $form->createView()
        );

        if ($additionnalParameters) {
            $parameters = array_merge($parameters, $additionnalParameters);
        }

        return $this->render($this->getTemplate($entityViewNamespace, $entityName, 'new.html.twig'), $parameters);
    }

    /**
     * CRUD Creation page
     *
     * @param Request   $request                The current request
     * @param string    $entity                 The Entity instance
     * @param string    $form                   The Entity FormType
     * @param string    $entityName             The Entity Name
     * @param string    $entityViewNamespace    The Entity View Namespace
     * @param string    $redirection            The route to redirect when the creation is done
     * @param string    $additionnalParameters  Some eventual additionnal parameters for the redirection
     * @param string    $postFlushCallback      An eventual callback to call after the database storage
     * @return Response
     */
    public function createEntity($request, $entity, $form, $entityName, $entityViewNamespace, $redirection, $additionnalParameters = array(), $preFlushCallback = null, $postFlushCallback = null)
    {
        $form = $this->createForm($form, $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            if ($preFlushCallback && is_callable($preFlushCallback)) {
                $preFlushCallback($entity);
            }

            $em->persist($entity);
            $em->flush();

            if ($postFlushCallback && is_callable($postFlushCallback)) {
                $postFlushCallback($entity);
            }

            $this->getRequest()->getSession()->getFlashBag()->add('success', 'La nouvelle entité "' . $entityName . '": [' . $entity . '] a bien été créée.');

            if (array_key_exists('routeParams', $additionnalParameters)) {
                $route = $this->generateUrl($redirection, array_merge($additionnalParameters['routeParams'], array('id' => $entity->getId())));
            } else {
                $route = $this->generateUrl($redirection, array('id' => $entity->getId()));
            }

            return $this->redirect($route);
        } else {
            $this->getRequest()->getSession()->getFlashBag()->add('error', 'L\'entité "' . $entityName . '": [' . $entity . '] n\'a pas pu être créée.');
        }

        $parameters = array(
            'entity' => $entity,
            'form' => $form->createView()
        );

        if ($additionnalParameters) {
            $parameters = array_merge($parameters, $additionnalParameters);
        }

        return $this->render($this->getTemplate($entityViewNamespace, $entityName, 'new.html.twig'), $parameters);
    }

    /**
     * CRUD Show page
     *
     * @param string    $id                         The Entity id
     * @param string    $entityName                 The Entity Name
     * @param string    $entityRepositoryNamespace  The EntityRepository Namespace
     * @param string    $entityViewNamespace        The Entity View Namespace
     * @return Response
     */
    public function showEntity($id, $entityName, $entityRepositoryNamespace, $entityViewNamespace, $additionnalParameters = array())
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($entityRepositoryNamespace . ':' . $entityName)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ' . $entityName . ' entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        $parameters = array(
            'entity' => $entity,
            'delete_form' => $deleteForm->createView()
        );

        if ($additionnalParameters) {
            $parameters = array_merge($parameters, $additionnalParameters);
        }

        return $this->render($this->getTemplate($entityViewNamespace, $entityName, 'show.html.twig'), $parameters);
    }

    /**
     * CRUD Enable page
     *
     * @param string    $id                         The Entity id
     * @param string    $entityName                 The Entity Name
     * @param string    $entityRepositoryNamespace  The EntityRepository Namespace
     * @param string    $redirection                The route to redirect when the creation is done
     * @return RedirectResponse
     */
    public function activateEntity($id, $entityName, $entityRepositoryNamespace, $redirection, $additionnalParameters = array())
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($entityRepositoryNamespace . ':' . $entityName)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ' . $entityName . ' entity.');
        }

        $entity->setIsActive(true);
        $em->persist($entity);
        $em->flush();
        $this->getRequest()->getSession()->getFlashBag()->add('info', 'L\'entité "' . $entityName . '": [' . $entity . '] est désormais autorisée à être affichée.');

        if (array_key_exists('routeParams', $additionnalParameters)) {
            $route = $this->generateUrl($redirection, array_merge($additionnalParameters['routeParams']));
        } else {
            $route = $this->generateUrl($redirection);
        }

        return $this->redirect($route);
    }

    /**
     * CRUD Disable page
     *
     * @param string    $id                         The Entity id
     * @param string    $entityName                 The Entity Name
     * @param string    $entityRepositoryNamespace  The EntityRepository Namespace
     * @param string    $redirection                The route to redirect when the creation is done
     * @return RedirectResponse
     */
    public function unactivateEntity($id, $entityName, $entityRepositoryNamespace, $redirection, $additionnalParameters = array())
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($entityRepositoryNamespace . ':' . $entityName)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ' . $entityName . ' entity.');
        }

        $entity->setIsActive(false);
        $em->persist($entity);
        $em->flush();
        $this->getRequest()->getSession()->getFlashBag()->add('info', 'L\'entité "' . $entityName . '": [' . $entity . '] est désormais masquée.');

        if (array_key_exists('routeParams', $additionnalParameters)) {
            $route = $this->generateUrl($redirection, array_merge($additionnalParameters['routeParams']));
        } else {
            $route = $this->generateUrl($redirection);
        }

        return $this->redirect($route);
    }

    /**
     * CRUD Update form page
     *
     * @param string    $id                         The Entity id
     * @param string    $form                       The Entity FormType
     * @param string    $entityName                 The Entity Name
     * @param string    $entityRepositoryNamespace  The EntityRepository Namespace
     * @param string    $entityViewNamespace        The Entity View Namespace
     * @return Response
     */
    public function renderEditForm($id, $form, $entityName, $entityRepositoryNamespace, $entityViewNamespace, $additionnalParameters = array())
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($entityRepositoryNamespace . ':' . $entityName)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ' . $entityName . ' entity.');
        }

        $editForm = $this->createForm($form, $entity);
        $deleteForm = $this->createDeleteForm($id);

        $parameters = array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView()
        );

        if ($additionnalParameters) {
            $parameters = array_merge($parameters, $additionnalParameters);
        }

        return $this->render($this->getTemplate($entityViewNamespace, $entityName, 'edit.html.twig'), $parameters);
    }

    /**
     * CRUD Update page
     *
     * @param Request   $request                    The current request
     * @param string    $id                         The Entity id
     * @param string    $form                       The Entity FormType
     * @param string    $entityName                 The Entity Name
     * @param string    $entityRepositoryNamespace  The EntityRepository Namespace
     * @param string    $entityViewNamespace        The Entity View Namespace
     * @param string    $redirection                The route to redirect when the creation is done
     * @param string    $additionnalParameters      Some eventual additionnal parameters for the redirection
     * @param string    $postFlushCallback          An eventual callback to call after the database storage
     * @return Response
     */
    public function updateEntity($request, $id, $form, $entityName, $entityRepositoryNamespace, $entityViewNamespace, $redirection, $additionnalParameters = array(), $preFlushCallback = null, $postFlushCallback = null)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($entityRepositoryNamespace . ':' . $entityName)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ' . $entityName . ' entity.');
        }

        $editForm = $this->createForm($form, $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            if ($preFlushCallback && is_callable($preFlushCallback)) {
                $preFlushCallback($entity);
            }

            $em->persist($entity);
            $em->flush();

            if ($postFlushCallback && is_callable($postFlushCallback)) {
                $postFlushCallback($entity);
            }

            $this->getRequest()->getSession()->getFlashBag()->add('success', 'L\'entité "' . $entityName . '": [' . $entity . '] a bien été mise à jour.');

            if (array_key_exists('routeParams', $additionnalParameters)) {
                $route = $this->generateUrl($redirection, array_merge($additionnalParameters['routeParams'], array('id' => $entity->getId())));
            } else {
                $route = $this->generateUrl($redirection, array('id' => $entity->getId()));
            }

            return $this->redirect($route);
        } else {
            $this->getRequest()->getSession()->getFlashBag()->add('error', 'L\'entité "' . $entityName . '": [' . $entity . '] n\'a pas pu être mise à jour.');
        }

        $deleteForm = $this->createDeleteForm($id);

        $parameters = array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView()
        );

        if ($additionnalParameters) {
            $parameters = array_merge($parameters, $additionnalParameters);
        }

        return $this->render($this->getTemplate($entityViewNamespace, $entityName, 'edit.html.twig'), $parameters);
    }

    /**
     * CRUD Remove page
     *
     * @param Request   $request                    The current request
     * @param string    $id                         The Entity id
     * @param string    $entityName                 The Entity Name
     * @param string    $entityRepositoryNamespace  The EntityRepository Namespace
     * @param string    $redirection                The route to redirect when the creation is done
     * @return Response
     */
    public function removeEntity($request, $id, $entityName, $entityRepositoryNamespace, $redirection, $additionnalParameters = array())
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository($entityRepositoryNamespace . ':' . $entityName)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find ' . $entityName . ' entity.');
            }

            $entityDisplayName = $entity->__toString();

            $entity->setRemovedAt(new \DateTime());
            $em->persist($entity);
            $em->flush();

            $this->getRequest()->getSession()->getFlashBag()->add('success', 'L\'entité "' . $entityName . '": [' . $entityDisplayName . '] a bien été supprimée.');
        } else {
            $this->getRequest()->getSession()->getFlashBag()->add('error', 'L\'entité "' . $entityName . '": [' . $entity . '] n\'a pas pu être supprimée.');
        }

        if (array_key_exists('routeParams', $additionnalParameters)) {
            $route = $this->generateUrl($redirection, array_merge($additionnalParameters['routeParams']));
        } else {
            $route = $this->generateUrl($redirection);
        }

        return $this->redirect($route);
    }

    /**
     * CRUD Delete page
     *
     * @param Request   $request                    The current request
     * @param string    $id                         The Entity id
     * @param string    $entityName                 The Entity Name
     * @param string    $entityRepositoryNamespace  The EntityRepository Namespace
     * @param string    $redirection                The route to redirect when the creation is done
     * @return Response
     */
    public function deleteEntity($request, $id, $entityName, $entityRepositoryNamespace, $redirection, $additionnalParameters = array())
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository($entityRepositoryNamespace . ':' . $entityName)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find ' . $entityName . ' entity.');
            }

            $entityDisplayName = $entity->__toString();

            $em->remove($entity);
            $em->flush();

            $this->getRequest()->getSession()->getFlashBag()->add('success', 'L\'entité "' . $entityName . '": [' . $entityDisplayName . '] a bien été supprimée de la base de données.');
        } else {
            $this->getRequest()->getSession()->getFlashBag()->add('error', 'L\'entité "' . $entityName . '": [' . $entity . '] n\'a pas pu être supprimée de la base de données.');
        }

        if (array_key_exists('routeParams', $additionnalParameters)) {
            $route = $this->generateUrl($redirection, array_merge($additionnalParameters['routeParams']));
        } else {
            $route = $this->generateUrl($redirection);
        }

        return $this->redirect($route);
    }

    /**
     * Creates a form to delete any entity by id.
     *
     * @param mixed $id The entity id
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    public function sanitise($string)
    {
        return $this->safeUrlEncode(strtolower(filter_var($string, FILTER_SANITIZE_URL)), "'", '-');
    }

    public function safeUrlEncode($url, $characters = array(), $delimiter = '-')
    {
        if (!empty($characters)) {
            $url = str_replace((array) $characters, ' ', $url);
        }

        $cleanUrl = iconv('UTF-8', 'ASCII//TRANSLIT', $url);
        $cleanUrl = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $cleanUrl);
        $cleanUrl = strtolower(trim($cleanUrl, '-'));
        $cleanUrl = preg_replace("/[\/_|+ -]+/", $delimiter, $cleanUrl);

        return $cleanUrl;
    }
}
