
wamp = new (function($){

    var self = this;

    var settings = {
        authmethods : ['token'],
        realm : 'realm1',
        url : 'ws://localhost:8000',
        uid : 0,
        token : ''
    };

    var connected = false;

    this.connect = null;

    this.init = function(_settings) {
        settings = $.extend(true, {}, settings, _settings);

        self.connect = new autobahn.Connection({
            url: settings.url,
            realm: settings.realm,
            authmethods: settings.authmethods,
            authid: settings.uid,
            onchallenge : function(self, method, extra){
                var hash = CryptoJS.HmacSHA256(extra.challenge, settings.token);
                var hashInBase64 = CryptoJS.enc.Base64.stringify(hash);
                return hashInBase64;
            }
        });

        self.connect.onopen = function (session) {
            self.session = session;
            if (connected) {
                return;
            }
            for (var i in events) {
                if (events.hasOwnProperty(i) == false) {
                    continue;
                }
                events[i].call(self, session);
            }
            events = [];
            connected = true;
        };

        self.connect.open();
    };

    this.subscribe = function(topic, handler, options) {
        return self.connect.session.subscribe(topic, handler, options);
    };

    this.unsubscribe = function(subscription) {
        return self.connect.session.unsubscribe(subscription);
    };

    this.publish = function(topic, args, kwargs, options) {
        if (!options || !options.authid) {
            options.session = self.session.id;
        }
        return self.connect.session.publish(topic, args, kwargs, options);
    };

    this.register = function(procedure, endpoint, options) {
        return self.connect.session.register(procedure, endpoint, options);
    };

    this.unregister = function (registration) {
        return self.connect.session.unregister(registration);
    };

    this.call = function(procedure, args, kwargs, options) {
        if (!options || !options.authid) {
            options.session = self.session.id;
        }
        return self.connect.session.call(procedure, args, kwargs, options);
    };

    var events = [];
    this.onopen = function(callback) {
        if (connected) {
            callback.call(self, self.session);
        } else {
            events.push(callback);
        }
    };
})(jQuery);
