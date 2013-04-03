Install & Configure
===================

Installation
------------

This is installable via [Composer](https://getcomposer.org/) as [erichard/dms-bundle](https://packagist.org/packages/erichard/dms-bundle).

Configuration
-------------

Add the bundle to your AppKernel class.

```
// app/AppKernel.php
public function registerBundles()
{
    return array(
        // ...
        new Erichard\DmsBundle\ErichardDmsBundle(),
        // ...
    );
}
```
