<?php

namespace Erichard\DmsBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'required' => true,
                'constraints' => array(
                    new Constraints\NotBlank()
                )
            ))
            ->add('filename', 'hidden')
            ->add('originalName', 'hidden')
            ->add('token', 'hidden', array(
                'mapped' => false
            ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Erichard\DmsBundle\Entity\Document'
        ));
    }

    public function getName()
    {
        return 'document';
    }
}
