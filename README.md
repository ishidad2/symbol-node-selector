# Symbol Node Selector

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)


## 概要
Symbol Node Selector は、Symbol の **アクティブなノードを選択して返す** PHP ライブラリです。 

テストネット・メインネットのノードリストを管理し、負荷分散や障害時のフェールオーバーを容易にします。

## インストール

Composer を使用してインストールできます。

```sh
composer require ishidad2/symbol-node-selector
```

Laravel で使用する場合は、設定ファイルを公開してください。

```sh
php artisan vendor:publish --tag=config
```

## 設定
`config/symbolnodeselector.php` にテストネット・メインネットのノードリストが定義されています。

```php
return [
    'testnet_symbol_nodes' => [
        'https://sym-test-01.opening-line.jp:3001',
        'https://sym-test-03.opening-line.jp:3001',
        'https://001-sai-dual.symboltest.net:3001',
    ],
    'mainnet_symbol_nodes' => [
        'https://yukikaze.symbol-nem.net:3001',
        'https://symbol-main-1.nemtus.com:3001',
        'https://xym1.kyoto-japan.cloud:3001',
    ],
];
```

## 使い方

アクティブなノードを取得する例:

```php
use Ishidad2\SymbolNodeSelector\SymbolNodeSelector;

$selector = new SymbolNodeSelector();

echo "Mainnet Node: " . $selector->getActiveMainnetNode();
echo "Testnet Node: " . $selector->getActiveTestnetNode();
```

## テスト

PHPUnit を使用してテストを実行できます。

```sh
vendor/bin/phpunit --testdox
```

## ライセンス

このライブラリは [MIT License](https://opensource.org/licenses/MIT) のもとで公開されています。

## 貢献

バグ報告や機能追加の提案は GitHub の Issue にてお願いします。

```sh
git clone https://github.com/ishidad2/symbol-node-selector.git
cd symbol-node-selector
composer install
```

