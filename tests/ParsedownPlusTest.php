<?php

require_once "../vendor/autoload.php";

use PHPUnit\Framework\TestCase;
use Izadori\ParsedownPlus\ParsedownPlus;

class ParsedownPlusTest extends TestCase
{
  public function testText()
  {
    $parser = new ParsedownPlus();
    $this->assertSame($parser->text("# header1"), "<h1>header1</h1>");
  }
}

$a = new ParsedownPlusTest();
$a->testText();
?>
