"use strict";

var fs_1 = require("fs");
var ts = require("typescript");
var net = require("net");
var ChunkProcessor = require("./chunk-processor");
var replySender = require("./reply-sender");
var processor = require('./processor');

var newMask = 0o000;
var oldMask = process.umask(newMask);

// This server listens on a Unix socket at /tmp/build
var numberOfRequestsHandled = 0,
    unixServer = net.createServer(function (client) {

    var chunkProcessor = new ChunkProcessor();

    client.on('error', function (error) { console.error('woah, wtf', error)});

    function sendReply(isSuccessful, message)
    {
        console.log('numberOfRequestsHandled', numberOfRequestsHandled);
        var stopProcess = numberOfRequestsHandled >= 1000;
        numberOfRequestsHandled++;

        //console.log("hi", typeof message, message, message.toString());
        //message = typeof message === 'string' ? message : JSON.stringify(message);

        //console.log(message);

        var callback = function() {};
        if (stopProcess) {
            callback = function () { unixServer.close();};
        }

        replySender.sendReply(client, isSuccessful, stopProcess, message, callback);
    }

    client.on('readable', function() {

        var chunk, bytes = chunkProcessor.protocolReceiveBytes;

        while (null !== (chunk = client.read(Math.min(1024, bytes)))) {
            var request = chunkProcessor.processChunk(chunk);
            switch (request.type) {
                case "additional-data":
                    bytes = request.bytes;
                    break;

                case "message-received":
                    bytes = chunkProcessor.protocolReceiveBytes;
                    try {
                        sendReply(true, processor.process(request.compileType, request.fileName, request.message));
                    } catch (e) {
                        if (e instanceof Error) {
                            e = e.message +
                                (e.hasOwnProperty('code') ? ' (code ' + e.code+ ')' : '') +
                                '. Stack: ' + e.stack;
                        }
                        sendReply(false, e);
                    }
                    break;

                default:
                    throw "Unknown type " + request.type;
            }
        }
        //console.log("Writable", client.writable);
        //client.write('Hola');
        //console.log(client.constructor.name);


        // return;
        //
        // var chunk,
        //     N = 4;
        //
        // while (null !== (chunk = client.read(N))) {
        //     var x = chunk.charCodeAt(0) << (8*3) +
        //     chunk.charCodeAt(1) << (8*2) +
        //     chunk.charCodeAt(2) << (8*1) +
        //     chunk.charCodeAt(3) << (8*0);
        //
        //     console.log('got %d bytes of data', x);
        //     console.log(chunk.toString());
        // }
    });
});
var _close = function () {
    console.log('EXITING');
    unixServer.close();
};
process.on('SIGTERM', function () {
    _close();
});
process.on('SIGINT', function () {
    _close();
});
process.on('exit', function () {
    _close();
});
unixServer.listen('/tmp/build');

