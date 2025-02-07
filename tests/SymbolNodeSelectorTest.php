<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\DataProvider;
use Ishidad2\SymbolNodeSelector\SymbolNodeSelector;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

#[TestDox('Symbol Node Selector のテスト')]
class SymbolNodeSelectorTest extends TestCase
{
    private $config;
    private const TESTNET_HASH = '49D6E1CE276A85B70EAFE52349AACCA389302E7A9754BCF1221E79494FC665A4';

    protected function setUp(): void
    {
        $this->config = [
            'testnet_symbol_nodes' => [
                'https://sym-test-03.opening-line.jp:3001',
                'https://sym-test-01.opening-line.jp:3001',
                'https://sym-test-100.opening-line.jp:3001'
            ]
        ];
    }

    #[Test]
    #[TestDox('最初のテストネットノードが正常な場合、そのノードのURLが返される')]
    public function getFirstActiveTestnetNode(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['networkGenerationHashSeed' => self::TESTNET_HASH])),
            new Response(200, [], json_encode(['status' => ['apiNode' => 'up', 'db' => 'up']])),
            new Response(200, [], json_encode(['network' => ['identifier' => 'testnet']]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $selector = new SymbolNodeSelector($this->config, $client);

        $node = $selector->getActiveTestnetNode();
        $this->assertEquals('https://sym-test-03.opening-line.jp:3001', $node);
    }

    #[Test]
    #[TestDox('最初のノードが失敗し2番目が正常な場合、2番目のノードのURLが返される')]
    public function getSecondActiveTestnetNode(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['networkGenerationHashSeed' => 'invalid_hash'])),
            new Response(200, [], json_encode(['networkGenerationHashSeed' => self::TESTNET_HASH])),
            new Response(200, [], json_encode(['status' => ['apiNode' => 'up', 'db' => 'up']])),
            new Response(200, [], json_encode(['network' => ['identifier' => 'testnet']]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $selector = new SymbolNodeSelector($this->config, $client);

        $node = $selector->getActiveTestnetNode();
        $this->assertEquals('https://sym-test-01.opening-line.jp:3001', $node);
    }

    #[Test]
    #[TestDox('非アクティブなノードは適切にスキップされnullが返される')]
    public function inactiveNodeIsSkipped(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['networkGenerationHashSeed' => self::TESTNET_HASH])),
            new Response(200, [], json_encode(['status' => ['apiNode' => 'up', 'db' => 'down']])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $selector = new SymbolNodeSelector($this->config, $client);

        $node = $selector->getActiveTestnetNode();
        $this->assertNull($node);
    }

    #[Test]
    #[TestDox('すべてのノードが失敗した場合、nullが返される')]
    public function allNodesFailure(): void
    {
        $mock = new MockHandler([
            // 1番目のノード - APIノードダウン
            new Response(200, [], json_encode(['networkGenerationHashSeed' => self::TESTNET_HASH])),
            new Response(200, [], json_encode(['status' => ['apiNode' => 'down', 'db' => 'up']])),

            // 2番目のノード - DBダウン
            new Response(200, [], json_encode(['networkGenerationHashSeed' => self::TESTNET_HASH])),
            new Response(200, [], json_encode(['status' => ['apiNode' => 'up', 'db' => 'down']])),

            // 3番目のノード - 不正なネットワークタイプ
            new Response(200, [], json_encode(['networkGenerationHashSeed' => self::TESTNET_HASH])),
            new Response(200, [], json_encode(['status' => ['apiNode' => 'up', 'db' => 'up']])),
            new Response(200, [], json_encode(['network' => ['identifier' => 'mainnet']]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $selector = new SymbolNodeSelector($this->config, $client);

        $node = $selector->getActiveTestnetNode();
        $this->assertNull($node);
    }

    #[Test]
    #[TestDox('レスポンスデータが不完全な場合、適切に処理される')]
    public function handleIncompleteResponse(): void
    {
        $mock = new MockHandler([
            // networkGenerationHashSeedキーが存在しない
            new Response(200, [], json_encode([])),

            // statusオブジェクトが不完全
            new Response(200, [], json_encode(['networkGenerationHashSeed' => self::TESTNET_HASH])),
            new Response(200, [], json_encode(['status' => []])),

            // networkオブジェクトが不完全
            new Response(200, [], json_encode(['networkGenerationHashSeed' => self::TESTNET_HASH])),
            new Response(200, [], json_encode(['status' => ['apiNode' => 'up', 'db' => 'up']])),
            new Response(200, [], json_encode(['network' => []]))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $selector = new SymbolNodeSelector($this->config, $client);
        $node = $selector->getActiveTestnetNode();

        $this->assertNull($node, '不完全なレスポンスデータの場合はnullが返されるべき');
    }

    #[Test]
    #[TestDox('HTTP通信エラーが発生した場合、適切に処理される')]
    public function handleHttpErrors(): void
    {
        $mock = new MockHandler([
            // 接続エラー
            new RequestException('Connection error', new Request('GET', 'test')),

            // 404エラー
            new Response(404),

            // 500エラー
            new Response(500),

            // 不正なJSONレスポンス
            new Response(200, [], 'Invalid JSON')
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $selector = new SymbolNodeSelector($this->config, $client);
        $node = $selector->getActiveTestnetNode();

        $this->assertNull($node, 'HTTP通信エラーの場合はnullが返されるべき');
    }

    #[Test]
    #[TestDox('タイムアウト設定が正しく機能する')]
    public function handleTimeout(): void
    {
        $config = ['timeout' => 5, 'testnet_symbol_nodes' => ['https://test.node']];
        $selector = new SymbolNodeSelector($config);

        $this->assertEquals(5, $selector->getTimeout());
    }

    #[Test]
    #[TestDox('設定が指定されていない場合のデフォルト値が正しく機能する')]
    public function handleDefaultConfig(): void
    {
        $selector = new SymbolNodeSelector();
        $this->assertEquals(10, $selector->getTimeout(), 'デフォルトのタイムアウト値は10秒であるべき');
    }

    #[Test]
    #[DataProvider('nodeStatusProvider')]
    #[TestDox('様々なノードステータスに対して適切に処理される')]
    public function testVariousNodeStatuses(array $status, bool $expectedActive): void
    {
        $responses = [
            new Response(200, [], json_encode(['networkGenerationHashSeed' => self::TESTNET_HASH])),
            new Response(200, [], json_encode(['status' => $status]))
        ];

        // ステータスチェックが成功した場合のみ、network/propertiesのチェックが行われる
        if ($status['apiNode'] === 'up' && $status['db'] === 'up') {
            $responses[] = new Response(200, [], json_encode([
                'network' => ['identifier' => $expectedActive ? 'testnet' : 'mainnet']
            ]));
        }

        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $selector = new SymbolNodeSelector($this->config, $client);
        $node = $selector->getActiveTestnetNode();

        if ($expectedActive) {
            $this->assertEquals('https://sym-test-03.opening-line.jp:3001', $node);
        } else {
            $this->assertNull($node);
        }
    }

    public static function nodeStatusProvider(): array
    {
        return [
            'both up' => [['apiNode' => 'up', 'db' => 'up'], true],
            'api down' => [['apiNode' => 'down', 'db' => 'up'], false],
            'db down' => [['apiNode' => 'up', 'db' => 'down'], false],
            'both down' => [['apiNode' => 'down', 'db' => 'down'], false],
            'missing api status' => [['db' => 'up'], false],
            'missing db status' => [['apiNode' => 'up'], false],
            'unknown status' => [['apiNode' => 'unknown', 'db' => 'up'], false],
        ];
    }
}