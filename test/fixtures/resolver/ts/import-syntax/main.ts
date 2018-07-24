import { Foo } from "./DoubleQuote";
import { Bar } from './SingleQuote';
import simple from './Simple';
import { A as B } from "./Alias";
import * as C from "./All";
import { D, E } from "./Multiple";
import {
    F,
    G
} from
    './Multiple2';
import "./module.js";
import "module_index";
import "module_package";
import "module_package_dir";
import $ from "jquery";
import i = require("./Import");
import Array from "./Array";

export {A as Alias} from "./Alias";
export * from "./DoubleQuote";
export * from "./Simple";

export { ZipCodeValidator as mainValidator };
export default "123";
export = ZipCodeValidator;
export var t = something + 1;

export default function (s: string) {
    return s.length === 5 && numberRegexp.test(s);
}

export default class ZipCodeValidator {
    static numberRegexp = /^[0-9]+$/;
    isAcceptable(s: string) {
        return s.length === 5 && ZipCodeValidator.numberRegexp.test(s);
    }
}
