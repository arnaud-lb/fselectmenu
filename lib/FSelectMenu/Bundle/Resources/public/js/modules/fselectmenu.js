
/**
 * - init() has to be called only one time for the life of the page;
 *   newly inserted select menus will automatically be handled without
 *   having to call init() again.
 * - init() is very lightweight, it only set up a few event handlers
 * - script can work directly with the native element for most things (e.g.
 *   getting and changing the value)
 */
define(function() {

    var namespace = 'fselectmenu';

    var keyCode = {
        UP: 38
        , DOWN: 40
        , SPACE: 32
        , ENTER: 13
        , TAB: 9
        , ESCAPE: 27
    };

    function valueClass(value) {
        return namespace + '-value-' + String(value).replace(/\s+/g, '-');
    }

    function Menu($root) {
        this._root = $root;
        this._root.data(namespace, this);
        this._options = $root.find('.'+namespace+'-options-wrapper');
        this._options.data(namespace, this);
        this._label = this._root.find('.'+namespace+'-label');
        this._input = this._root.find('.'+namespace+'-native');

        this._searchPattern = '';
        this._searchResults = null;
        this._searchResultActive = 0;
    };
    Menu.prototype = {
        selectOption: function($elem) {
            // .data() tries to convert the value to number
            this._input.val($elem.attr('data-value')).change();
        }
        , disabled: function() {
            if (this._input.prop('disabled')) {
                this._root.addClass(namespace+'-disabled');
            } else {
                this._root.removeClass(namespace+'-disabled');
            }
            return this._input.prop('disabled');
        }
        , readonly: function() {
            if (this._input.prop('readonly')) {
                this._root.addClass(namespace+'-readonly');
            } else {
                this._root.removeClass(namespace+'-readonly');
            }
            return this._input.prop('readonly');
        }
        , active: function() {
            return this._options.find('.'+namespace+'-active');
        }
        , activate: function($elem) {
            this._options
                .find('.'+namespace+'-active')
                .removeClass(namespace+'-active')
                ;
            $elem.addClass(namespace+'-active');
        }
        , activateNext: function() {
            var $next = this.active()
                .removeClass(namespace+'-active')
                .next();
            if (!$next.length) {
                this._options.find('.'+namespace+'-option:first')
                    .addClass(namespace+'-active');
                this._options.find('.'+namespace+'-options')
                    .get(0).scrollTop = 0;
            } else {
                $next.addClass(namespace+'-active');
                this._options.find('.'+namespace+'-options')
                    .get(0).scrollTop += $next.outerHeight();
            }
        }
        , activatePrev: function() {
            var $prev = this.active()
                .removeClass(namespace+'-active')
                .prev();
            if (!$prev.length) {
                this._options.find('.'+namespace+'-option:last')
                    .addClass(namespace+'-active');
                this._options.find('.'+namespace+'-options')
                    .get(0).scrollTop = this._options
                        .find('.'+namespace+'-options')[0].scrollHeight;
            } else {
                $prev.addClass(namespace+'-active');
                this._options.find('.'+namespace+'-options')
                    .get(0).scrollTop -= $prev.outerHeight();
            }
        }
        , elemWithValue: function(value) {
            value = value + '';
            return this._options.find('.'+namespace+'-option')
                .filter(function() {
                    // .data() tries to convert the value to number
                    return ($(this).attr('data-value')+'') == value;
                });
        }
        , close: function() {
            $('body').unbind('mousedown.'+namespace+'-opened blur.'+namespace+'-opened');
            this._options.removeClass(namespace+'-opened '+namespace+'-opened2');
            this.resetSearch();
        }
        , open: function() {
            var that = this;

            if (this.disabled()) return;
            if (this.readonly()) return;

            $('body').bind('mousedown.'+namespace+'-opened', function(event) {
                var $elem = $(event.target);
                if (instance($elem) != that) {
                    that.close();
                }
            });
            $(window).bind('blur.'+namespace+'-opened', function(event) {
                // that.close();
            });

            this.activate(this.elemWithValue(this._input.val()));

            var root = this._root
                , offset = root.offset()
                , height = root.outerHeight()
                , width = root.outerWidth();

            this._options.css({
                'minWidth': width+'px'
                , 'top': (offset['top']+height)+'px'
                , left: offset.left+'px'
            });

            var optionsHeight = this._options.outerHeight();

            if (offset['top']+height+optionsHeight > $('body').outerHeight()) {
                this._options.css({
                    'top': (offset['top']-optionsHeight)+'px'
                });
            }

            this._options.appendTo('body').addClass(namespace+'-opened');
            setTimeout($.proxy(function() {
                this._options.addClass(namespace+'-opened2');
            }, this), 100);
            this.ensureActiveVisible();
        }
        , ensureActiveVisible: function() {
            var $active = this.active();
            if (!$active.length) return;
            var t = $active.position().top;
            var tbottom = t + $active.outerHeight();
            if (t < 0 || tbottom > this._options.height()) {
                var mid = this._options.height()/2;
                this._options.find('.'+namespace+'-options')
                    .get(0).scrollTop += (t+tbottom)/2 - mid;
            }
        }
        , opened: function() {
            return this._options.hasClass(namespace+'-opened');
        }
        , toggle: function() {
            if (this.opened()) {
                this.close();
            } else {
                this.open();
            }
        }
        , keydown: function(event, $that) {

            if (this.disabled()) return;
            if (this.readonly()) return;

            switch(event.keyCode) {
                case keyCode.UP:
                    this.activatePrev();
                    this.selectOption(this.active());
                    this._root.focus();
                    return false;
                case keyCode.DOWN:
                    this.activateNext();
                    this.selectOption(this.active());
                    this._root.focus();
                    return false;
                case keyCode.SPACE:
                case keyCode.ENTER:
                case keyCode.TAB:
                case keyCode.ESCAPE:
                    if (this.opened()) {
                        if (event.keyCode != 27) {
                            this.selectOption(this.active());
                        }
                        this.close();
                    } else if (event.keyCode != keyCode.TAB) {
                        this.open();
                    }
                    if (event.keyCode != keyCode.TAB) {
                        this._root.focus();
                        return false;
                    }
                default:
                    if (event.keyCode > "!".charCodeAt(0)
                            && event.keyCode < 127)
                    {
                        this.search(event);
                    }
                    break;
            }
        }
        , resetSearch: function() {
            this._searchPattern = '';
            this._searchResults = null;
        }
        , search: function(event) {

            var letter = String.fromCharCode(event.keyCode).toLowerCase();
            var pattern = this._searchPattern += letter;

            this._searchResults = this._options.find('.'+namespace+'-option')
                .filter(function() {
                    var label = $(this).attr('data-label')
                        .replace(/[^\x21-\x7E]/g, '')
                        .toLowerCase();
                    if (label.indexOf(pattern) === 0) {
                        return true;
                    }
                });

            if (!this._searchResults.length) {
                this._searchPattern = '';
                return;
            }

            this.activate($(this._searchResults[0]));
            this.selectOption($(this._searchResults[0]));
            this.ensureActiveVisible();
        }
        , clickOption: function(event, $that) {
            if ($that.hasClass(namespace+'-disabled')) {
                return;
            }
            this.selectOption($that);
            this.close();
            this._root.focus();
        }
        , mouseenterOption: function(event, $that) {
            this.activate($that);
            this._root.focus();
        }
        , click: function(event, $that) {
            event.preventDefault();
            this.toggle();
            this._root.focus();
        }
        , change: function(event, $that) {
            var hasFocus = this._root.is(':focus');
            var val = $that.val();
            var $elem = this.elemWithValue(val);
            // .data() tries to convert the value to number
            this._label.html(this._root.attr('data-fixedlabel') || $elem.attr('data-label'));

            // set value class
            this._root.toggleClass(function(index, cls) {
                var classes = cls.split(/\s+/);
                var toggle = [];
                var re = new RegExp('^'+namespace+'-value-');
                for (var i = 0, l = classes.length; i < l; ++i) {
                    if (re.test(classes[i])) {
                        toggle.push(classes[i]);
                    }
                }
                return toggle.join(' ');
            }).addClass(valueClass(val));

            this.activate($elem);
            if (hasFocus) {
                this._root.focus();
            }
            this.disabled();
            this.readonly();
        }
        , disable: function(event) {
            this._input.prop('disabled', true);
            this.disabled();
        }
        , enable: function(event) {
            this._input.prop('disabled', null);
            this.disabled();
        }
        , setReadonly: function(event, elem, readonly) {
            this._input.prop('readonly', readonly);
            this.readonly();
        }
    };

    function instance($elem) {
        var menu;
        $elem = $elem.closest('.'+namespace+'-events').andSelf().slice(0,1);
        if (menu = $elem.data(namespace)) {
            return menu;
        }
        return new Menu($elem);
    }

    function delegate(selector, event, fun) {

        if (selector) {
            selector = ' .' + namespace + '-' + selector;
        } else {
            selector = '';
        }
        selector = '.' + namespace + '-events' + selector;

        $(document).delegate(selector, event, function(event) {
            var menu = instance($(this));
            if (!menu) return;
            var args = Array.prototype.slice.call(arguments, 1);
            args.unshift(event, $(this));
            return Menu.prototype[fun].apply(menu, args);
        });
    }

    function init(options) {

        options = options || {};

        if (options.namespace) {
            namespace = options.namespace;
        }

        delegate(null,              'keydown',      'keydown');
        delegate('option',          'mouseup',      'clickOption');
        delegate('option',          'mouseenter',   'mouseenterOption');
        delegate('label-wrapper',   'mousedown',    'click');
        delegate('native',          'change',       'change');
        delegate('native',          'disable',      'disable');
        delegate('native',          'enable',       'enable');
        delegate('native',          'readonly',     'setReadonly');
    }

    return {
        init: init
    };
});
