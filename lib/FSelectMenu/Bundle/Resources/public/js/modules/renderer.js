define(function() {
    var renderer = {
        // set default implementation of translator
        translator: {
            trans: function(value) {
                return value;
            }
        },
        
        render: function(value, choices, options) {
            options = array_replace_recursive({
                    // fselectmenu element attributes
                    'attrs':  {
                        'class':  'fselectmenu-style-default',
                        'tabindex':  '0'
                    },
                    // native select element attributes
                    'nativeAttrs':  {
                        'class':  ''
                    },
                    // individual options attribs
                    'optionAttrs':  {},
                    // options wrapper element attributes
                    'optionWrapperAttrs':  {
                        'class':  ''
                    },
                    // whether to escape labels
                    'rawLabels':  false,
                    // always display this label
                    'fixedLabel':  null,
                    'disabledValues':  [],
                    'emptyLabel':  null,
                    'preferredChoices':  {},
                    'separator':  '-------------------'
                }, options
            );

            options['attrs']['class'] += " fselectmenu fselectmenu-events" + ' ' + this.valueClass(value);
            options['nativeAttrs']['class'] += " fselectmenu-native";

            if (!empty(options['nativeAttrs']['disabled'])) {
                options['attrs']['class'] += " fselectmenu-disabled";
            }

            options['optionWrapperAttrs']['class'] += " fselectmenu-options-wrapper fselectmenu-events" + ' ' + this.valueClass(value);

            if (count(options['disabledValues']) > 0) {
                var disabledValues = {};
                for (index in options['disabledValues']) {
                    disabledValues[options['disabledValues'][index]] = options['disabledValues'][index];
                }
                options['disabledValues'] = disabledValues;
            }

            // build the fselectmenu element

            var html = [];
            if (null !== options['fixedLabel']) {
                options['attrs']['data-fixedlabel'] = options['fixedLabel'];
            }

            html.push('<span');
            for(attrName in options['attrs']) {
                var attrValue = options['attrs'][attrName];

                html.push(' ' + this.escape(attrName) + '="' + this.escape(attrValue) + '"');
            }
            html.push('>');

            html.push(this.buildNativeElement(
                options['nativeAttrs'],
                options['emptyLabel'],
                options['preferredChoices'],
                choices,
                options['separator'],
                value,
                options['disabledValues']
            ));

            if (null !== options['fixedLabel']) {
                var label = options['fixedLabel'];
            } else {
                var label = this.getSelectedLabel(options['emptyLabel'], options['preferredChoices'], choices, value);
            }
            label = this.translator.trans(label);

            // fixes rendering issues when the label is empty
            label = label ? label : "\u00A0"; // &nbsp;

            html.push('<span class="fselectmenu-label-wrapper">');
            html.push('<span class="fselectmenu-label">');
            html.push(options['rawLabels'] ? label : this.escape(label));
            html.push('</span>');
            html.push('<span class="fselectmenu-icon"></span>');
            html.push('</span>');

            html.push("<span");
            for (attrName in options['optionWrapperAttrs']) {
                var attrValue = options['optionWrapperAttrs'][attrName];

                html.push(' ' + this.escape(attrName) + '="' + this.escape(attrValue) + '"');
            }
            html.push('><span class="fselectmenu-options">');

            if (null !== options['emptyLabel']) {
                html.push(this.buildChoices(
                    {'': options['emptyLabel']},
                    value,
                    options['rawLabels'], 
                    options['optionAttrs'],
                    options['disabledValues']
                ));
            }

            if (count(options['preferredChoices']) > 0) {
                html.push(this.buildChoices(
                    options['preferredChoices'],
                    value,
                    options['rawLabels'],
                    options['optionAttrs'],
                    options['disabledValues']
                ));
                if (null !== options['separator']) {
                    html.push(
                        '<span class="fselectmenu-option fselectmenu-disabled fselectmenu-separator">' +
                            (options['rawLabels'] ? options['separator'] : this.escape(options['separator'])) + '</span>'
                    );
                }
            }

            html.push(this.buildChoices(
                choices,
                value,
                options['rawLabels'],
                options['optionAttrs'],
                options['disabledValues']
            ));

            html.push('</span></span></span>');

            return html.join('');
        },

        buildChoices: function(choices, value, rawLabels, optionAttrs, disabledValues)
        {
            var html = [];

            for (choiceValue in choices) {
                var choiceLabel = choices[choiceValue];

                if (is_array(choiceLabel)) {
                    html.push('<span class="fselectmenu-optgroup">');
                    html.push('<span class="fselectmenu-optgroup-title">');
                    html.push(this.escape(this.translator.trans(choiceValue)));
                    html.push('</span>');
                    html.push(this.buildChoices(choiceLabel, value, rawLabels, optionAttrs, disabledValues));
                    html.push('</span>');

                    continue;
                }

                choiceLabel = this.translator.trans(choiceLabel);

                var attrs = [];
                if (typeof(optionAttrs[choiceValue]) != 'undefined') {
                    attrs = optionAttrs[choiceValue];
                }

                attrs['data-value'] = choiceValue;
                attrs['data-label'] = rawLabels ? choiceLabel : this.escape(choiceLabel);
                attrs['class'] = "fselectmenu-option";

                if (value === choiceValue.toString()) {
                    attrs['class'] += ' fselectmenu-selected';
                }
                if (typeof(disabledValues[choiceValue]) != 'undefined') {
                    attrs['class'] += ' fselectmenu-disabled';
                }

                attrs['class'] += ' '+this.valueClass(choiceValue);

                var opt = [];
                opt.push('<span');
                for (attrName in attrs) {
                    var attrValue = attrs[attrName];

                    opt.push(' ' + this.escape(attrName));
                    opt.push('="' + this.escape(attrValue) + '"');
                }
                opt.push('>');
                opt.push(rawLabels ? choiceLabel : this.escape(choiceLabel));
                opt.push('</span>');

                html.push(opt.join(''));
            }

            return html.join('');
        },

        getSelectedLabel: function(emptyLabel, preferredChoices, choices, value) {
            if (null !== emptyLabel && '' === value.toString()) {
                return emptyLabel;
            }
            
            var label = this.getSelectedLabelFromChoices(preferredChoices, value);
            if(false === label) {
                label = this.getSelectedLabelFromChoices(choices, value);
            }

            //use first label from preferredChoices 
            if(false === label) {
                label = this.getFirstLabelFromChoices(preferredChoices);
            }

            //use first label from choices 
            if(false === label) {
                label = this.getFirstLabelFromChoices(choices);
            }

            return label;
        },

        getSelectedLabelFromChoices: function(choices, value) {
            for (choiceValue in choices) {
                var choiceLabel = choices[choiceValue];

                if (is_array(choiceLabel)) {
                    for (cValue in choiceLabel) {
                        var cLabel = choiceLabel[cValue];

                        if (value === cValue.toString()) {
                            return cLabel;
                        }
                    }
                } else {
                    if (value === choiceValue.toString()) {
                        return choiceLabel;
                    }
                }
            }

            return false;
        },
        
        getFirstLabelFromChoices: function(choices) {
            for (choiceValue in choices) {
                var choiceLabel = choices[choiceValue];

                if (is_array(choiceLabel)) {
                    for (cValue in choiceLabel) {
                        return choiceLabel[cValue];
                    }
                } else {
                    return choiceLabel;
                }
            }
            
            return false;
        },

        buildNativeElement: function(attrs, emptyLabel, preferredChoices, choices, separator, value, disabledValues)
        {
            var html = [];

            html.push('<select');
            for(attrName in attrs) {
                var attrValue = attrs[attrName];

                html.push(' ' + this.escape(attrName) + '="' + this.escape(attrValue) + '"');
            }
            html.push('>');

            if (null !== emptyLabel) {
                html.push(this.buildNativeChoices({'': emptyLabel}, value, disabledValues));
            }

            if (count(preferredChoices) > 0) {
                html.push(this.buildNativeChoices(preferredChoices, value, disabledValues));
                if (null !== separator) {
                    html.push('<option value="" disabled="disabled">' + this.escape(separator) + '</option>');
                }
            }

            html.push(this.buildNativeChoices(choices, value, disabledValues));
            html.push('</select>');

            return html.join('');
        },

        buildNativeChoices: function(choices, value, disabledValues) {
            var html = new Array();

            for (optValue in choices) {
                var optLabel = choices[optValue];

                if (is_array(optLabel)) {
                    var title = this.translator.trans(optValue);

                    html.push('<optgroup title="' + this.escape(title) + '">');
                    html.push(this.buildNativeChoices(optLabel, value, disabledValues));
                    html.push('</optgroup>');

                    continue;
                }

                var label = this.translator.trans(optLabel);

                var selected = value === optValue.toString();
                var disabled = typeof(disabledValues[optValue]) != 'undefined';

                html.push('<option' +
                    (selected ? ' selected="selected"' : '') +
                    (disabled ? ' disabled="disabled"' : '') +
                    ' value="' + this.escape(optValue) + '"' +
                    ' class="' + this.escape(this.valueClass(optValue)) + '"' +
                    '>' + this.escape(label) + '</option>');
            }

            return html.join('');
        },

        // http://mathiasbynens.be/notes/html5-id-class
        valueClass: function(value) {
            return 'fselectmenu-value-' + value.replace(/\s+/g, '-');
        },
        escape: function(str) {
            var ENT_QUOTES = 3;

            return htmlspecialchars(str, ENT_QUOTES, "utf-8");
        },
    };

    //functions from: http://phpjs.org

    function array_replace_recursive (arr) {
        // +   original by: Brett Zamir (http://brett-zamir.me)
        // *     example 1: array_replace_recursive({'citrus' : ["orange"], 'berries' : ["blackberry", "raspberry"]}, {'citrus' : ['pineapple'], 'berries' : ['blueberry']});
        // *     returns 1: {citrus : ['pineapple'], berries : ['blueberry', 'raspberry']}

        var retObj = {},
            i = 0,
            p = '',
            argl = arguments.length;

        if (argl < 2) {
            throw new Error('There should be at least 2 arguments passed to array_replace_recursive()');
        }

        // Although docs state that the arguments are passed in by reference, it seems they are not altered, but rather the copy that is returned (just guessing), so we make a copy here, instead of acting on arr itself
        for (p in arr) {
            retObj[p] = arr[p];
        }

        for (i = 1; i < argl; i++) {
            for (p in arguments[i]) {
                if (retObj[p] && typeof retObj[p] === 'object') {
                    retObj[p] = array_replace_recursive(retObj[p], arguments[i][p]);
                } else {
                    retObj[p] = arguments[i][p];
                }
            }
        }
        return retObj;
    }

    function htmlspecialchars (string, quote_style, charset, double_encode) {
        // Convert special characters to HTML entities  
        // 
        // version: 1109.2015
        // discuss at: http://phpjs.org/functions/htmlspecialchars
        // +   original by: Mirek Slugen
        // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +   bugfixed by: Nathan
        // +   bugfixed by: Arno
        // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
        // +      input by: Ratheous
        // +      input by: Mailfaker (http://www.weedem.fr/)
        // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
        // +      input by: felix
        // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
        // %        note 1: charset argument not supported
        // *     example 1: htmlspecialchars("<a href='test'>Test</a>", 'ENT_QUOTES');
        // *     returns 1: '&lt;a href=&#039;test&#039;&gt;Test&lt;/a&gt;'
        // *     example 2: htmlspecialchars("ab\"c'd", ['ENT_NOQUOTES', 'ENT_QUOTES']);
        // *     returns 2: 'ab"c&#039;d'
        // *     example 3: htmlspecialchars("my "&entity;" is still here", null, null, false);
        // *     returns 3: 'my &quot;&entity;&quot; is still here'
        var optTemp = 0,
            i = 0,
            noquotes = false;
        if (typeof quote_style === 'undefined' || quote_style === null) {
            quote_style = 2;
        }
        string = string.toString();
        if (double_encode !== false) { // Put this first to avoid double-encoding
            string = string.replace(/&/g, '&amp;');
        }
        string = string.replace(/</g, '&lt;').replace(/>/g, '&gt;');

        var OPTS = {
            'ENT_NOQUOTES': 0,
            'ENT_HTML_QUOTE_SINGLE': 1,
            'ENT_HTML_QUOTE_DOUBLE': 2,
            'ENT_COMPAT': 2,
            'ENT_QUOTES': 3,
            'ENT_IGNORE': 4
        };
        if (quote_style === 0) {
            noquotes = true;
        }
        if (typeof quote_style !== 'number') { // Allow for a single string or an array of string flags
            quote_style = [].concat(quote_style);
            for (i = 0; i < quote_style.length; i++) {
                // Resolve string input to bitwise e.g. 'ENT_IGNORE' becomes 4
                if (OPTS[quote_style[i]] === 0) {
                    noquotes = true;
                }
                else if (OPTS[quote_style[i]]) {
                    optTemp = optTemp | OPTS[quote_style[i]];
                }
            }
            quote_style = optTemp;
        }
        if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE) {
            string = string.replace(/'/g, '&#039;');
        }
        if (!noquotes) {
            string = string.replace(/"/g, '&quot;');
        }

        return string;
    }

    function is_array (mixed_var) {
        // http://kevin.vanzonneveld.net
        // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +   improved by: Legaev Andrey
        // +   bugfixed by: Cord
        // +   bugfixed by: Manish
        // +   improved by: Onno Marsman
        // +   improved by: Brett Zamir (http://brett-zamir.me)
        // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
        // +   improved by: Nathan Sepulveda
        // +   improved by: Brett Zamir (http://brett-zamir.me)
        // %        note 1: In php.js, javascript objects are like php associative arrays, thus JavaScript objects will also
        // %        note 1: return true in this function (except for objects which inherit properties, being thus used as objects),
        // %        note 1: unless you do ini_set('phpjs.objectsAsArrays', 0), in which case only genuine JavaScript arrays
        // %        note 1: will return true
        // *     example 1: is_array(['Kevin', 'van', 'Zonneveld']);
        // *     returns 1: true
        // *     example 2: is_array('Kevin van Zonneveld');
        // *     returns 2: false
        // *     example 3: is_array({0: 'Kevin', 1: 'van', 2: 'Zonneveld'});
        // *     returns 3: true
        // *     example 4: is_array(function tmp_a(){this.name = 'Kevin'});
        // *     returns 4: false
        var ini,
            _getFuncName = function (fn) {
                var name = (/\W*function\s+([\w\$]+)\s*\(/).exec(fn);
                if (!name) {
                    return '(Anonymous)';
                }
                return name[1];
            },
            _isArray = function (mixed_var) {
                // return Object.prototype.toString.call(mixed_var) === '[object Array]';
                // The above works, but let's do the even more stringent approach: (since Object.prototype.toString could be overridden)
                // Null, Not an object, no length property so couldn't be an Array (or String)
                if (!mixed_var || typeof mixed_var !== 'object' || typeof mixed_var.length !== 'number') {
                    return false;
                }
                var len = mixed_var.length;
                mixed_var[mixed_var.length] = 'bogus';
                // The only way I can think of to get around this (or where there would be trouble) would be to have an object defined 
                // with a custom "length" getter which changed behavior on each call (or a setter to mess up the following below) or a custom 
                // setter for numeric properties, but even that would need to listen for specific indexes; but there should be no false negatives 
                // and such a false positive would need to rely on later JavaScript innovations like __defineSetter__
                if (len !== mixed_var.length) { // We know it's an array since length auto-changed with the addition of a 
                    // numeric property at its length end, so safely get rid of our bogus element
                    mixed_var.length -= 1;
                    return true;
                }
                // Get rid of the property we added onto a non-array object; only possible 
                // side-effect is if the user adds back the property later, it will iterate 
                // this property in the older order placement in IE (an order which should not 
                // be depended on anyways)
                delete mixed_var[mixed_var.length];
                return false;
            };

        if (!mixed_var || typeof mixed_var !== 'object') {
            return false;
        }

        // BEGIN REDUNDANT
        this.php_js = this.php_js || {};
        this.php_js.ini = this.php_js.ini || {};
        // END REDUNDANT

        ini = this.php_js.ini['phpjs.objectsAsArrays'];

        return _isArray(mixed_var) ||
            // Allow returning true unless user has called
            // ini_set('phpjs.objectsAsArrays', 0) to disallow objects as arrays
            ((!ini || ( // if it's not set to 0 and it's not 'off', check for objects as arrays
                (parseInt(ini.local_value, 10) !== 0 && (!ini.local_value.toLowerCase || ini.local_value.toLowerCase() !== 'off')))
                ) && (
                Object.prototype.toString.call(mixed_var) === '[object Object]' && _getFuncName(mixed_var.constructor) === 'Object' // Most likely a literal and intended as assoc. array
                ));
    }

    function count (mixed_var, mode) {
        // http://kevin.vanzonneveld.net
        // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +      input by: Waldo Malqui Silva
        // +   bugfixed by: Soren Hansen
        // +      input by: merabi
        // +   improved by: Brett Zamir (http://brett-zamir.me)
        // +   bugfixed by: Olivier Louvignes (http://mg-crea.com/)
        // *     example 1: count([[0,0],[0,-4]], 'COUNT_RECURSIVE');
        // *     returns 1: 6
        // *     example 2: count({'one' : [1,2,3,4,5]}, 'COUNT_RECURSIVE');
        // *     returns 2: 6
        var key, cnt = 0;

        if (mixed_var === null || typeof mixed_var === 'undefined') {
            return 0;
        } else if (mixed_var.constructor !== Array && mixed_var.constructor !== Object) {
            return 1;
        }

        if (mode === 'COUNT_RECURSIVE') {
            mode = 1;
        }
        if (mode != 1) {
            mode = 0;
        }

        for (key in mixed_var) {
            if (mixed_var.hasOwnProperty(key)) {
                cnt++;
                if (mode == 1 && mixed_var[key] && (mixed_var[key].constructor === Array || mixed_var[key].constructor === Object)) {
                    cnt += this.count(mixed_var[key], 1);
                }
            }
        }

        return cnt;
    }

    function empty (mixed_var) {
        // http://kevin.vanzonneveld.net
        // +   original by: Philippe Baumann
        // +      input by: Onno Marsman
        // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +      input by: LH
        // +   improved by: Onno Marsman
        // +   improved by: Francesco
        // +   improved by: Marc Jansen
        // +   input by: Stoyan Kyosev (http://www.svest.org/)
        // *     example 1: empty(null);
        // *     returns 1: true
        // *     example 2: empty(undefined);
        // *     returns 2: true
        // *     example 3: empty([]);
        // *     returns 3: true
        // *     example 4: empty({});
        // *     returns 4: true
        // *     example 5: empty({'aFunc' : function () { alert('humpty'); } });
        // *     returns 5: false
        var key;

        if (mixed_var === "" || mixed_var === 0 || mixed_var === "0" || mixed_var === null || mixed_var === false || typeof mixed_var === 'undefined') {
            return true;
        }

        if (typeof mixed_var == 'object') {
            for (key in mixed_var) {
                return false;
            }
            return true;
        }

        return false;
    }

    function array_combine (keys, values) {
        // http://kevin.vanzonneveld.net
        // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +   improved by: Brett Zamir (http://brett-zamir.me)
        // *     example 1: array_combine([0,1,2], ['kevin','van','zonneveld']);
        // *     returns 1: {0: 'kevin', 1: 'van', 2: 'zonneveld'}
        var new_array = {},
            keycount = keys && keys.length,
            i = 0;

        // input sanitation
        if (typeof keys !== 'object' || typeof values !== 'object' || // Only accept arrays or array-like objects
            typeof keycount !== 'number' || typeof values.length !== 'number' || !keycount) { // Require arrays to have a count
            return false;
        }

        // number of elements does not match
        if (keycount != values.length) {
            return false;
        }

        for (i = 0; i < keycount; i++) {
            new_array[keys[i]] = values[i];
        }

        return new_array;
    }
    
    return renderer;
});