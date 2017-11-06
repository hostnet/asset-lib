var ChunkProcessor = require('../src/Resources/chunk-processor');

describe("chunk-processor", function () {
    it("protocolReceiveBytes", function () {
        var chunkProcessor = new ChunkProcessor();
        expect(chunkProcessor.numberOfBytesToAsk()).toEqual(7);
    });

    it("processChunk in large bits", function () {
        var chunkProcessor = new ChunkProcessor();
        // Send the header, we need 12 bytes of file contents afterwards
        var chunk = Buffer.alloc(7);
        chunk.write('ABC');
        chunk.writeUInt32LE(9, 3);

        var result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('additional-data');
        expect(result.bytes).toEqual(9);

        // Send 9 bytes, the file name
        chunk = Buffer.from("/h/foo.js");
        result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('additional-data');
        expect(result.bytes).toEqual(4);

        chunk = Buffer.alloc(4);
        chunk.writeUInt32LE(12);
        result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('additional-data');
        expect(result.bytes).toEqual(12);

        // Send the file contents
        chunk = Buffer.from('alert("Hi");');
        result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('message-received');
        expect(result.compileType).toEqual('ABC');
        expect(result.fileName).toEqual('/h/foo.js');
        expect(result.message).toEqual('alert("Hi");');
        expect(chunkProcessor.numberOfBytesToAsk()).toEqual(7);
    });

    it("processChunk in small bits", function () {
        var chunkProcessor = new ChunkProcessor();

        // Send 2 out of 7 bytes of the header, five more required
        var chunk = Buffer.from('AB');
        var result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('additional-data');
        expect(result.bytes).toEqual(5);

        chunk = Buffer.alloc(5);
        chunk.write('C');
        chunk.writeUInt32LE(9, 1);

        result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('additional-data');
        expect(result.bytes).toEqual(9);

        // Send a part of the filename
        chunk = Buffer.from("/h/");
        result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('additional-data');
        expect(result.bytes).toEqual(6);

        // Send exactly the filename
        chunk = Buffer.from("foo.js");
        result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('additional-data');
        expect(result.bytes).toEqual(4);

        chunk = Buffer.alloc(4);
        chunk.writeUInt32LE(12);
        result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('additional-data');
        expect(result.bytes).toEqual(12);

        // Send three parts of file content, 9 more required
        chunk = Buffer.from('ale');
        result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('additional-data');
        expect(result.bytes).toEqual(9);

        // Send the last 9 parts
        chunk = Buffer.from('rt("Hi");');
        result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('message-received');
        expect(result.compileType).toEqual('ABC');
        expect(result.fileName).toEqual('/h/foo.js');
        expect(result.message).toEqual('alert("Hi");');
    });

    it("processChunk with empty file", function () {
        var chunkProcessor = new ChunkProcessor();

        var chunk = Buffer.alloc(7);
        chunk.write('ABC');
        chunk.writeUInt32LE(0, 3);

        var result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('additional-data');
        expect(result.bytes).toEqual(4);

        // Ok, so now we are asked for the file length
        // Let's also send 0 for that
        chunk = Buffer.alloc(4);
        chunk.writeUInt32LE(0);

        result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('message-received');
        expect(result.compileType).toEqual('ABC');
        expect(result.fileName).toEqual('');
        expect(result.message).toEqual('');
    });

    it("processChunk send all at once", function () {
        var chunkProcessor = new ChunkProcessor();

        var chunk = Buffer.alloc(32);
        chunk.write("ABC");
        chunk.writeUInt32LE(9, 3);
        chunk.write("/h/foo.js", 7);
        chunk.writeUInt32LE(12, 16);
        chunk.write("alert(\"Hi\");", 20);

        var result = chunkProcessor.processChunk(chunk);

        expect(result.type).toEqual('message-received');
        expect(result.compileType).toEqual('ABC');
        expect(result.fileName).toEqual('/h/foo.js');
        expect(result.message).toEqual('alert("Hi");');
    });

    it("processChunk handles two messages", function () {
        var chunkProcessor = new ChunkProcessor();

        var chunk = Buffer.alloc(32);
        chunk.write("ABC");
        chunk.writeUInt32LE(9, 3);
        chunk.write("/h/foo.js", 7);
        chunk.writeUInt32LE(12, 16);
        chunk.write("alert(\"Hi\");", 20);

        var result = chunkProcessor.processChunk(chunk);

        expect(result.type).toEqual('message-received');
        expect(result.compileType).toEqual('ABC');
        expect(result.fileName).toEqual('/h/foo.js');
        expect(result.message).toEqual('alert("Hi");');

        chunk.write("/h/bar.js", 7);
        chunk.write("alert(\"Yo\");", 20);
        result = chunkProcessor.processChunk(chunk);

        expect(result.type).toEqual('message-received');
        expect(result.compileType).toEqual('ABC');
        expect(result.fileName).toEqual('/h/bar.js');
        expect(result.message).toEqual('alert("Yo");');
    });
});
