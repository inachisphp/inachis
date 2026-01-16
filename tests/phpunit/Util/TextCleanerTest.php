<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Util;

use Inachis\Util\TextCleaner;
use PHPUnit\Framework\TestCase;

class TextCleanerTest extends TestCase
{
    protected TextCleaner $cleaner;

    protected string $example = '';

    public function setUp(): void
    {
        $this->cleaner = new TextCleaner();
        $this->example = <<<MD
<p><a href="" title="don't keep this">example</a> of <strong>HTML</strong></p>
Which *mistakenly* an > ![image](https://www.example.com/image.jpg) and [link](https://www.example.com)

> A blockquote here
> second line

```
code block
```

some `inline code`

- this
- is

* a 
* list

__more__ text
MD;

        parent::setUp();
    }

    public function testStripDefault(): void
    {
        $result = $this->cleaner->strip($this->example);
        $this->assertEquals(<<<MD
example of HTML
Which mistakenly an > image and link

A blockquote here
second line

code block

some inline code

this
is

a 
list

more text
MD, $result);
    }

    public function testStripRemoveBlockquote(): void
    {
        $result = $this->cleaner->strip($this->example, TextCleaner::REMOVE_BLOCKQUOTE_CONTENT);
        $this->assertEquals(<<<MD
example of HTML
Which mistakenly an > image and link


code block

some inline code

this
is

a 
list

more text
MD, $result);
    }

    public function testStripImageAlt(): void
    {
        $result = $this->cleaner->strip($this->example, TextCleaner::REMOVE_IMAGE_ALT);
        $this->assertEquals(<<<MD
example of HTML
Which mistakenly an >  and link

A blockquote here
second line

code block

some inline code

this
is

a 
list

more text
MD, $result);
    }

    public function testStripNormaliseWhitespace(): void
    {
        $result = $this->cleaner->strip($this->example, TextCleaner::NORMALISE_WHITESPACE);
        $this->assertEquals(<<<MD
example of HTML
Which mistakenly an > image and link
A blockquote here
second line
code block
some inline code
this
is
a 
list
more text
MD, $result);
    }

    public function testStripAll(): void
    {
        $result = $this->cleaner->strip(
            $this->example,
            TextCleaner::NORMALISE_WHITESPACE | TextCleaner::REMOVE_IMAGE_ALT | TextCleaner::REMOVE_BLOCKQUOTE_CONTENT
        );
        $this->assertEquals(<<<MD
example of HTML
Which mistakenly an > and link
code block
some inline code
this
is
a 
list
more text
MD, $result);
    }
}
