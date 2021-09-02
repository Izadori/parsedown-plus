# ParsedownPlus

Parsedown/ParsedownExtra用の拡張ライブラリです。

## 特長

1. 目次の自動作成
   1. 本文中に目次タグを指定することで自動作成した目次を挿入します。
2. LaTeX形式の数式に対応
   1. `$ ... $`, `$$ ... $$`で囲まれた部分をLaTeX形式の数式と認識し、Markdownのパースを行いません。
   2. `$ ... $`はインライン形式、`$$ ... $$`はブロック形式として認識します。
3. コードブロックのファイル名を認識
   1. コードフェンス後の` ```lang:file ... ``` `を認識し、`<code>`の`data-filename`属性にファイル名を展開します。

## 使用方法

### Composerによる方法

composerを使用する場合は、プロジェクトフォルダへ移動し、次のコマンドを入力します。  

```bash
composer require izadori/parsedown-plus
```

`Parsedown`がインストールされていない場合は、一緒にインストールされます。`ParsedownExtra`が必要な場合は、手動でインストールしてください。

```bash
composer require erusev/parsedown-extra
```

`ParsedownPlus`を使用するために、PHPのソース内で`autoload.php`をrequireしてください。

```php
require_once __DIR__ . "/vender/autoload.php";

use \Izadori\ParsedownPlus\ParsedownPlus;

$parser = new ParsedownPlus();

$text = <<<EOF
# Equation of _Circle_

$ x_{1}^{2} + x_{2}^{2} = 1 $
EOF;

$line = "It's **inline** text!";

// Markdown形式のテキスト全体をパースします
echo $parser->text($text); // prints: <h1>Equation of <em>Circle</em></h1> <p>$  x_{1}^{2} + x_{2}^{2} = 1  $</p>
// 行内の一部のMarkdownテキストをパースします
echo $parser->line($line); // prints: It's <strong>inline</strong> text!
```

### Composerを使わない方法

Composerを使用しない場合は、[こちら](https://izadori.github.io/...)から`ParsedownPlus`のソースコードをプロジェクト内にダウンロードしてください。

`ParsedownPlus`を使用するために、まずPHPのソース内で`Parsedown.php`と、必要であれば`ParsedownExtra.php`をrequireします。それから`ParsedownPlus.php`をrequireしてください。

```php
require __DIR__ . "/Parsedown.php";
require __DIR__ . "/ParsedownExtra.php"; // 必要であれば
require __DIR__ . "/ParsedownPlus.php";

use /Izadori;

$parser = new ParsedownPlus();

$text = <<<EOF
# Equation of _Circle_

$ x_{1}^{2} + x_{2}^{2} = 1 $
EOF;

$line = "It's **inline** text!";

// Markdown形式のテキスト全体をパースします
echo $parser->text($text); // prints: <h1>Equation of <em>Circle</em></h1> <p>$  x_{1}^{2} + x_{2}^{2} = 1  $</p>
// 行内の一部のMarkdownテキストをパースします
echo $parser->line($line); // prints: It's <strong>inline</strong> text!
```

### オプション設定

`ParsedownPlus`はオプション設定用にいくつかの`public`メンバ変数を持ちます。

|メンバ変数|説明|
|:-:|:--|
|`$langPrefix`|コードフェンスで指定される言語名を表すクラスに付加する先頭文字列です。<br>デフォルトは`prism.js`用の`language-`です。|
|`$tocTag`|`'begin'`と`'end'`の2つのメンバを持つ連想配列です。<br>目次として認識する見出しタグの開始レベルと終了レベルを数値で指定します。|
|`$tocIdentTag`|Markdown本文中で目次を挿入する疑似タグを文字列の配列で指定します。|
|`$tocFormat`|生成された目次([^1](#目次の生成について))を含む書式を指定します。<br>`sprintf()`を使っているので、目次に置き換える部分を`%s`で指定してください。|

### 目次の生成について

`ParsedownPlus`が生成する目次は次のようなordered-listです。

```html
<ol>
  <li>見出し1</li>
  <li>見出し2</li>
    <ol>
      <li>見出し2.1</li>
      <li>見出し2.2</li>
    </ol>
  <li>見出し3</li>
</ol>
```

## バグについて

バグが見つかった場合は、Github Project上の[issue](https://github.com/izadori/parsedown-plus/issues/new)に、バグの内容とバグが起こったMarkdownテキストをお知らせください。

## ライセンスについて

`ParsedownPlus`は[MITライセンス](http://opensource.org/licenses/MIT)に準拠します。詳細は[LICENSEファイル](https://github.com/izadori/parsedown-plus/LICENSE)を参照してください。

## 作者について

- いざどり
  - [Github](https://github.com/izadori/parsedown-plus/)
  - [ウェブサイト](https://izadori.net/)
  - [E-mail](mailto:izadori.trial.and.error@gmail.com)
  - [Twitter](https://twitter.com/izadori97362)

---

## 履歴

### ParsedownPlus.php

1. __2021.09.02__ [_ver.1.0.0_]
   - Github上に公開
2. __2021.09.02__ [_ver.1.0.1_]
   - ParsedownExtraでうまく動作しないのを修正。
   - コードフェンスの言語名を示すクラスの先頭文字列を変更できるようにした。

### このドキュメント

1. __2021.09.02__
   - ParsedownPlus ver.1.0.1に合わせて記述を追加。
   - Github上に公開
