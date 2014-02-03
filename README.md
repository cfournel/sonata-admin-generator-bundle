sonata-admin-generator-bundle
=============================

A automatic Sonata Admin generator

It provide you some command lines to generator admin class for your entities ...

Installation
------------

VObject requires PHP 5.3, and should be installed using composer.
The general composer instructions can be found on the [composer website](http://getcomposer.org/doc/00-intro.md composer website).

After that, just declare the vobject dependency as follows:

```
"require" : {
    "huitiemesens/sonata-admin-generator-bundle" : "dev-master"
}
```

Then, run `composer.phar update` and you should be good.


Finally add the following code to your AppKernel.php

```
new huitiemesens\SonataAdminGeneratorBundle\huitiemesensSonataAdminGeneratorBundle(),
```


Usage
-----

Will generate every entities inside a defined bundle

```
app/console admin:generate AppBundle
```

Then a prompt will ask to confirm each of entities found to be generated.