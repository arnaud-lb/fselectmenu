
FSelectMenu
===========

FSelectMenu is a Fast and non-intrusive HTML select menu.

Features:

 - Fast: rendering is done on the server side
 - Non intrusive: Works out of the box with existing scripts
   - Scripts don't have to know anything about FSelectMenu for simple things like listening for events, getting and changing the value, etc.
   - Scripts interact with the native select element directly
   - Works well with Ajax (any FSelectMenu added on the document after page is loaded just works, without calling any FSelectMenu method)

Usage
-----

Load and init the FSelectMenu javascript module:

``` javascript
require(['fselectmenu/fselectmenu'], function(FSelectMenu) {
    FSelectMenu.init();
});

That's all.

If you add new select menus on the document after that, you don't have to call `.init()` again.

Just trigger the `change` event on the native select element when programmatically changing its value.

Install
-------

Symfony
```````

Register the Bundle

TODO

Zend
````

Register view helper path:

    FSelectMenu_Zend_View_Helper => vendor/fselectmenu/lib/FSelectMenu/Zend/View/Helper

TODO
