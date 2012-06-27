define(function() {
    var renderer = {
        // set default implementation of translator
        translator: {
            trans: function(value) {
                return value;
            }
        },
        
        render: function(value, choices, options) {
            options = extendRecursive({
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

            if (options['nativeAttrs']['disabled']) {
                options['attrs']['class'] += " fselectmenu-disabled";
            }

            options['optionWrapperAttrs']['class'] += " fselectmenu-options-wrapper fselectmenu-events" + ' ' + this.valueClass(value);

            if (options['disabledValues'].length > 0) {
                var disabledValues = {};
                for (var i = 0, l = options['disabledValues'].length; i < l; ++i) {
                    disabledValues[options['disabledValues'][i]] = options['disabledValues'][i];
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

            if (objectHasProperties(options['preferredChoices'])) {
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

                if (typeof choiceLabel == 'object') {
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

                if (typeof choiceLabel == 'object') {
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

                if (typeof choiceLabel == 'object') {
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

            if (objectHasProperties(preferredChoices)) {
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

                if (typeof optLabel == 'object') {
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

            var map = {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;"
            };

            return String(str).replace(/[&<>"']/g, function (m) {
                return map[m];
            });
        },
    };

    function objectHasProperties(object) {
        for (var key in object) {
            if (object.hasOwnProperty(key)) {
                return true;
            }
        }
        return false;
    }

    function extendRecursive(a, b) {
        for (var key in b) {
            if (!b.hasOwnProperty(key)) {
                continue;
            }
            if (a.hasOwnProperty(key) && typeof a[key] == 'object' && typeof b[key] == 'object') {
                extendRecursive(a[key], b[key]);
            } else {
                a[key] = b[key];
            }
        }
        return a;
    }

    var htmlMap = 

    function escapeHtml() {
    }
    
    return renderer;
});
