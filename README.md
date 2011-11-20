FSelectMenu
===========

FSelectMenu is a Fast and non-intrusive HTML select menu.

## Features:

- **Fast**: rendering is done on the server side: no DOM manipulation on the client side

- **Non intrusive**: Once FSelectMenu is initialized you can forget it: every select menu will work, whether they were present during initialization or added later. (FSelectMenu uses event delegation. The HTML code is generated on the server; all that is left to FSelectMenu is to listen for events bubbling to the document element.).

- **Non intrusive**: Works out of the box with existing scripts
   - Scripts don't have to know anything about FSelectMenu for simple things like listening for events, getting and changing the value, etc.
   - Scripts interact with the native select element directly

Usage
-----

Load and init the FSelectMenu javascript module:

``` javascript
require(['fselectmenu/fselectmenu'], function(FSelectMenu) {
    FSelectMenu.init();
});
```

That's all.

If you add new select menus on the document after that, you don't have to call `.init()` again.

Just trigger the `change` event on the native select element when programmatically changing its value.

Install
-------

```
$ git submodule add git://github.com/arnaud-lb/fselectmenu.git vendor/fselectmenu
```

### Plain PHP

You can render a FSelectMenu with the FSelectMenu\Renderer class:

``` php
<?php

$renderer = new FSelectMenu\Renderer;
echo $renderer->render($value, $choices, $options);
```

 - $value is the value of the selected choice
 - $choices is an array of value => label choices (with nested arrays, for optgroups)
 - $options is an array with the following keys:
   - attrs: fselectmenu element attributes (e.g. id, class, ...)
   - nativeAttrs: native select element attributes (e.g. id, name)
   - optionAttrs: choice elements attributes (array of value => attributes)
   - optionWrapperAttrs: choice elements wrapper attributes
   - rawLabels: whether to escape labels
   - fixedLabel: a label that will always be displayed instead of the selected label

Example:

``` php
<?php
echo $renderer->render('x', array('x' => 'Foo', 'y' => 'Bar'), array('nativeAttrs' => array('name' => 'foo')));
```

### Twig

Register the extension:

``` php
<?php
$extension = new FSelectMenu\Twig\Extension;
$twigEnvironment->addExtension($extension);
```

The extension exposes the `fselectmenu` method:

    fselectmenu(value, choices, options)

See Plain PHP above for a description of the parameters.

Example:

``` jinja
{{ fselectmenu('x', {'x': 'Foo', 'y': 'Bar'}, {'nativeAttrs':{'name': 'foo'}}) }}
```

### Symfony2

#### Add the FSelectMenu namespace to your autoloader

``` php
<?php
// app/autoload.php

$loader->registerNamespaces(array(
    'FSelectMenu' => __DIR__.'/../vendor/fselectmenu/lib',
    // your other namespaces
);
```

#### Add FSelectMenuBundle to your application kernel

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    return array(
        // ...
        new FSelectMenu\Bundle\FSelectMenuBundle(),
    );
}
```

#### Overload the `choice_widget` block in your form theme:

``` jinja
{% use "FSelectMenuBundle::fselectmenu.html.twig" %}

{% block choice_widget %}
{% spaceless %}
    {% if expanded %}
        {% for child in form %}
            {{ form_widget(child) }}
        {% endfor %}
    {% else %}
        {% if multiple %}
            {{ parent() }}
        {% else %}
            {{ block('fselectmenu_choice_widget') }}
        {% endif %}
    {% endif %}
{% endspaceless %}
{% endblock choice_widget %}
```

### ZendFramework

#### Register view helper path:

``` ini
# application.ini
resources.view.helperPath.FSelectMenu_Zend_View_Helper = APPLICATION_PATH "/../vendor/fselectmenu/lib/FSelectMenu/Zend/View/Helper"
```

#### Subclass Zend_Form_Element_Select:

```
<?php
class App_Form_Element_FSelectMenu extends Zend_Form_Element_Select
{
    public $helper = 'formFSelectMenu';
}
```

### Styling

FSelectMenu comes with a minimal (behavior only) stylesheet at `lib/FSelectMenu/Bundle/Resources/sass/_fselectmenu.sass`.