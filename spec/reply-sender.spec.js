var replySender = require('../src/Resources/reply-sender');

describe("chunk-processor", function () {
    it("sendReply", function () {
        var sent = [];
        var client = { write: function (buffer) { sent.push(buffer); } },
            result = replySender.sendReply(client, true, true, "ABCDE𒍅");

        expect(sent.length).toEqual(2);

        expect(sent[0].constructor.name).toEqual('Buffer');
        expect(sent[0].readUInt32LE()).toEqual(9);
        expect(sent[0].readUInt8(4)).toEqual(3);

        expect(sent[1]).toEqual('ABCDE𒍅');
    });
});
