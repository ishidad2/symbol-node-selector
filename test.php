<?php

require __DIR__ . '/vendor/autoload.php';

use Ishidad2\SymbolNodeSelector\SymbolNodeSelector;

$selector = new SymbolNodeSelector();

echo "Mainnet Node: " . $selector->getActiveMainnetNode();
echo "Testnet Node: " . $selector->getActiveTestnetNode();