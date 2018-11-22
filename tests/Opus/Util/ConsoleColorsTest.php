<?php

class Opus_Util_ConsoleColorsTest extends SimpleTestCase
{

    public function testGetForegroundColors()
    {
        $colors = new Opus_Util_ConsoleColors();

        $foreground = $colors->getForegroundColors();

        $this->assertCount(16, $foreground);
        $this->assertContains('light_purple', $foreground);
        $this->assertContains('white', $foreground);
    }

    public function testGetBackgroundColors()
    {
        $colors = new Opus_Util_ConsoleColors();

        $background = $colors->getBackgroundColors();

        $this->assertCount(8, $background);
        $this->assertContains('black', $background);
        $this->assertContains('magenta', $background);
    }

    public function testMagicCallMethod()
    {
        $colors = new Opus_Util_ConsoleColors();

        $colored = $colors->blue('Hello, world!');

        $this->assertEquals("\033[0;34mHello, world!\033[0m", $colored);

        $colored = $colors->lightPurple('Hello, world!');

        $this->assertEquals("\033[1;35mHello, world!\033[0m", $colored);
    }

    public function testMagicCallMethodWithBackgroundColor()
    {
        $colors = new Opus_Util_ConsoleColors();

        $output = $colors->blue('Hello, world!', 'green');

        $this->assertEquals("\033[0;34m\033[42mHello, world!\033[0m", $output);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Unknown function 'pink'
     */
    public function testMagicCallMethodUnknownColor()
    {
        $colors = new Opus_Util_ConsoleColors();

        $output = $colors->pink('Hello, world!');
    }

    public function testMagicCallMethodWithUnkownBackgroundColor()
    {
        $colors = new Opus_Util_ConsoleColors();

        $output = $colors->blue('Hello, world!', 'pink');

        $this->assertEquals("\033[0;34mHello, world!\033[0m", $output);
    }

    public function testMagicCallMethodWithoutArguments()
    {
        $colors = new Opus_Util_ConsoleColors();

        $output = $colors->green();

        $this->assertEquals('', $output);
    }

    public function testGetColoredString()
    {
        $colors = new Opus_Util_ConsoleColors();

        $output = $colors->getColoredString('Hello, world!', Opus_Util_ConsoleColors::RED);

        $this->assertEquals("\033[0;31mHello, world!\033[0m", $output);
    }

    public function testGetColoredStringWithBackground()
    {
        $colors = new Opus_Util_ConsoleColors();

        $output = $colors->getColoredString(
            "Hello, world!",
            Opus_Util_ConsoleColors::RED,
            Opus_Util_ConsoleColors::BLUE
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
            Opus_Util_ConsoleColors::RED,
            Opus_Util_ConsoleColors::RED
        );

        $this->assertEquals("\033[0;31m\033[41mHello, world!\033[0m", $output);
    }

    public function testGetColoredStringWithUnknownColor()
    {
        $colors = new Opus_Util_ConsoleColors();

        $output = $colors->getColoredString('Hello, world!', 'orange');

        $this->assertEquals('Hello, world!', $output);
    }

    public function testGetColoredStringWithoutMessage()
    {
        $colors = new Opus_Util_ConsoleColors();

        $output = $colors->getColoredString(null);

        $this->assertEquals('', $output);
    }

    public function testGetColoredStringWithoutColors()
    {
        $colors = new Opus_Util_ConsoleColors();

        $output = $colors->getColoredString('Hello, world!');

        $this->assertEquals('Hello, world!', $output);
    }

    public function testGetColoredStringWithCode()
    {
        $colors = new Opus_Util_ConsoleColors();

        $output = $colors->getColoredString('Hello, world!', '0;35', '42');

        $this->assertEquals("\033[0;35m\033[42mHello, world!\033[0m", $output);
    }
}
