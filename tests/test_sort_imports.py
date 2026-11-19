"""Unit tests for the import sorter in new-repo.py (mirrors Pint's ordered_imports)."""
import importlib.util, os, unittest

REPO = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
spec = importlib.util.spec_from_file_location("new_repo", os.path.join(REPO, "scripts", "new-repo.py"))
new_repo = importlib.util.module_from_spec(spec)
spec.loader.exec_module(new_repo)
sort = new_repo.sort_imports

HEAD = "<?php\n\ndeclare(strict_types=1);\n\nnamespace X;\n\n"


class SortImportsTest(unittest.TestCase):
    def test_namespace_after_illuminate(self):
        before = HEAD + "use Zebra\\Health\\Thing;\nuse Illuminate\\Support\\ServiceProvider;\n\nfinal class A {}\n"
        after = HEAD + "use Illuminate\\Support\\ServiceProvider;\nuse Zebra\\Health\\Thing;\n\nfinal class A {}\n"
        self.assertEqual(sort(before), after)

    def test_namespace_before_illuminate(self):
        before = HEAD + "use Illuminate\\Support\\ServiceProvider;\nuse Aardvark\\Health\\Thing;\n"
        after = HEAD + "use Aardvark\\Health\\Thing;\nuse Illuminate\\Support\\ServiceProvider;\n"
        self.assertEqual(sort(before), after)

    def test_case_insensitive_and_backslash_as_space(self):
        # "Foo Bar" (Foo\Bar) sorts before "FooBar" because a space sorts before a letter.
        before = HEAD + "use FooBar;\nuse Foo\\Bar;\nuse foo\\Aaa;\n"
        after = HEAD + "use foo\\Aaa;\nuse Foo\\Bar;\nuse FooBar;\n"
        self.assertEqual(sort(before), after)

    def test_groups_are_const_then_class_then_function(self):
        before = HEAD + "use function A\\fn;\nuse B\\Klass;\nuse const C\\VALUE;\nuse A\\Other;\n"
        after = HEAD + "use const C\\VALUE;\nuse A\\Other;\nuse B\\Klass;\nuse function A\\fn;\n"
        self.assertEqual(sort(before), after)

    def test_blank_lines_inside_a_run_keep_their_place(self):
        before = HEAD + "use B\\X;\n\nuse A\\X;\n"
        self.assertEqual(sort(before), HEAD + "use A\\X;\n\nuse B\\X;\n")

    def test_separate_blocks_sort_independently(self):
        before = "use B\\X;\nuse A\\X;\n\nclass K {}\n\nuse D\\X;\nuse C\\X;\n"
        self.assertEqual(sort(before), "use A\\X;\nuse B\\X;\n\nclass K {}\n\nuse C\\X;\nuse D\\X;\n")

    def test_alias_and_non_imports_are_left_alone(self):
        text = HEAD + "use A\\B as C;\n\nfinal class K\n{\n    use Z\\Trait;\n    use A\\Trait;\n\n    public function f(): void\n    {\n        $x = static function () use ($y) {};\n    }\n}\n"
        self.assertEqual(sort(text), text)

    def test_sorted_input_is_unchanged_and_idempotent(self):
        text = HEAD + "use A\\X;\nuse B\\Y;\n"
        self.assertEqual(sort(text), text)
        self.assertEqual(sort(sort(text)), sort(text))


if __name__ == "__main__":
    unittest.main()
