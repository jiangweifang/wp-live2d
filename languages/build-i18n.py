"""Extract translatable strings from PHP source and build POT/PO/MO.

Usage:
    python languages/build-i18n.py

Generates (under languages/):
    - live-2d.pot
    - live-2d-{locale}.po for each locale in translations.json
    - live-2d-{locale}.mo compiled from each .po

Pure stdlib: no babel/msgfmt required.
"""
from __future__ import annotations

import json
import os
import re
import struct
import sys
from collections import OrderedDict
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
LANG_DIR = ROOT / "languages"
TEXT_DOMAIN = "live-2d"

# PHP files to scan for gettext calls.
SCAN_FILES = [
    ROOT / "wordpress-live2d.php",
    *(ROOT / "src").glob("*.php"),
]

# Functions whose 1st arg is the singular msgid in the live-2d domain.
GETTEXT_FUNCS = (
    "__",
    "_e",
    "esc_html__",
    "esc_html_e",
    "esc_attr__",
    "esc_attr_e",
    "_x",
    "_ex",
    "esc_html_x",
    "esc_attr_x",
)

# Match a gettext call:  funcname( 'string' [, ...] , 'live-2d' )
# - string can be single- or double-quoted
# - allow escaped quotes inside
# - require the live-2d text domain to disambiguate from other plugins' calls
CALL_RE = re.compile(
    r"\b(?P<fn>" + "|".join(GETTEXT_FUNCS) + r")\s*\(\s*"
    r"(?P<q>['\"])(?P<msg>(?:\\.|(?!(?P=q)).)*)(?P=q)"
    r"(?:\s*,\s*(?:['\"](?:\\.|[^'\"\\])*['\"]\s*,\s*)?)"
    r"['\"]" + re.escape(TEXT_DOMAIN) + r"['\"]\s*\)",
    re.DOTALL,
)


def php_unescape(s: str, quote: str) -> str:
    """Mimic PHP single/double quoted string escapes for the subset we use."""
    if quote == "'":
        # PHP single-quoted: only \' and \\ are special
        return re.sub(r"\\(['\\])", r"\1", s)
    # double-quoted: handle \n, \r, \t, \", \\, \$
    out = []
    i = 0
    while i < len(s):
        c = s[i]
        if c == "\\" and i + 1 < len(s):
            nxt = s[i + 1]
            if nxt == "n":
                out.append("\n")
            elif nxt == "r":
                out.append("\r")
            elif nxt == "t":
                out.append("\t")
            elif nxt in '"\\$':
                out.append(nxt)
            else:
                out.append(c + nxt)
            i += 2
            continue
        out.append(c)
        i += 1
    return "".join(out)


def extract_calls(text: str) -> list[tuple[str, int]]:
    """Return list of (msgid, line_number) tuples found in text."""
    results = []
    for m in CALL_RE.finditer(text):
        msg = php_unescape(m.group("msg"), m.group("q"))
        line = text.count("\n", 0, m.start()) + 1
        results.append((msg, line))
    return results


def collect_strings() -> "OrderedDict[str, list[str]]":
    """Walk SCAN_FILES, return ordered dict of msgid -> ['file:line', ...]."""
    found: "OrderedDict[str, list[str]]" = OrderedDict()
    for fpath in SCAN_FILES:
        if not fpath.exists():
            continue
        text = fpath.read_text(encoding="utf-8")
        rel = fpath.relative_to(ROOT).as_posix()
        for msg, line in extract_calls(text):
            ref = f"{rel}:{line}"
            if msg not in found:
                found[msg] = []
            if ref not in found[msg]:
                found[msg].append(ref)
    # Sort by first occurrence file/line for stable diffs
    return found


def po_escape(s: str) -> str:
    return (
        s.replace("\\", "\\\\")
        .replace('"', '\\"')
        .replace("\n", "\\n")
        .replace("\t", "\\t")
    )


def format_po_msg(key: str, value: str) -> list[str]:
    """Render `key "value"` with continuation lines for embedded \n."""
    if "\n" not in value:
        return [f'{key} "{po_escape(value)}"']
    # Multi-line: leading "" then each line ending with \n quoted separately
    out = [f'{key} ""']
    parts = value.split("\n")
    for i, p in enumerate(parts):
        suffix = "\\n" if i < len(parts) - 1 else ""
        out.append(f'"{po_escape(p)}{suffix}"')
    return out


def write_pot(strings: "OrderedDict[str, list[str]]", path: Path) -> None:
    now = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M+0000")
    lines = [
        "# Live 2D - WordPress plugin",
        "# Copyright (C) Weifang Chiang",
        '# This file is distributed under the same license as the Live 2D plugin.',
        "#",
        "#, fuzzy",
        'msgid ""',
        'msgstr ""',
        '"Project-Id-Version: Live 2D 2.0.0\\n"',
        '"Report-Msgid-Bugs-To: \\n"',
        f'"POT-Creation-Date: {now}\\n"',
        '"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"',
        '"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"',
        '"Language-Team: \\n"',
        '"Language: \\n"',
        '"MIME-Version: 1.0\\n"',
        '"Content-Type: text/plain; charset=UTF-8\\n"',
        '"Content-Transfer-Encoding: 8bit\\n"',
        '"X-Generator: build-i18n.py\\n"',
        "",
    ]
    for msgid, refs in strings.items():
        for ref in refs:
            lines.append(f"#: {ref}")
        lines.extend(format_po_msg("msgid", msgid))
        lines.append('msgstr ""')
        lines.append("")
    path.write_text("\n".join(lines) + "\n", encoding="utf-8")


def write_po(
    strings: "OrderedDict[str, list[str]]",
    translations: dict[str, str],
    locale: str,
    plural_forms: str,
    language_team: str,
    path: Path,
) -> None:
    now = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M+0000")
    lines = [
        f"# Live 2D - {locale}",
        "# Copyright (C) Weifang Chiang",
        '# This file is distributed under the same license as the Live 2D plugin.',
        "#",
        'msgid ""',
        'msgstr ""',
        '"Project-Id-Version: Live 2D 2.0.0\\n"',
        '"Report-Msgid-Bugs-To: \\n"',
        f'"POT-Creation-Date: {now}\\n"',
        f'"PO-Revision-Date: {now}\\n"',
        f'"Language-Team: {language_team}\\n"',
        f'"Language: {locale}\\n"',
        '"MIME-Version: 1.0\\n"',
        '"Content-Type: text/plain; charset=UTF-8\\n"',
        '"Content-Transfer-Encoding: 8bit\\n"',
        f'"Plural-Forms: {plural_forms}\\n"',
        '"X-Generator: build-i18n.py\\n"',
        "",
    ]
    for msgid, refs in strings.items():
        for ref in refs:
            lines.append(f"#: {ref}")
        lines.extend(format_po_msg("msgid", msgid))
        lines.extend(format_po_msg("msgstr", translations.get(msgid, "")))
        lines.append("")
    path.write_text("\n".join(lines) + "\n", encoding="utf-8")


# --- MO compilation (gettext binary format) ---
# https://www.gnu.org/software/gettext/manual/html_node/MO-Files.html

MO_MAGIC = 0x950412DE


def compile_mo(po_entries: list[tuple[str, str]], path: Path) -> None:
    """Write a .mo file from a list of (msgid, msgstr) pairs.

    The metadata header (empty msgid) MUST be the first entry. Entries with
    empty msgstr are skipped (untranslated -> let gettext fall back).
    """
    # Filter and sort by msgid (gettext requires sorted msgid for binary search)
    items = [(k, v) for k, v in po_entries if v != "" or k == ""]
    items.sort(key=lambda kv: kv[0].encode("utf-8"))

    keys_b = [k.encode("utf-8") for k, _ in items]
    vals_b = [v.encode("utf-8") for _, v in items]
    n = len(items)

    header_size = 7 * 4
    key_table_offset = header_size
    val_table_offset = key_table_offset + n * 8

    output = bytearray()
    # Reserve space for header + tables; we'll fill string offsets as we append data.
    strings_start = val_table_offset + n * 8
    output.extend(b"\x00" * strings_start)

    key_offsets: list[tuple[int, int]] = []
    val_offsets: list[tuple[int, int]] = []

    for kb in keys_b:
        offset = len(output)
        output.extend(kb)
        output.append(0)
        key_offsets.append((len(kb), offset))
    for vb in vals_b:
        offset = len(output)
        output.extend(vb)
        output.append(0)
        val_offsets.append((len(vb), offset))

    # Write header
    struct.pack_into(
        "<IIIIIII",
        output,
        0,
        MO_MAGIC,           # magic
        0,                  # revision
        n,                  # nstrings
        key_table_offset,   # offset of key table
        val_table_offset,   # offset of value table
        0,                  # hash table size (0 = no hash)
        0,                  # hash table offset
    )
    # Key table
    for i, (length, offset) in enumerate(key_offsets):
        struct.pack_into("<II", output, key_table_offset + i * 8, length, offset)
    # Value table
    for i, (length, offset) in enumerate(val_offsets):
        struct.pack_into("<II", output, val_table_offset + i * 8, length, offset)

    path.write_bytes(bytes(output))


def build_mo_for_locale(
    strings: "OrderedDict[str, list[str]]",
    translations: dict[str, str],
    locale: str,
    plural_forms: str,
    path: Path,
) -> None:
    # The metadata entry; gettext header.
    now = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M+0000")
    metadata = (
        f"Project-Id-Version: Live 2D 2.0.0\n"
        f"PO-Revision-Date: {now}\n"
        f"Language-Team: \n"
        f"Language: {locale}\n"
        f"MIME-Version: 1.0\n"
        f"Content-Type: text/plain; charset=UTF-8\n"
        f"Content-Transfer-Encoding: 8bit\n"
        f"Plural-Forms: {plural_forms}\n"
    )
    entries: list[tuple[str, str]] = [("", metadata)]
    for msgid in strings:
        tr = translations.get(msgid, "")
        if tr:
            entries.append((msgid, tr))
    compile_mo(entries, path)


# --- Locale config ---
LOCALES = {
    # WP locale code -> (Plural-Forms, Language-Team)
    "zh_CN": ("nplurals=1; plural=0;", "Simplified Chinese"),
    "zh_TW": ("nplurals=1; plural=0;", "Traditional Chinese (Taiwan)"),
    "en_US": ("nplurals=2; plural=(n != 1);", "English (United States)"),
    "ja":    ("nplurals=1; plural=0;", "Japanese"),
}


def main() -> int:
    LANG_DIR.mkdir(parents=True, exist_ok=True)
    strings = collect_strings()
    print(f"Extracted {len(strings)} unique strings from {len(SCAN_FILES)} PHP files")

    # POT
    pot_path = LANG_DIR / f"{TEXT_DOMAIN}.pot"
    write_pot(strings, pot_path)
    print(f"Wrote {pot_path.relative_to(ROOT)}")

    # Translations
    tr_path = LANG_DIR / "translations.json"
    if not tr_path.exists():
        print(f"ERROR: missing {tr_path}", file=sys.stderr)
        return 1
    all_tr = json.loads(tr_path.read_text(encoding="utf-8"))

    missing_report = {}
    for locale, (plural, team) in LOCALES.items():
        tr_map = all_tr.get(locale, {})
        po_path = LANG_DIR / f"{TEXT_DOMAIN}-{locale}.po"
        write_po(strings, tr_map, locale, plural, team, po_path)
        mo_path = LANG_DIR / f"{TEXT_DOMAIN}-{locale}.mo"
        build_mo_for_locale(strings, tr_map, locale, plural, mo_path)
        missing = [m for m in strings if m not in tr_map or tr_map[m] == ""]
        if missing:
            missing_report[locale] = missing
        print(f"Wrote {po_path.relative_to(ROOT)} and {mo_path.relative_to(ROOT)} ({len(strings) - len(missing)}/{len(strings)} translated)")

    if missing_report:
        print("\n--- Missing translations ---")
        for locale, ms in missing_report.items():
            print(f"  [{locale}] missing {len(ms)} strings:")
            for m in ms:
                print(f"    - {m!r}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
