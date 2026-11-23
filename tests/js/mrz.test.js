// Run: node tests/js/mrz.test.js  (synthetic card, no real person)
const assert = require("assert");
const { parseMrz, checkDigit } = require("../../resources/assets/js/patients/mrz.js");

const pad = (s) => s + "<".repeat(30 - s.length);
const number = "B12345678";
const birth = "900415";
const expiry = "330101";
const l1 = pad("I<BFA" + number + checkDigit(number));
const l2body = birth + checkDigit(birth) + "F" + expiry + checkDigit(expiry) + "BFA" + "<".repeat(11);
const composite = l1.slice(5, 30) + l2body.slice(0, 7) + l2body.slice(8, 15) + l2body.slice(18, 29);
const l2 = l2body + checkDigit(composite);
const l3 = pad("OUEDRAOGO<<AWA<MARIE");

// OCR noise: other text around, spaces, "O" read for "0" in a date, "K" read for the fillers.
const ocr = ["CARTE D'IDENTITE", l1.replace(/</g, "< ").slice(0, 40), l2.replace("0415", "O415"), l3.replace(/<+$/, "KKKKKKKKK"), "foot"].join("\n");
const card = parseMrz(ocr);
assert.deepStrictEqual(
    [card.documentNumber, card.birthDate, card.sex, card.expiryDate, card.lastName, card.firstNames, card.nationality],
    ["B12345678", "1990-04-15", "F", "2033-01-01", "OUEDRAOGO", "AWA MARIE", "BFA"]
);

// A misread digit breaks a check digit: the frame is refused, never half-filled.
assert.strictEqual(parseMrz([l1.replace("12345", "12845"), l2, l3].join("\n")), null);
assert.strictEqual(parseMrz("no card here"), null);
assert.strictEqual(checkDigit("B00000008"), "5"); // ICAO example weights 7-3-1
console.log("mrz ok");
