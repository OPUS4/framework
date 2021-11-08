<?php

namespace OpusTest\Util;

use Exception;
use Opus\Util\ConsoleColors;
use OpusTest\TestAsset\AbstractSimpleTestCase;

class ConsoleColorsTest extends AbstractSimpleTestCase
{
    protected $colors;

    public function setUp()
    {
        parent::setUp();

        $this->colors = new ConsoleColors();
    }

    public function testGetForegroundColors()
    {
        $colors = $this->colors;

        $foreground = $colors->getForegroundColors();

        $this->assertCount(16, $foreground);
        $this->assertContains('light_purple', $foreground);
        $this->assertContains('white', $foreground);
    }

    public function testGetBackgroundColors()
    {
        $colors = $this->colors;

        $background = $colors->getBackgroundColors();

        $this->assertCount(8, $background);
        $this->assertContains('black', $background);
        $this->assertContains('magenta', $background);
    }

    public function testMagicCallMethod()
    {
        $colors = $this->colors;

        $colored = $colors->blue('Hello, world!');

        $this->assertEquals("\033[0;34mHello, world!\033[0m", $colored);

        $colored = $colors->lightPurple('Hello, world!');

        $this->assertEquals("\033[1;35mHello, world!\033[0m", $colored);
    }

    public function testMagicCallMethodWithBackgroundColor()
    {
        $colors = $this->colors;

        $output = $colors->blue('Hello, world!', 'green');

        $this->assertEquals("\033[0;34m\033[42mHello, world!\033[0m", $output);
    }

    public function testMagicCallMethodUnknownColor()
    {
        $colors = $this->colors;

        $this->setExpectedException(Exception::class, 'Unknown function \'pink\'');

        $colors->pink('Hello, world!');
    }

    public function testMagicCallMethodWithUnkownBackgroundColor()
    {
        $colors = $this->colors;

        $output = $colors->blue('Hello, world!', 'pink');

        $this->assertEquals("\033[0;34mHello, world!\033[0m", $output);
    }

    public function testMagicCallMethodWithoutArguments()
    {
        $colors = $this->colors;

        $output = $colors->green();

        $this->assertEquals('', $output);
    }

    public function testGetColoredString()
    {
        $colors = $this->colors;

        $output = $colors->getColoredString('Hello, world!', ConsoleColors::RED);

        $this->assertEquals("\033[0;31mHello, world!\033[0m", $output);
    }

    public function testGetColoredStringWithBackground()
    {
        $colors = $this->colors;

        $output = $colors->getColoredString(
            "Hello, world!",
            ConsoleColors::RED,
            ConsoleColors::BLUE
        );

        $this->assertEquals("\033[0;31m\033[44mHello, world!\033[0m", $output);

        $output = $colors->getColoredString(
            "Hello, world!",
            'red',
            'blue'
        );

        $this->assertEquals("\033[0;31m\033[44mHello, world!\033[0m", $output);

        $output = $colors->getColoredString(
            "Hello, world!",
            ConsoleColors::RED,
            ConsoleColors::RED
        );

        $this->assertEquals("\033[0;31m\033[41mHello, world!\033[0m", $output);
    }

    public function testGetColoredStringWithUnknownColor()
    {
        $colors = $this->colors;

        $output = $colors->getColoredString('Hello, world!', 'orange');

        $this->assertEquals('Hello, world!', $output);
    }

    public function testGetColoredStringWithoutMessage()
    {
        $colors = $this->colors;

        $output = $colors->getColoredString(null);

        $this->assertEquals('', $output);
    }

    public function testGetColoredStringWithoutColors()
    {
        $colors = $this->colors;

        $output = $colors->getColoredString('Hello, world!');

        $this->assertEquals('Hello, world!', $output);
    }

    public function testGetColoredStringWithCode()
    {
        $colors = $this->colors;

        $output = $colors->getColoredString('Hello, world!', '0;35', '42');

        $this->assertEquals("\033[0;35m\033[42mHello, world!\033[0m", $output);
    }
}
