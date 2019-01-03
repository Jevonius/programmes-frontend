/**
 * Cookie is responsible to persist variables and recover them from cookies
 */
define(function () {

    var CookieStore = function() {

        var COOKIE_LIFETIME = 1000 * 60 * 60 * 24 * 90; // 90 days

        this.findByName = function (name) {
            var all = findAll();
            var cookie = all[name];

            if (cookie !== undefined) {
                try {
                    return JSON.parse(cookie);
                } catch (e) {
                    this.persist(name, []);
                }
            }

            return null;
        };

        this.persist = function (name, value) {
            if (window.bbccookies !== undefined && window.bbccookies.cookiesEnabled() && bbccookies.isAllowed(name)) {
                value = JSON.stringify(value);

                var expires = new Date(new Date().getTime() + COOKIE_LIFETIME);

                window.bbccookies.set(document.cookie = [
                    encodeURIComponent(name) + '=' + window.escape(value),
                    'path=/programmes',
                    expires ? 'expires=' + expires.toUTCString() : '',
                    'domain=.bbc.co.uk'
                ].join('; '));
            }
        };

        var findAll = function () {
            if (window.bbccookies === undefined || !window.bbccookies.cookiesEnabled()) {
                return {};
            }
            var cookies = window.bbccookies.get().split(';'),
                cookieValue,
                currentCookie,
                cookieName,
                parsedCookies = {};

            for (var i = 0; i < cookies.length; i++) {
                currentCookie = cookies[i];
                currentCookie = currentCookie.replace(/^\s+|\s+$/g,""); /* trim */
                currentCookie = currentCookie.split('=');
                cookieValue = window.unescape(currentCookie[1]);
                cookieName = window.unescape(currentCookie[0]);

                parsedCookies[cookieName] = cookieValue;
            }

            return parsedCookies;
        };

    };

    return CookieStore;
});
