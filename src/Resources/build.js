"use strict";

var net = require("net");
var ChunkProcessor = require("./chunk-processor");
var replySender = require("./reply-sender");
var processor = require('./processor');

process.umask(0o000);

// This server listens on a Unix socket at process.argv[2]
var numberOfRequestsHandled = 0,
    unixServer;

unixServer = net.createServer(function (client) {

    var chunkProcessor = new ChunkProcessor();

    client.on('error', function (error) { console.error('woah, wtf', error)});

    function sendReply(isSuccessful, message)
    {
        numberOfRequestsHandled++;
        var stopProcess = numberOfRequestsHandled >= 1000,
            callback = function() {};

        if (stopProcess) {
            callback = function () { unixServer.close(); };
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
    });
});
var _close = function () {
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
unixServer.listen(process.argv[2]);
