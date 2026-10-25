<?php

class MarkdownHtmlBlockTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * Inputs made of many unclosed block-level HTML tags, each alone on its
	 * line. The HTML-block regex used to explore an exponential number of ways
	 * to pair these up, so a few hundred bytes either exhausted
	 * pcre.backtrack_limit (wiping the whole document to an empty string) or,
	 * with the limit raised, took time doubling about every 8 tags.
	 */
	public function unclosedBlocks(): array
	{
		return array(
			'div, blank lines' => array(str_repeat("<div>\n\n", 2000)),
			'div, single lines' => array(str_repeat("<div>\n", 2000)),
			'ins (group a)' => array(str_repeat("<ins>\n\n", 2000)),
			'del (group a)' => array(str_repeat("<del>\n", 2000)),
			'section' => array(str_repeat("<section>\n\n", 2000)),
			'attr with > in quotes' => array(str_repeat("<div class=\"a>b\">\n\n", 2000)),
		);
	}

	/**
	 * @dataProvider unclosedBlocks
	 */
	public function testUnclosedBlocksDoNotWipeOutput(string $prefix)
	{
		$html = (new \Michelf\Markdown())->transform($prefix . "the trailing paragraph\n");
		// Before the fix this came back as "" under the default backtrack
		// limit, or took seconds to minutes with the limit raised.
		$this->assertStringContainsString('the trailing paragraph', $html);
	}

	/**
	 * Well-formed nested block tags (up to the parser's supported depth) must
	 * still be recognised as a single HTML block and passed through verbatim.
	 */
	public function testWellFormedNestedBlocksAreUnchanged()
	{
		$html = "<div>\n<div>\n<div>\n<div>\nhello\n</div>\n</div>\n</div>\n</div>";

		$result = (new \Michelf\Markdown())->transform($html);

		// The whole block is emitted as-is, not wrapped in <p> and not escaped.
		$this->assertStringContainsString("<div>\nhello\n</div>", $result);
		$this->assertStringNotContainsString('<p>', $result);
		$this->assertStringNotContainsString('&lt;', $result);
	}
}
