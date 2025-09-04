define([
], function () {
    'use strict';
    // return function (params) {
    //     console.log(params);
    //     var ws = new WebSocket('ws://' + document.location.hostname + ':' + params.port + '?uid=' + params.uid + '&room=' + params.room);
    //     ws.onopen = function (params) {

    //     };
    //     ws.onmessage = params.onmessage;
    //     ws.onerror = function (params) {

    //     };
    //     ws.onclose = function (params) {

    //     };
    //     return ws;
    // }
    function Socket(params) {
        new WebSocket(params)
        console.log(params);
    }
    Socket.prototype = Object.create(WebSocket.prototype);
    Socket.prototype.constructor = Socket;
    return Socket;
});