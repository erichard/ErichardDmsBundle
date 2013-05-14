# Install & Configure


## Installation

This is installable via [Composer](https://getcomposer.org/) as [erichard/dms-bundle](https://packagist.org/packages/erichard/dms-bundle).


## Add the bundle to your AppKernel class

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

## Configure the bundle

```
erichard_dms:
    storage:
        path:  <path/to/your/files>
```

## Add the routing

```
# app/config/routing.yml
erichard_dms:
    resource: @ErichardDmsBundle/Resources/config/routing.yml
```
