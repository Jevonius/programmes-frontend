define(['idcta/idcta-1'], function(idcta) {
    return {
        userIsGuest: function() {
            if (idcta.hasCookie() && idcta.getUserDetailsFromCookie() !== null) {
                return false;
            }
            return true;
        },

        readPolicy: function(policyName) {
            // Can't have policy if you don't have a control panel, no?
            if (this.userIsGuest()) {
                return null;
            }

            if (window.bbccookies !== undefined && window.bbccookies.cookiesEnabled()) {
                return window.bbccookies.readPolicy(policyName);
            }

            // If the user hasn't explictly granted you permission it's false by default
            // But you should never arrive here since bbccookies must be always set
            return false;
        }
    };
});
