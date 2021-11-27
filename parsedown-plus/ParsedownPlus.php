<?php

namespace Izadori\ParsedownPlus;

// Make it compatible with ParsedownExtra
//   - Feature Implementation from Issue #13 by @qwertygc
//     https://github.com/KEINOS/parsedown-extension_table-of-contents/issues/13
if (class_exists('ParsedownExtra')) {
  class DynamicParent extends \ParsedownExtra
  {
    // Required Parsedown version
    public const REQUIRED_PARSEDOWN_VERSION = '0.8';

    public function __construct()
    {
      parent::__construct();
    }
  }
}
else {
  class DynamicParent extends \Parsedown
  {
    // Required Parsedown version
    public const REQUIRED_PARSEDOWN_VERSION = '1.7';

    public function __construct()
    {
      //
    }
  }
}


//
// ParsedownPlus: An extension for Parsedown/ParsedownExtra
//
class ParsedownPlus extends DynamicParent
{
  // Version
  public const PARSEDOWNPLUS_VERSION = '1.1.1';

  //
  // public member variabls: ParsedownPlus options
  //

  // prefix of laguage type in fenced code
  public $langPrefix = "language-";
  // beginning and end of header tag levels
  public $tocTag = array(
    'begin' => 2, // "h2"
    'end' => 4    // "h4"
  );
  // recognizing tags as table-of-contents in markdown text
  public $tocIdentTag = array(
    '<!-- toc -->',
    '[toc]',
    '[:contents]',
  );
  // template of table-of-contents
  // (ParsedownPlus uses sprintf() to generate table-of-contents. "%s" is required in format string.)
  public $tocFormat = "<div class=\"toc\">\n%s\n</div>";

  //
  // protected member variables
  //

  // generated table-of-contents string
  protected $tocText;
  // header tag list
  protected $tocTagList;
  // last header tag level (relative)
  protected $tocLastIndex;
  // first table-of-contents tag
  protected $tocString;
  // placeholder
  protected $tocPlaceholder;
  // extracted title
  protected $title;
  // title has been found?
  protected $isTitleFound;

  //
  // Constructor
  //
  public function __construct()
  {
    parent::__construct();

    if(version_compare(parent::version, self::REQUIRED_PARSEDOWN_VERSION) < 0) {
      throw new \Exception('ParsedownPlus: '.(
        class_exists('ParsedownExtra') ? "ParsedownExtra" : "Parsedown"
        ).' version unmatched. Version '.parent::REQUIRED_PARSEDOWN_VERSION.' or later is required.');
    }

    // for LaTex math
    $this->BlockTypes['$'] = ['Math'];
    $this->InlineTypes['$'] = ['Math'];
    $this->inlineMarkerList .= '$';
    $this->specialCharacters[] = '$';
  }

  //
  // get generated table-of-contents
  //
  public function getToc()
  {
    return $this->tocText;
  }

  //
  // get extracted title
  //
  public function getTitle()
  {
    return $this->title;
  }

  //
  // Parsing markdown: overriding to add function of table-of-contents
  //
  public function text($text)
  {
    $this->tocText = "";
    $this->tocLastIndex = -1;
    $this->tocString = null;
    $this->tocTagList = [];
    $this->title = "";
    $this->isTitleFound = false;

    if($this->tocTag['begin'] <= 0 || $this->tocTag['begin'] > 5){
      throw new \Exception('$tocTag["begin"] is out of range.');
    }
    elseif($this->tocTag['end'] <= 0 || $this->tocTag['end'] > 6){
      throw new \Exception('$tocTag["end"] is out of range.');
    }
    elseif($this->tocTag['begin'] > $this->tocTag['end']) {
      list(
        $this->tocTag['begin'], $this->tocTag['end']
      ) = array(
        $this->tocTag['end'], $this->tocTag['begin']
      );
    }

    // generate header tag list to make table-of-contents
    foreach(range($this->tocTag['begin'], $this->tocTag['end']) as $number) {
      $this->tocTagList[] = 'h'.$number;
    }

    // search first table-of-contents tag
    $pos = strlen($text) + 1;
    foreach($this->tocIdentTag as $tag) {
      $tmpPos = stripos($text, $tag);
      if($tmpPos !== false && $pos > $tmpPos){
        $pos = $tmpPos;
        $this->tocString = $tag;
      }
    }

    // replace table-of-contents tag to placeholder
    if($this->tocString !== null) {
      $this->tocPlaceholder = '<'.\bin2hex(random_bytes(4)).' />';
      $text = str_replace($this->tocString, $this->tocPlaceholder, $text);
    }

    // start parsing markdown and generating table-of-contentes
    $html = parent::text($text);

    while($this->tocLastIndex >= 0) {
      $this->tocText .= "</ol>\n";
      $this->tocLastIndex--;
    }

    // replace placeholder to generated table-of-contents HTML
    if($this->tocString !== null) {
      $this->tocText = sprintf($this->tocFormat, chop($this->tocText));
      $html = str_replace($this->tocPlaceholder, $this->tocText, $html);
    }

    return $html;
  }

  //
  // inline LaTeX math expression($ ... $)
  //
  protected function inlineMath($Excerpt)
  {
    $marker = '\\'.$Excerpt['text'][0];
    $pattern = '/^('.$marker.')([ ]*[^'.$marker.']+[ ]*)\1/s';

    if (preg_match($pattern, $Excerpt['text'], $matches))
    {
      $text = $matches[2];
      $text = preg_replace("/[ ]*\n/", ' ', $text);

      return array(
        'markup' => $matches[1].$text.$matches[1],
        'extent' => strlen($matches[0]),
      );
    }
  }

  //
  // beginning of block for LaTeX math expression($$ ... $$)
  //
  protected function blockMath($Line)
  {
    $pattern = '/^[\\'.$Line['text'][0].']{2,2}[ ]*([^\\$]+)?[ ]*$/';

    if (\preg_match($pattern, $Line['text'], $matches)) {
      $Block = array(
        'char' => $Line['text'][0],
        'element' => array(
          'name' => 'p',
          'text' => $Line['text'],
          'attributes' => array(
            'class' => 'block-math'
          )
        ),
      );

      return $Block;
    }
  }

  //
  // block LaTeX math expression($$ ... $$)
  //
  protected function blockMathContinue($Line, $Block)
  {
    if (isset($Block['complete'])) {
      return;
    }

    if (isset($Block['interrupted'])) {
      $Block['element']['text'] .= "\n";

      unset($Block['interrupted']);
    }

    if (preg_match('/^\\'.$Block['char'].'{2,2}.*$/', $Line['text'])) {
      $Block['element']['text'] .= "\n".$Line['text'];

      $Block['complete'] = true;

      return $Block;
    }

    $Block['element']['text'] .= "\n".$Line['body'];

    return $Block;
  }

  //
  // end of block for LaTeX math expression($$ ... $$)
  //
  protected function blockMathComplete($Block)
  {
    return $Block;
  }

  //
  // Fenced code: overriding to add function to show file name.
  // (```lang:filename ... ```)
  //
  protected function blockFencedCode($Line)
  {
    if (preg_match('/^['.$Line['text'][0].']{3,}[ ]*([^`]+)?[ ]*$/', $Line['text'], $matches)) {
      $Element = array(
        'name' => 'code',
        'text' => '',
      );

      if (isset($matches[1])) {
        $pos = strcspn($matches[1], ": \t\n\f\r");
        $language = substr($matches[1], 0, $pos);
        $filename = substr($matches[1], $pos + 1);

        if($filename === "") {
          $filename = null;
        }

        $class = $this->langPrefix.$language;

        $Element['attributes'] = array(
          'class' => $class,
          'data-filename' => $filename
        );
      }

      $Block = array(
        'char' => $Line['text'][0],
        'element' => array(
          'name' => 'pre',
          'handler' => 'element',
          'text' => $Element,
/*
          'attributes' => array(
            'class' => $class,
            'data-filename' => $filename
          )
*/
        ),
      );

      return $Block;
    }
  }

  //
  // Header: overriding to add funtion of making table-of-contentes.
  //
  protected function blockHeader($Line)
  {
    $Block = parent::blockHeader($Line);

    if($Block['element']['name'] === 'h1' && !$this->isTitleFound){
      $this->title = $Block['element']['text'];
      $this->isTitleFound = true;
    }

    $index = array_search($Block['element']['name'], $this->tocTagList);

    if($index !== false) {
      $text = $Block['element']['text'];
      $id = preg_replace("/\\s/", '_', $text);

      if($index > $this->tocLastIndex) {
        $tmpIndex = $index;
        while($tmpIndex > $this->tocLastIndex) {
          $this->tocText .= "<ol>\n";
          $tmpIndex--;
        }
      }
      else if($index < $this->tocLastIndex) {
        $tmpIndex = $index;
        while($tmpIndex < $this->tocLastIndex) {
          $this->tocText .= "</ol>\n";
          $tmpIndex++;
        }
      }

      $this->tocLastIndex = $index;
      $this->tocText .= '<li><a href="#'.$id.'">'.$text."</a></li>\n";
      $Block['element']['attributes']['id'] = $id;
    }

    return $Block;
  }
}
?>
