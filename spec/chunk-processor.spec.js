var ChunkProcessor = require('../src/Resources/chunk-processor');

describe("chunk-processor", function () {
    it("protocolReceiveBytes", function () {
        var chunkProcessor = new ChunkProcessor();
        expect(chunkProcessor.protocolReceiveBytes).toEqual(7);
    });

    it("processChunk in large bits", function () {
        var chunkProcessor = new ChunkProcessor();
        // Send the header, we need 12 bytes of file contents afterwards
        var chunk = Buffer.alloc(7);
        chunk.writeUInt32LE(12);
        chunk.write('ABC', 4);

        var result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('additional-data');
        expect(result.bytes).toEqual(12);

        // Send 12 bytes, part of which is the file name
        chunk = Buffer.from("/h/foo.js\0al");
        result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('additional-data');
        expect(result.bytes).toEqual(10);

        // Send the other 10 bytes of content
        chunk = Buffer.from('ert("Hi");');
        result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('message-received');
        expect(result.compileType).toEqual('ABC');
        expect(result.fileName).toEqual('/h/foo.js');
        expect(result.message).toEqual('alert("Hi");');
    });

    it("processChunk in small bits", function () {
        var chunkProcessor = new ChunkProcessor();
        // Send 5 out of 7 bytes of the header, two more required
        var chunk = Buffer.alloc(5);
        chunk.writeUInt32LE(12);
        chunk.write('A', 4);

        var result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('additional-data');
        expect(result.bytes).toEqual(2);

        // Send the last two. Now 12 bytes of file content required.
        chunk = Buffer.from('BC');
        result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('additional-data');
        expect(result.bytes).toEqual(12);

        // Send a part of the filename
        chunk = Buffer.from("/h/");
        result = chunkProcessor.processChunk(chunk);
        expect(result.type).toEqual('additional-data');
        expect(result.bytes).toEqual(12);

        // Send exactly the filename
        chunk = Buffer.from("foo.js\0");
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
});
