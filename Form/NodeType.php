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
            ->add('enabled', 'checkbox', array(
                'required' => false
            ))
            ->add('metadatas', 'node_metadata', array(
                'label' => false,
                'mapped' => false
            ))
        ;

        $factory = $builder->getFormFactory();
        $registry = $this->registry;

        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($factory, $registry) {

                $data = $event->getData();
                $form = $event->getForm();

                if (null === $data || null === $data->getId() ) {
                    return;
                }

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
                ;

                if (count($descendants) > 0) {
                    $tree
                        ->andWhere('node.id NOT IN (:node_id)')
                        ->setParameter('node_id', $descendants)
                     ;
                }

                $tree = $repository->buildTree($tree->getQuery()->getArrayResult());

                // On met en place notre itérator en vue de retirer la profondeur de nos éléments
                $iterator = new \RecursiveIteratorIterator(
                    new GedmoTreeIterator($tree), \RecursiveIteratorIterator::SELF_FIRST
                );

                $choices = array();
                foreach ($iterator as $node) {
                    $depth = $iterator->getDepth();
                    $choices[$node['id']] = str_repeat("&nbsp;&nbsp;&nbsp;", $depth).$node['name'];
                }

                $form->add(
                    $factory->createNamedBuilder('parent', 'choice', $data->getParent(), array(
                        'required'      => false,
                        'choices'       => $choices,
                        'empty_value'   => 'documentNode.form.parent_empty_value'
                    ))->addModelTransformer(new NodeToIdTransformer($registry))
                    ->getForm()
                );

                $form->add($factory->createNamed('_locale', 'hidden', $data->getLocale(), array(
                    'property_path' => false,
                    'data'          => $data->getLocale(),
                )));
            })
            ->addEventListener(FormEvents::POST_BIND, function(FormEvent $event) use ($factory) {
                $node = $event->getData();
                $form = $event->getForm();

                if (null === $node) {
                    return;
                }

               if ($form->has('_locale')) {
                    $locale = $form->get('_locale');
                    $node->setLocale($locale->getViewData());
                }
            })
        ;
    }

    public function getName()
    {
        return 'dms_node';
    }

}
