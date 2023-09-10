export class Ajax
{
    static get(url, options = {}, headers = {})
    {
        options = Object.assign({
            'url': url,
        }, options);

        return this.run(options, headers);
    }

    static post(url, data = {}, options = {}, headers = {})
    {
        options = Object.assign({
            'method': 'POST',
            'url': url,
            'data': data,
        }, options);

        return this.run(options, headers);
    }

    static run(options = {}, headers = {})
    {
        let defaultOptions = {};
        let ajaxHeaders = {};
        if (typeof window.config === 'object' && typeof window.config.ajax === 'object') {
            if (typeof window.config.ajax.options === 'object') {
                defaultOptions = window.config.ajax.options;
            }
            if (typeof window.config.ajax.headers === 'object') {
                ajaxHeaders = window.config.ajax.headers;
            }
        }

        options = Object.assign({
            'method': 'GET',
            'url': '',
            'data': null,
            'timeout': 0,
            'loadFunction': null,
            'timeoutFunction': null,
            'errorFunction': null,
            'loadendFunction': null,
            'cache': false,
            'contentType': null,
            'xhr': null,
        }, defaultOptions, options);

        headers = Object.assign({
            'X-Requested-With': 'XMLHttpRequest',
        }, ajaxHeaders, headers);

        if (!options.cache) {
            headers = Object.assign(headers, {
                'Cache-Control': 'no-cache, max-age=0',
            });
        }

        let xhr = new XMLHttpRequest();
        xhr.timeout = options.timeout;
        xhr.open(options.method, options.url);

        for (let key in headers) {
            if (!headers.hasOwnProperty(key)) {
                continue;
            }

            xhr.setRequestHeader(key, headers[key]);
        }

        // Run always
        this.onEnd = function(fn)
        {
            xhr.addEventListener('loadend', function()
            {
                fn(xhr);
            });

            return this;
        };
        if (typeof options.loadendFunction === 'function') {
            this.onEnd(options.loadendFunction);
        }

        // OnLoad
        this.onLoad = function(fn)
        {
            xhr.addEventListener('load', function()
            {
                if (xhr.status !== 200) {
                    return;
                }

                fn(xhr);
            });

            return this;
        };
        if (typeof options.loadFunction === 'function') {
            this.onLoad(options.loadFunction);
        }

        // OnTimeout
        this.onTimeout = function(fn)
        {
            xhr.addEventListener('timeout', function()
            {
                fn(xhr);
            });

            return this;
        };
        if (typeof options.timeoutFunction === 'function') {
            this.onTimeout(options.timeoutFunction);
        }

        // OnError
        this.onError = function(fn)
        {
            xhr.addEventListener('loadend', function()
            {
                // !getAllResponseHeaders returns true if the user aborted the load
                if (xhr.status === 200 || !xhr.getAllResponseHeaders()) {
                    return;
                }
                fn(xhr);
            });

            return this;
        };
        if (typeof options.errorFunction === 'function') {
            this.onError(options.errorFunction);
        }

        this.getXhrObject = function()
        {
            return xhr;
        };

        // Customizable XHR-object
        if (typeof options.xhr === 'function') {
            xhr = options.xhr(xhr);
        }

        if (options.data) {
            if (typeof options.data.append !== 'function') {
                // Not FormData... Maybe a regular object? Convert to FormData.
                let data = new FormData();
                Object.keys(options.data).forEach(key => data.append(key, options.data[key]));
                options.data = data;
            }
        }

        xhr.send(options.data);

        return this;
    }
}
