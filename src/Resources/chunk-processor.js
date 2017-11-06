module.exports = function ()  {
    var buffer = null,
        compileType = null,
        fileNameLength = null,
        fileName,
        fileLength,
        file,
        states = {
            "start": [
                function () { return 7; },
                function (chunk) {
                    compileType = chunk.slice(0, 3).toString();
                    fileNameLength = chunk.readUInt32LE(3);
                    return "filename"
                }
            ],
            "filename": [
                function () { return fileNameLength; },
                function (chunk) {
                    fileName = chunk.toString();
                    return "file_length"
                }
            ],
            "file_length": [
                function () { return 4; },
                function (chunk) {
                    fileLength = chunk.readUInt32LE();
                    return "file";
                }
            ],
            "file": [
                function () { return fileLength; },
                function (chunk) {
                    file = chunk.toString();
                }
            ]
        },
        state = "start",
        numberOfBytesToAsk = function () {
            if (buffer !== null ) {
                return states[state][0]() - buffer.length;
            }
            return states[state][0]();
        };

    function additionalData(bytes) {
        return {
            "type": "additional-data",
            "bytes": bytes
        };
    }

    function processChunk(chunk) {
        if (null !== buffer) {
            chunk = Buffer.concat([buffer, chunk], buffer.length + chunk.length);
            buffer = null;
        }

        var currentState = states[state],
            desiredLength = currentState[0]();

        if (desiredLength > chunk.length) {
            buffer = chunk;
            return additionalData(numberOfBytesToAsk());
        }

        state = currentState[1](chunk.slice(0, desiredLength));
        if (state !== undefined) {
            let nextChunk = chunk.length > desiredLength ? chunk.slice(desiredLength) : Buffer.alloc(0);
            return processChunk(nextChunk);
        }

        var result = {
            "type": "message-received",
            "compileType": compileType,
            "fileName": fileName,
            "message": file
        };
        compileType = null;
        fileNameLength = null;
        fileName = null;
        fileLength = null;
        file = null;
        state = "start";

        return result;
    }

    this.processChunk = processChunk;
    this.numberOfBytesToAsk = numberOfBytesToAsk;
};
