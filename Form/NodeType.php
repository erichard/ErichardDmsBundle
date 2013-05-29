<?php

namespace Erichard\DmsBundle\Form;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Erichard\DmsBundle\Form\Transformer\NodeToIdTransformer;
use Erichard\DmsBundle\Iterator\GedmoTreeIterator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints;

class NodeType extends AbstractType
{
    protected $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

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

        $factory = $builder->getFormFactory();
        $registry = $this->registry;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use($factory, $registry) {

            $data = $event->getData();
            $form = $event->getForm();

            $repository = $registry
                ->getRepository('Erichard\DmsBundle\Entity\DocumentNode')
            ;

            $descendants = $registry
                ->getRepository('Erichard\DmsBundle\Entity\DocumentNodeClosure')
                ->createQueryBuilder('c')
                ->select('d.id')
                ->innerJoin('c.descendant', 'd')
                ->where('c.ancestor = :id')
                ->setParameter('id', $data->getId())
                ->getQuery()
                ->getArrayResult()
            ;

            $descendants = array_map(function($descendant) { return $descendant['id'];}, $descendants);

            $tree = $repository
                ->getNodesHierarchyQueryBuilder(null, false, array(
                    'childSort' => array('field' => 'name', 'dir' => 'asc')), true)
                ->andWhere('node.id NOT IN (:node_id)')
                ->setParameter('node_id', $descendants)
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

            $form->add(
                $factory->createNamedBuilder('parent', 'choice', $data->getParent(), array(
                    'choices'       => $choices,
                    'empty_value'   => 'node_selector.no_parent'
                ))->addModelTransformer(new NodeToIdTransformer($registry))
                ->getForm()
            );
        });
    }

    public function getName()
    {
        return 'dms_node';
    }

}
