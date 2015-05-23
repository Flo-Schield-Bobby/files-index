<?php

namespace Fsb\Media\FilesIndexBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('isActive', 'checkbox', array('label' => 'Active ?', 'required' => false))
            ->add('username', 'text', array('label' => 'Username', 'required' => true))
            ->add('password', 'password', array('label' => 'Password', 'required' => true))
            ->add('rootFolder', 'text', array('label' => 'Root Folder', 'required' => true))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fsb\Media\FilesIndexBundle\Entity\User'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'fsb_media_filesindexbundle_user';
    }
}
