module.exports = function ()  {
    var amountToReceive = null,
        compileType = null,
        fileName = null,
        buffer,
        protocolBuffer = null,
        protocolReceiveBytes = 7;

    function additionalData(bytes) {
        return {
            "type": "additional-data",
            "bytes": bytes
        };
    }

    function waitForProtocolHeader(chunk) {
        if (protocolBuffer !== null) {
            chunk = Buffer.concat([protocolBuffer, chunk], protocolBuffer.length + chunk.length);
        }

        // We need more data!
        if (chunk.length < protocolReceiveBytes) {
            protocolBuffer = chunk;
            return additionalData(protocolReceiveBytes - chunk.length);
        }

        amountToReceive = chunk.readUInt32LE();
        compileType = String.fromCharCode(chunk.readUInt8(4)) +
            String.fromCharCode(chunk.readUInt8(5)) +
            String.fromCharCode(chunk.readUInt8(6));

        buffer = '';
        protocolBuffer = null;

        return additionalData(amountToReceive);
    };

    function waitForFileName(chunk) {
        if (protocolBuffer !== null) {
            chunk = Buffer.concat([protocolBuffer, chunk], protocolBuffer.length + chunk.length);
        }

        var index = chunk.indexOf("\0");

        if (index === -1) {
            protocolBuffer = chunk;
            return additionalData(amountToReceive);
        }

        // Yeah, we got a \0
        // Everything before is the filename
        // Everything after is part of the file contents
        fileName = chunk.slice(0, index).toString();
        protocolBuffer = null;
        return processChunk(chunk.slice(index + 1));
    }

    function processChunk(chunk) {
        var originalFileName;
        // Receiving new message info
        if (amountToReceive === null) {
            return waitForProtocolHeader(chunk);
        }
        
        if (fileName === null) {
            return waitForFileName(chunk);
        }

        // Completed new message
        if (chunk.length >= amountToReceive) {

            buffer += chunk.slice(0, amountToReceive);
            originalFileName = fileName;
            fileName = null;
            amountToReceive = null;

            return {
                "type": "message-received",
                "compileType": compileType,
                "fileName": originalFileName,
                "message": buffer
            };
        }

        // Message receiving in progress, more coming
        amountToReceive -= chunk.length;
        buffer += chunk.toString();

        return additionalData(amountToReceive);
    }

    this.protocolReceiveBytes = protocolReceiveBytes;
    this.processChunk = processChunk;
};
