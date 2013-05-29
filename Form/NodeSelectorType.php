<?php

namespace Erichard\DmsBundle\Form;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Erichard\DmsBundle\Form\Transformer\NodeToIdTransformer;
use Erichard\DmsBundle\Iterator\GedmoTreeIterator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class NodeSelectorType extends AbstractType
{
    protected $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new NodeToIdTransformer($this->registry));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $choices = array();

        $repository = $this
            ->registry
            ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
        ;

        $tree = $repository
            ->getNodesHierarchyQueryBuilder(null, false, array(
                'childSort' => array('field' => 'name', 'dir' => 'asc')), true)
            ->getQuery()
            ->getArrayResult()
        ;

        $tree = $repository->buildTree($tree);

        // On met en place notre itérator en vue de retirer la profondeur de nos éléments
        $iterator = new \RecursiveIteratorIterator(
            new GedmoTreeIterator($tree), \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $node) {
            $depth = $iterator->getDepth();
            $choices[$node['id']] = str_repeat("&nbsp;&nbsp;&nbsp;", $depth).$node['name'];
        }

        $resolver->setDefaults(array(
            'choices'       => $choices
        ));
    }

    public function getParent()
    {
        return 'choice';
    }

    public function getName()
    {
        return 'dms_node_selector';
    }
}
