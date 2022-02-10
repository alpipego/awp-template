(() => {
    if (typeof window.wp !== 'undefined') {
        return;
    }

    window.wp = {};

    // Certain characters need to be escaped so that they can be put into a
    // string literal.
    const escapes = {
            "'": "'",
            '\\': '\\',
            '\r': 'r',
            '\n': 'n',
            '\u2028': 'u2028',
            '\u2029': 'u2029'
        },
        escapeRegExp = /[\\'\r\n\u2028\u2029]/g,
        escapeChar = match => {
            return '\\' + escapes[match];
        },
        // In order to prevent third-party code injection through
        // `_.templateSettings.variable`, we test it against the following regular
        // expression. It is intentionally a bit more liberal than just matching valid
        // identifiers, but still prevents possible loopholes through defaults or
        // destructuring assignment.
        bareIdentifier = /^\s*(\w|\$)+\s*$/,
        // Underscore's default ERB-style templates are incompatible with PHP
        // when asp_tags is enabled, so WordPress uses Mustache-inspired templating syntax.
        // @see trac ticket #22344.
        options = {
            evaluate: /<#([\s\S]+?)#>/g,
            interpolate: /{{{([\s\S]+?)}}}/g,
            escape: /{{([^}]+?)}}(?!})/g,
            variable: 'data'
        },
        cache = {};

    // JavaScript micro-templating, similar to John Resig's implementation.
    // Underscore templating handles arbitrary delimiters, preserves whitespace,
    // and correctly escapes quotes within interpolated code.
    function template(text, settings) {
        // Combine delimiters into one regular expression via alternation.
        const matcher = RegExp([
            (settings.escape || noMatch).source,
            (settings.interpolate || noMatch).source,
            (settings.evaluate || noMatch).source
        ].join('|') + '|$', 'g');

        // Compile the template source, escaping string literals appropriately.
        let index = 0,
            source = "__p+='";

        text.replace(matcher, function (match, escape, interpolate, evaluate, offset) {
            source += text.slice(index, offset).replace(escapeRegExp, escapeChar);
            index = offset + match.length;

            if (escape) {
                source += "'+\n((__t=(" + escape + "))==null?'':_.escape(__t))+\n'";
            } else if (interpolate) {
                source += "'+\n((__t=(" + interpolate + "))==null?'':__t)+\n'";
            } else if (evaluate) {
                source += "';\n" + evaluate + "\n__p+='";
            }

            // Adobe VMs need the match returned to produce the correct offset.
            return match;
        });
        source += "';\n";

        let argument = settings.variable;
        if (argument) {
            // Insure against third-party code injection. (CVE-2021-23358)
            if (!bareIdentifier.test(argument)) throw new Error(
                'variable is not a bare identifier: ' + argument
            );
        } else {
            // If a variable is not specified, place data values in local scope.
            source = 'with(obj||{}){\n' + source + '}\n';
            argument = 'obj';
        }

        source = "var __t,__p='',__j=Array.prototype.join," +
            "print=function(){__p+=__j.call(arguments,'');};\n" +
            source + 'return __p;\n';

        let render;
        try {
            render = new Function(argument, source);
        } catch (e) {
            e.source = source;
            throw e;
        }

        return function (data) {
            return render.call(this, data);
        };
    }

    /**
     * wp.template( id )
     *
     * Fetch a JavaScript template for an id, and return a templating function for it.
     *
     * @param {string} id A string that corresponds to a DOM element with an id prefixed with "tmpl-".
     *                    For example, "attachment" maps to "tmpl-attachment".
     * @return {function} A function that lazily-compiles the template requested.
     */
    wp.template = id => {
        if (!cache.hasOwnProperty(id)) {
            cache[id] = template(document.getElementById('tmpl-' + id)?.innerHTML, options);
        }

        return function (data) {
            return cache[id](data);
        };
    };
})();
