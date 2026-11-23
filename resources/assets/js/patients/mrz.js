// Reads the machine-readable zone (MRZ) of an ID card (ICAO 9303, TD1: three lines of 30 characters,
// as on the back of the Burkina Faso / AES identity card) from OCR text. Every field with a check digit
// must pass it, so a badly read frame is refused instead of filling the form with wrong values.

(function (root) {
    const DIGIT_FIX = { O: "0", Q: "0", D: "0", U: "0", I: "1", L: "1", Z: "2", S: "5", B: "8", G: "6", T: "7" };
    const ALPHA_FIX = { 0: "O", 1: "I", 2: "Z", 5: "S", 8: "B", 6: "G", 7: "T" };

    function fix(text, map) {
        return text.split("").map((c) => map[c] || c).join("");
    }

    function charValue(c) {
        if (c >= "0" && c <= "9") return c.charCodeAt(0) - 48;
        if (c >= "A" && c <= "Z") return c.charCodeAt(0) - 55;
        return 0; // "<"
    }

    function checkDigit(text) {
        const weights = [7, 3, 1];
        let sum = 0;
        for (let i = 0; i < text.length; i++) sum += charValue(text[i]) * weights[i % 3];
        return String(sum % 10);
    }

    function valid(field, check) {
        return checkDigit(field) === fix(check, DIGIT_FIX).replace("<", "0");
    }

    // YYMMDD -> YYYY-MM-DD; birth dates are in the past, expiry dates may be in the future.
    function date(yymmdd, future) {
        const yy = parseInt(yymmdd.slice(0, 2), 10);
        const now = new Date().getFullYear() % 100;
        const century = future ? (yy < now + 50 ? 2000 : 1900) : yy > now ? 1900 : 2000;
        const month = parseInt(yymmdd.slice(2, 4), 10);
        const day = parseInt(yymmdd.slice(4, 6), 10);
        if (!month || month > 12 || !day || day > 31) return null;
        return century + yy + "-" + yymmdd.slice(2, 4) + "-" + yymmdd.slice(4, 6);
    }

    function clean(line) {
        return line.toUpperCase().replace(/«/g, "<").replace(/[^A-Z0-9<]/g, "");
    }

    function fit(line) {
        line = line.slice(0, 30);
        return line + "<".repeat(30 - line.length);
    }

    function words(text) {
        return text.replace(/<+$/, "").split("<").filter(Boolean);
    }

    // The three MRZ lines from OCR text: the second line is the one that looks like dates and a sex.
    function findLines(text) {
        const lines = String(text).split(/\r?\n/).map(clean).filter((l) => l.length >= 25);
        for (let i = 1; i < lines.length - 1; i++) {
            const second = fix(lines[i].slice(0, 7), DIGIT_FIX) + lines[i].slice(7);
            if (/^\d{7}[MFX<]/.test(second) && lines[i - 1].length >= 25) {
                return [fit(lines[i - 1]), fit(lines[i]), fit(lines[i + 1])];
            }
        }
        return null;
    }


    function parseMrz(text) {
        const lines = findLines(text);
        if (!lines) return null;
        let [l1, l2, l3] = lines;
        l1 = l1.replace(/^[T1L]/, "I");

        // Document number (9 characters, longer numbers continue in the optional field).
        let number = l1.slice(5, 14);
        let numberCheck = l1[14];
        let optional1 = l1.slice(15, 30);
        if (numberCheck === "<") {
            const rest = optional1.replace(/<.*$/, "");
            number += rest.slice(0, -1);
            numberCheck = rest.slice(-1);
            optional1 = optional1.slice(rest.length);
        }
        const birth = fix(l2.slice(0, 6), DIGIT_FIX);
        const expiry = fix(l2.slice(8, 14), DIGIT_FIX);
        if (!valid(number, numberCheck) || !valid(birth, l2[6]) || !valid(expiry, l2[14])) return null;

        // Optional data: digits and letters are kept, misread fillers become "<". When the optional data
        // is not empty, the composite check digit must pass too.
        const clean1 = /^[<KLXC(]*$/.test(optional1) ? "" : optional1.replace(/<[KLXC(<]*$/, "");
        const optional2 = l2.slice(18, 29);
        const clean2 = /^[<KLXC(]*$/.test(optional2) ? "" : optional2.replace(/<[KLXC(<]*$/, "");
        if (clean1 || clean2) {
            const composite = l1.slice(5, 30) + birth + l2[6] + expiry + l2[14] + optional2;
            if (!valid(composite, l2[29])) return null;
        }

        // Names: SURNAME<<GIVEN<NAMES, then fillers (and filler misreads) to the end.
        const nameLine = fix(l3, ALPHA_FIX);
        const split = nameLine.indexOf("<<");
        const surname = split < 0 ? nameLine : nameLine.slice(0, split);
        const given = split < 0 ? "" : nameLine.slice(split + 2).replace(/[KLXC(]{4,}<*$/, "").replace(/<{2,}.*$/, "").replace(/<[KLXC(]{1,3}$/, "");
        const sexChar = l2[7];

        return {
            documentType: l1.slice(0, 2).replace(/</g, ""),
            country: fix(l1.slice(2, 5), ALPHA_FIX).replace(/</g, ""),
            documentNumber: number.replace(/</g, ""),
            birthDate: date(birth, false),
            sex: sexChar === "M" ? "M" : sexChar === "F" ? "F" : null,
            expiryDate: date(expiry, true),
            nationality: fix(l2.slice(15, 18), ALPHA_FIX).replace(/</g, ""),
            lastName: words(surname).join(" "),
            firstNames: words(given).join(" "),
            optional: (clean1 + clean2).replace(/</g, ""),
        };
    }

    root.parseMrz = parseMrz;
    root.mrzCheckDigit = checkDigit;
    if (typeof module !== "undefined") module.exports = { parseMrz, checkDigit };
})(typeof window !== "undefined" ? window : globalThis);
