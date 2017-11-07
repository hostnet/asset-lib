(function ()  {
    module.exports = {
        sendReply: function (client, isSuccessful, stopProcess, message, callback) {
            var messageReplyLength = Buffer.byteLength(message),
                reply = Buffer.alloc(5),
                flags = 0;

            if (isSuccessful) {

                flags |= 1;
            }
            if (stopProcess) {

                flags |= 2;
            }
            reply.writeUInt8(flags);
            reply.writeUInt32LE(messageReplyLength, 1);

            client.write(reply, function () {});
            client.write(message, callback);
        }
    };
}());
