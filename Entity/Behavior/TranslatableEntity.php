<?php

namespace Erichard\DmsBundle\Entity\Behavior;

use Gedmo\Mapping\Annotation as Gedmo;

trait TranslatableEntity
{
    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    protected $locale;

    /**
     * Overrides events listener default locale
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale()
    {
        return $this->locale;
    }

}
