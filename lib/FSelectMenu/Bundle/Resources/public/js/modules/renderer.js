
var fun = function() {
    var renderer = {
        // set default implementation of translator
        translator: {
            trans: function(value) {
                return value;
            }
        },

        render2: function(value, choices, options) {

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
                    // options wrapper element attributes
                    'optionWrapperAttrs':  {
                        'class':  ''
                    },
                    // whether to escape labels
                    'rawLabels':  false,
                    // always display this label
                    'fixedLabel':  null,
                    'emptyLabel':  null,
                    'preferredChoices':  [],
                    'separator':  '-------------------'
                }, options
            );

            value = String(value);

            options['attrs']['class'] += " fselectmenu fselectmenu-events" + ' ' + this.valueClass(value);
            options['nativeAttrs']['class'] += " fselectmenu-native";

            if (options['nativeAttrs']['disabled']) {
                options['attrs']['class'] += " fselectmenu-disabled";
            }

            options['optionWrapperAttrs']['class'] += " fselectmenu-options-wrapper fselectmenu-events" + ' ' + this.valueClass(value);

            choices = fixupChoiceList(choices);
            options['preferredChoices'] = fixupChoiceList(options['preferredChoices']);

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
                value
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

                var list = fixupChoiceList([
                    {
                        value: '',
                        label: options['emptyLabel']
                    }
                ]);

                html.push(this.buildChoices(
                    list,
                    value,
                    options['rawLabels']
                ));
            }

            if (objectHasProperties(options['preferredChoices'])) {
                html.push(this.buildChoices(
                    options['preferredChoices'],
                    value,
                    options['rawLabels']
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
                options['rawLabels']
            ));

            html.push('</span></span></span>');

            return html.join('');
        },
        
        render: function(value, choices, options) {

            options = extendRecursive({
                    // individual options attribs
                    'optionAttrs':  {},
                    'disabledValues':  [],
                    'preferredChoices':  {},
                }, options
            );

            choices = makeChoiceList(
                choices
                , options['optionAttrs']
                , options['disabledValues']
            );

            options['preferredChoices'] = makeChoiceList(
                options['preferredChoices']
                , options['optionAttrs']
                , options['disabledValues']
            );

            delete options['optionAttrs'];
            delete options['disabledValues'];

            return this.render2(value, choices, options);
        },

        buildChoices: function(choices, value, rawLabels) {
            var html = [];

            for (var i = 0, l = choices.length; i < l; ++i) {

                var choice = choices[i];

                if (choice.choices) {
                    html.push('<span class="fselectmenu-optgroup">');
                    html.push('<span class="fselectmenu-optgroup-title">');
                    html.push(this.escape(this.translator.trans(choice.label)));
                    html.push('</span>');
                    html.push(this.buildChoices(choice.choices, value, rawLabels));
                    html.push('</span>');

                    continue;
                }

                var choiceValue = choice.value;
                var choiceLabel = choice.label;

                var choiceLabel = this.translator.trans(choiceLabel);

                var attrs = choice.attrs;

                if (attrs) {
                attrs['data-value'] = choiceValue;
                attrs['data-label'] = rawLabels ? choiceLabel : this.escape(choiceLabel);
                attrs['class'] = "fselectmenu-option";

                if (value === choiceValue) {
                    attrs['class'] += ' fselectmenu-selected';
                }
                if (choice.disabled) {
                    attrs['class'] += ' fselectmenu-disabled';
                }

                attrs['class'] += ' '+this.valueClass(choiceValue);

                var opt = [];
                opt.push('<span');
                for (attrName in attrs) {
                    if (!attrs.hasOwnProperty(attrName)) {
                        continue;
                    }

                    var attrValue = attrs[attrName];

                    opt.push(' ' + this.escape(attrName));
                    opt.push('="' + this.escape(attrValue) + '"');
                }
                opt.push('>');
                opt.push(rawLabels ? choiceLabel : this.escape(choiceLabel));
                opt.push('</span>');

                html.push(opt.join(''));
                }
            }

            return html.join('');
        },

        getSelectedLabel: function(emptyLabel, preferredChoices, choices, value) {

            if (null !== emptyLabel && '' === value) {
                return emptyLabel;
            }

            var result = this.getSelectedLabelFromChoices(preferredChoices, value);

            if (!result.found) {
                result = this.getSelectedLabelFromChoices(choices, value, result.label);
            }

            return result.label;
        },

        getSelectedLabelFromChoices: function (choices, value, selectedLabel) {

            if (typeof selectedLabel === 'undefined') {
                selectedLabel = null;
            }

            for (var i = 0, l = choices.length; i < l; ++i) {
                var choice = choices[i];
                if (choice.choices) {
                    for (var j = 0, jl = choice.choices.length; j < jl; ++j) {
                        var cChoice = choice.choices[j];
                        var cLabel = cChoice.label;
                        var cValue = cChoice.value;
                        if (null === selectedLabel) {
                            selectedLabel = cLabel;
                        }
                        if (value === cValue) {
                            return {
                                label: cLabel,
                                found: true
                            };
                        }
                    }
                } else {

                    var choiceValue = choice['value'];
                    var choiceLabel = choice['label'];

                    if (null === selectedLabel) {
                        selectedLabel = choiceLabel;
                    }
                    if (value === choiceValue) {
                        return {
                            label: choiceLabel,
                            found: true
                        };
                    }
                }
            }

            return {
                label: selectedLabel,
                found: false
            };
        },

        buildNativeElement: function(attrs, emptyLabel, preferredChoices, choices, separator, value)
        {
            var html = [];

            html.push('<select');
            for(attrName in attrs) {
                if (!attrs.hasOwnProperty(attrName)) {
                    continue;
                }
                var attrValue = attrs[attrName];

                html.push(' ' + this.escape(attrName) + '="' + this.escape(attrValue) + '"');
            }
            html.push('>');

            if (null !== emptyLabel) {
                var list = fixupChoiceList([
                    {
                        value: '',
                        label: emptyLabel
                    }
                ]);
                html.push(this.buildNativeChoices(list, value));
            }

            if (objectHasProperties(preferredChoices)) {
                html.push(this.buildNativeChoices(preferredChoices, value));
                if (null !== separator) {
                    html.push('<option value="" disabled="disabled">' + this.escape(separator) + '</option>');
                }
            }

            html.push(this.buildNativeChoices(choices, value));
            html.push('</select>');

            return html.join('');
        },

        buildNativeChoices: function(choices, value) {

            var html = [];

            for (var i = 0, l = choices.length; i < l; ++i) {

                var choice = choices[i];

                if (choice.choices) {
                    var title = this.translator.trans(choice.label);

                    html.push('<optgroup title="' + this.escape(title) + '">');
                    html.push(this.buildNativeChoices(choice.choices, value));
                    html.push('</optgroup>');

                    continue;
                }

                var optLabel = choice.label;
                var optValue = choice.value;

                var label = this.translator.trans(optLabel);

                var selected = value === optValue;
                var disabled = choice.disabled;

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

    function extend(a, b) {
        for (var k in b) {
            if (!b.hasOwnProperty(k)) {
                continue;
            }
            a[k] = b[k];
        }
        return a;
    }

    function makeChoiceList(choices, optionAttrs, disabledValues) {

        var list = [];

        var origDisabledValues = disabledValues;
        disabledValues = {};

        for (var i = 0, l = origDisabledValues.length; i < l; ++i) {
            disabledValues[origDisabledValues[i]] = origDisabledValues[i];
        }

        for (var value in choices) {
            if (!choices.hasOwnProperty(value)) {
                continue;
            }

            var label = choices[value];

            if (typeof label == 'object') {
                list.push({
                    choices: makeChoiceList(label, optionAttrs, disabledValues),
                    label: value
                });
            } else {
                var item = {
                    value: value,
                    label: label
                };
                if (optionAttrs.hasOwnProperty(value)) {
                    item.attrs = optionAttrs[value];
                }
                if (disabledValues.hasOwnProperty(value)) {
                    item.disabled = disabledValues[value];
                }
                list.push(item);
            }
        }

        return list;
    }

    function fixupChoiceList(origList) {

        var list = [];

        var defaults = {
            choices: null,
            label: null,
            value: null,
            attrs: {},
            disabled: false
        };

        for (var i = 0, l = origList.length; i < l; ++i) {

            var item = extend(extend({}, defaults), origList[i]);

            item['label'] = item['label'] ? String(item['label']) : '';

            if (item['choices']) {
                item['choices'] = fixupChoiceList(item['choices']);
            } else {
                item['value'] = item['value'] ? String(item['value']) : '';
            }

            list.push(item);
        }

        return list;
    }

    return renderer;
};

if (typeof define === 'undefined') {
    var e = typeof exports !== 'undefiend' ? exports : window;
    var module = fun();
    for (var k in module) {
        if (!module.hasOwnProperty(k)) {
            continue;
        }
        e[k] = module[k];
    }
} else {
    define(fun);
}

