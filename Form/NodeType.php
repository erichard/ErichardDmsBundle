<?php

namespace Erichard\DmsBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

class NodeType extends AbstractType
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
            ->add('enabled')
            ->add('metadatas', 'node_metadata', array(
                'label' => false,
                'mapped' => false
            ))
        ;
    }

    public function getName()
    {
        return 'node';
    }

}
