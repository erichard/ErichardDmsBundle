<?php

namespace Erichard\DmsBundle\Entity;

class Metadata
{
    protected $id;

    protected $name;

    protected $label;

    protected $type;

    protected $defaultValue;

    protected $attributes;

    protected $scope;

    protected $required;

    public static $typeValues = array(
        'text'            => 'metadata.type.text',
        'textarea'        => 'metadata.type.textarea',
        'rich_text'       => 'metadata.type.rich_text',
        'integer'         => 'metadata.type.integer',
        'single_choice'   => 'metadata.type.single_choice',
        'multiple_choice' => 'metadata.type.multiple_choice',
    );

    public static $scopeValues = array(
        'document' => 'metadata.scope.document',
        'node'     => 'metadata.scope.node',
        'both'     => 'metadata.scope.both',
    );

    public function __construct()
    {
        $this->required = false;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setScope($scope)
    {
        if (null !== $scope && !isset(self::$scopeValues[$scope])) {
            throw new \InvalidArgumentException(sprintf(
                'The value "%s" is not allowed for the scope property.',
                $scope
            ));
        }

        $this->scope = $scope;

        return $this;
    }

    public function setType($type)
    {
        if (null !== $type && !isset(self::$typeValues[$type])) {
            throw new \InvalidArgumentException(sprintf(
                'The value "%s" is not allowed for the type property.',
                $type
            ));
        }

        $this->type = $type;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getTypeLabel()
    {
        return self::$typeValues[$this->type];
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function isRequired()
    {
        return $this->required;
    }
}
