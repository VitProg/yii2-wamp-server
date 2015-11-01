
wamp = new (function($){

    var self = this;

    var settings = {
        reconnectInterval: 1000,
        maxRetries: 999,
        realm : 'app',
        url : 'ws://localhost:8000',
        uid : 0,
        authToken : '',
        authUrl : 'app.auth'
    };

    var connected = false;

    this.ws = null;

    var token = null;

    this.log = function(v) {
        if (settings.debug) {
            console.log(v);
        }
    };

    this.init = function(_settings) {
        settings = $.extend(true, {}, settings, _settings);

        settings.onConnect = function () {
            self.log('onConnection');
            self.trigger('connect');

            var authData = {
                'sessionId' : self.ws.getSessionId(),
                'authToken' : settings.authToken,
                'uid' : settings.uid
            };
            self.ws.call(settings.authUrl, authData, {
                onSuccess: function (result) {
                    self.log('RPC auth successfully called');
                    self.log(result);

                    if (result && result.token) {
                        self.log('Auth successfully. Token: '. result.token);
                        token = result.token;
                    } else {
                        self.log('Auth error!');
                        self.ws.disconnect();
                        delete (self);
                        delete (wamp);
                    }
                },
                onError: function (err) {
                    self.log('RPC auth successfully failed with error ' + err);
                    self.ws.disconnect();
                    delete (self);
                    delete (wamp);
                }
            })
        };
        settings.onClose = function () {
            self.log('onClose');
            self.trigger('close');
        };
        settings.onError = function (err) {
            self.trigger('error', err);
            self.log('onError');
            self.log(err);
        };
        settings.onReconnect = function () {
            self.trigger('reconnect');
            self.log('reconnect');
        };

        self.ws = new Wampy(settings.url, settings);
    };

    this.subscribe = function(topic, onSuccess, onError, onEvent) {
        return self.ws.subscribe(topic, {'onSuccess' : onSuccess, 'onError' : onError, 'onEvent' : onEvent});
    };

    this.unsubscribe = function(topic, onSuccess, onError, onEvent) {
        return self.ws.unsubscribe(topic, {'onSuccess' : onSuccess, 'onError' : onError, 'onEvent' : onEvent});
    };

    this.publish = function(topic, data, onSuccess, onError, advancedOptions) {
        if (typeof (data) == 'undefined') {
            data = {};
        }
        if (typeof (data.token) == 'undefined') {
            data.token = token;
        }
        return self.ws.publish(topic, data, {'onSuccess' : onSuccess, 'onError' : onError}, advancedOptions);
    };

    this.register = function(topic, rpc, onSuccess, onError) {
        return self.ws.register(topic, {'rpc' : rpc, 'onSuccess' : onSuccess, 'onError' : onError}, advancedOptions);
    };

    this.unregister = function (topic, onSuccess, onError) {
        return self.ws.register(topic, {'rpc' : rpc, 'onSuccess' : onSuccess, 'onError' : onError}, advancedOptions);
    };

    this.call = function(topic, data, onSuccess, onError, advancedOptions) {
        if (typeof (data) == 'undefined') {
            data = {};
        }
        if (typeof (data.token) == 'undefined') {
            data.token = token;
        }
        return self.ws.call(topic, data, {'onSuccess' : onSuccess, 'onError' : onError}, advancedOptions);
    };

    var events = {};
    this.bind = function(event, callback) {
        if (typeof events[event] == 'undefined') {
            events[event] = [];
        }
        events[event].push(callback);
    };

    this.trigger = function(event, data) {
        if (typeof events[event] != 'undefined') {
            for (var i in events[event]) {
                if (events[event].hasOwnProperty(i) == false) {
                    continue;
                }
                events[event][i].call(self, data);
            }
        }
    }

})(jQuery);
