(function ()  {
    module.exports = {
        sendReply: function (client, isSuccessful, stopProcess, message, callback) {
            var messageReplyLength = Buffer.byteLength(message),
                reply = Buffer.alloc(5),
                flags = 0;

            reply.writeUInt32LE(messageReplyLength);

            if (isSuccessful) {
                flags |= 1;
            }

            if (stopProcess) {
                flags |= 2;
            }

            reply.writeUInt8(flags, 4);

            client.write(reply, function () { console.log('...?')});
            client.write(message, callback);
        }
    };
}());
