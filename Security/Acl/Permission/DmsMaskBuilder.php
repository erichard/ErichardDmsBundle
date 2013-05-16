<?php

namespace Erichard\DmsBundle\Security\Acl\Permission;

class DmsMaskBuilder
{
    const MASK_VIEW                 = 1;
    const MASK_DOCUMENT_ADD         = 2;
    const MASK_DOCUMENT_EDIT        = 4;
    const MASK_DOCUMENT_DELETE      = 8;
    const MASK_DOCUMENT_DOWNLOAD    = 16;
    const MASK_NODE_ADD             = 32;
    const MASK_NODE_EDIT            = 64;
    const MASK_NODE_DELETE          = 128;
    const MASK_NODE_DOWNLOAD        = 256;
    const MASK_MANAGE               = 512;
    const MASK_IDDQD                = 1073741823;

    const CODE_VIEW                 = 'R';
    const CODE_DOCUMENT_ADD         = 'A';
    const CODE_DOCUMENT_EDIT        = 'E';
    const CODE_DOCUMENT_DELETE      = 'D';
    const CODE_DOCUMENT_DOWNLOAD    = 'T';
    const CODE_NODE_ADD             = 'N';
    const CODE_NODE_EDIT            = 'Z';
    const CODE_NODE_DELETE          = 'X';
    const CODE_NODE_DOWNLOAD        = 'Y';
    const CODE_MANAGE               = 'M';

    const ALL_OFF           = '................................';
    const OFF               = '.';
    const ON                = '*';

    private $mask;

    /**
     * Constructor
     *
     * @param integer $mask optional; defaults to 0
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($mask = 0)
    {
        if (!is_int($mask)) {
            throw new \InvalidArgumentException('$mask must be an integer.');
        }

        $this->mask = $mask;
    }

    /**
     * Adds a mask to the permission
     *
     * @param mixed $mask
     *
     * @return MaskBuilder
     *
     * @throws \InvalidArgumentException
     */
    public function add($mask)
    {
        if (is_string($mask) && defined($name = 'static::MASK_'.strtoupper($mask))) {
            $mask = constant($name);
        } elseif (!is_int($mask)) {
            throw new \InvalidArgumentException('$mask must be an integer.');
        }

        $this->mask |= $mask;

        return $this;
    }

    /**
     * Returns the mask of this permission
     *
     * @return integer
     */
    public function get()
    {
        return $this->mask;
    }

    /**
     * Returns a human-readable representation of the permission
     *
     * @return string
     */
    public function getPattern()
    {
        $pattern = self::ALL_OFF;
        $length = strlen($pattern);
        $bitmask = str_pad(decbin($this->mask), $length, '0', STR_PAD_LEFT);

        for ($i=$length-1; $i>=0; $i--) {
            if ('1' === $bitmask[$i]) {
                try {
                    $pattern[$i] = self::getCode(1 << ($length - $i - 1));
                } catch (\Exception $notPredefined) {
                    $pattern[$i] = self::ON;
                }
            }
        }

        return $pattern;
    }

    /**
     * Removes a mask from the permission
     *
     * @param mixed $mask
     *
     * @return MaskBuilder
     *
     * @throws \InvalidArgumentException
     */
    public function remove($mask)
    {
        if (is_string($mask) && defined($name = 'static::MASK_'.strtoupper($mask))) {
            $mask = constant($name);
        } elseif (!is_int($mask)) {
            throw new \InvalidArgumentException('$mask must be an integer.');
        }

        $this->mask &= ~$mask;

        return $this;
    }

    /**
     * Resets the PermissionBuilder
     *
     * @return MaskBuilder
     */
    public function reset()
    {
        $this->mask = 0;

        return $this;
    }

    /**
     * Returns the code for the passed mask
     *
     * @param  integer                   $mask
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return string
     */
    public static function getCode($mask)
    {
        if (!is_int($mask)) {
            throw new \InvalidArgumentException('$mask must be an integer.');
        }

        $reflection = new \ReflectionClass(get_called_class());
        foreach ($reflection->getConstants() as $name => $cMask) {
            if (0 !== strpos($name, 'MASK_')) {
                continue;
            }

            if ($mask === $cMask) {
                if (!defined($cName = 'static::CODE_'.substr($name, 5))) {
                    throw new \RuntimeException('There was no code defined for this mask.');
                }

                return constant($cName);
            }
        }

        throw new \InvalidArgumentException(sprintf('The mask "%d" is not supported.', $mask));
    }
}
