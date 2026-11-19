#!/usr/bin/env python3
"""Create a product repository from the Laravel template.

Usage: python3 scripts/new-repo.py --name "Sample Product" --slug sample-product \\
           --pitch "One line." --target <dir> [--namespace SampleProduct] \\
           [--modules Orders,Billing] [--date YYYY-MM-DD] [--no-git]
Exit code: 2 on bad input or a leftover token, otherwise 0."""
import argparse, datetime, hashlib, json, os, re, shutil, subprocess, sys

REPO = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
TEMPLATE = os.path.join(REPO, "template")
MODULE_TEMPLATE = os.path.join(REPO, "module-template")

TOKEN_RE = re.compile(r"\{\{[A-Z][A-Z0-9_]*\}\}")
SLUG_RE = re.compile(r"^[a-z0-9][a-z0-9-]*$")
STUDLY_RE = re.compile(r"^[A-Z][A-Za-z0-9]*$")
RESERVED = {"Framework", "Tests", "Tools", "Illuminate", "App", "Database"}
SAMPLE_MODULE = "Health"


class InputError(Exception):
    pass


def studly(text):
    return "".join(part[:1].upper() + part[1:] for part in re.split(r"[^A-Za-z0-9]+", text) if part)


def substitute(text, tokens):
    for key, value in tokens.items():
        text = text.replace(key, value)
    return text


def substitute_file_text(name, text, tokens):
    """JSON files get JSON-escaped values, so a quote in the pitch cannot break them."""
    if not name.endswith(".json"):
        return substitute(text, tokens)
    escaped = {k: json.dumps(v, ensure_ascii=False)[1:-1] for k, v in tokens.items()}
    return substitute(text, escaped)


def copy_tree(source, target, tokens):
    for root, dirs, files in os.walk(source):
        dirs[:] = sorted(d for d in dirs if d != "__pycache__")
        rel_root = os.path.relpath(root, source)
        dest_root = target if rel_root == "." else os.path.join(target, substitute(rel_root, tokens))
        os.makedirs(dest_root, exist_ok=True)
        for name in sorted(files):
            src = os.path.join(root, name)
            dest = os.path.join(dest_root, substitute(name, tokens))
            try:
                with open(src, encoding="utf-8") as f:
                    text = f.read()
            except UnicodeDecodeError:
                shutil.copy2(src, dest)
                continue
            with open(dest, "w", encoding="utf-8") as f:
                f.write(substitute_file_text(name, text, tokens))
            shutil.copymode(src, dest)


def add_modules_to_list(target, modules):
    path = os.path.join(target, "modules.php")
    with open(path, encoding="utf-8") as f:
        text = f.read()
    pattern = re.compile(r"(    'modules' => \[\n(?:        '[A-Za-z0-9]+',\n)*)(    \],)")
    if not pattern.search(text):
        raise InputError("modules.php in the template has an unexpected shape")
    lines = "".join(f"        '{m}',\n" for m in modules)
    with open(path, "w", encoding="utf-8") as f:
        f.write(pattern.sub(lambda m: m.group(1) + lines + m.group(2), text, count=1))


def content_hash(composer):
    """Composer's lock content-hash: md5 of the JSON of the keys that affect resolution."""
    keys = ["name", "version", "require", "require-dev", "conflict", "replace", "provide",
            "minimum-stability", "prefer-stable", "repositories", "extra"]
    relevant = {k: composer[k] for k in keys if k in composer}
    platform = composer.get("config", {}).get("platform")
    if platform:
        relevant["config"] = {"platform": platform}
    encoded = json.dumps(dict(sorted(relevant.items())), separators=(",", ":"))
    return hashlib.md5(encoded.replace("/", "\\/").encode("utf-8")).hexdigest()


def refresh_lock_hash(target):
    lock_path = os.path.join(target, "composer.lock")
    if not os.path.isfile(lock_path):
        return
    with open(os.path.join(target, "composer.json"), encoding="utf-8") as f:
        composer = json.load(f)
    with open(lock_path, encoding="utf-8") as f:
        lock = f.read()
    new = re.sub(r'("content-hash": ")[0-9a-f]+(")', lambda m: m.group(1) + content_hash(composer) + m.group(2), lock, count=1)
    with open(lock_path, "w", encoding="utf-8") as f:
        f.write(new)


def leftover_tokens(target):
    found = []
    for root, dirs, files in os.walk(target):
        dirs[:] = [d for d in dirs if d not in (".git", "vendor", "node_modules")]
        for name in dirs + files:
            if TOKEN_RE.search(name):
                found.append(os.path.relpath(os.path.join(root, name), target))
        for name in files:
            path = os.path.join(root, name)
            try:
                with open(path, encoding="utf-8") as f:
                    if TOKEN_RE.search(f.read()):
                        found.append(os.path.relpath(path, target))
            except UnicodeDecodeError:
                pass
    return sorted(set(found))


def parse_modules(raw, namespace):
    if not raw:
        return []
    modules = []
    for item in (part.strip() for part in raw.split(",")):
        if not STUDLY_RE.match(item):
            raise InputError(f"module name {item!r} must be StudlyCase letters and digits")
        if item in RESERVED or item == SAMPLE_MODULE:
            raise InputError(f"module name {item!r} is reserved or already in the template")
        if item.lower() in (m.lower() for m in modules):
            raise InputError(f"module name {item!r} is listed twice")
        modules.append(item)
    return modules


def validate(args):
    if not args.name.strip() or any(c in args.name for c in '"\\$\n\r'):
        raise InputError("--name must be non-empty and contain no double quote, backslash, dollar sign or newline")
    if not SLUG_RE.match(args.slug):
        raise InputError("--slug must be lowercase letters, digits and hyphens, for example sample-product")
    if "\n" in args.pitch or "\r" in args.pitch:
        raise InputError("--pitch must be one line")
    try:
        datetime.date.fromisoformat(args.date)
    except ValueError:
        raise InputError(f"--date must be YYYY-MM-DD, got {args.date!r}")
    namespace = args.namespace or studly(args.name)
    if not STUDLY_RE.match(namespace) or namespace in RESERVED:
        raise InputError(f"namespace {namespace!r} must be StudlyCase letters and digits and not reserved; pass --namespace")
    return namespace, parse_modules(args.modules, namespace)


def main():
    ap = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    ap.add_argument("--name", required=True, help="product name, e.g. 'Sample Product'")
    ap.add_argument("--slug", required=True, help="product slug, e.g. sample-product")
    ap.add_argument("--pitch", required=True, help="one-line product pitch")
    ap.add_argument("--target", required=True, help="directory to create the repository in")
    ap.add_argument("--namespace", help="PHP namespace and folder for the product modules, default StudlyCase of --name")
    ap.add_argument("--modules", help="comma-separated module names to scaffold, e.g. Orders,Billing")
    ap.add_argument("--date", default=datetime.date.today().isoformat(), help="YYYY-MM-DD, default today")
    ap.add_argument("--no-git", action="store_true", help="skip git init and the first commit")
    args = ap.parse_args()

    try:
        namespace, modules = validate(args)
    except InputError as e:
        print(f"error: {e}", file=sys.stderr)
        return 2
    target = os.path.abspath(args.target)
    if os.path.exists(target) and (not os.path.isdir(target) or os.listdir(target)):
        print(f"error: {target} exists and is not an empty directory; refusing to write into it", file=sys.stderr)
        return 2

    tokens = {"{{PRODUCT_NAME}}": args.name, "{{PRODUCT_SLUG}}": args.slug,
              "{{PRODUCT_PITCH}}": args.pitch, "{{PRODUCT_NAMESPACE}}": namespace,
              "{{DATE}}": args.date}
    os.makedirs(target, exist_ok=True)
    copy_tree(TEMPLATE, target, tokens)
    for module in modules:
        module_tokens = {**tokens, "{{MODULE}}": module, "{{MODULE_LOWER}}": module.lower()}
        copy_tree(MODULE_TEMPLATE, target, module_tokens)
    if modules:
        add_modules_to_list(target, modules)
    refresh_lock_hash(target)

    left = leftover_tokens(target)
    if left:
        print("error: unreplaced {{ tokens remain in:", file=sys.stderr)
        for rel in left:
            print(f"  {rel}", file=sys.stderr)
        return 2

    if not args.no_git:
        def git(*a):
            subprocess.run(["git", *a], cwd=target, check=True, stdout=subprocess.DEVNULL)
        git("init", "-b", "main")
        git("add", "-A")
        git("commit", "-m", f"Create the {args.name} repo from the Laravel template")

    print(f"Created {target}")
    print(f"Modules: {', '.join([SAMPLE_MODULE, *modules])}")
    print("Next: run the setup and check commands in .factory/recipe.yml")
    return 0


if __name__ == "__main__":
    sys.exit(main())
