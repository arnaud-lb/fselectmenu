var fs = require('fs');
var vm = require('vm');

var rendererValue = "%s";
var rendererChoices = JSON.parse('%s');
var rendererOptions = JSON.parse('%s');
var translator = (function(data) {
    return {
        trans: function(key) {
            return typeof(data[key]) == 'undefined' ? key : data[key];
        }
    };
})(JSON.parse('%s'));
var rendererFileContent = fs.readFileSync("%s", 'utf8');

var sandbox = {
    console: console,

    renderer: null,

    rendererValue: rendererValue,
    rendererChoices: rendererChoices,
    rendererOptions: rendererOptions,
    translator: translator
};

// window
sandbox.window = sandbox;

// create a context for the vm using the sandbox data
var context = vm.createContext(sandbox);

// load fake requirejs
vm.runInContext('define = function(func) { renderer = func(); };', context, 'requirejs.js');

// load renderer code
vm.runInContext(rendererFileContent, context, 'renderer.js');

vm.runInContext('renderer.translator = translator', context, 'translator.js');

//run renderer.render()
vm.runInContext('console.log(renderer.render(rendererValue, rendererChoices, rendererOptions));', context, 'test.js');