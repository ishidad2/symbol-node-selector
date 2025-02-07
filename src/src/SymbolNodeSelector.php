<?php

namespace Ishidad2\SymbolNodeSelector;

use GuzzleHttp\Client;

class SymbolNodeSelector
{
    protected $config;
    public $client;

    /**
     * Constructor for the SymbolNodeSelector class
     *
     * @param array|null $config Configuration array (if null, loads from config file)
     * @param Client|null $client GuzzleHttp client instance (if null, creates new instance)
     */
    public function __construct(array $config = null, Client $client = null)
    {
        // Prioritize passed config, load from file only if null
        $this->config = $config ?? require __DIR__ . '/config/symbolnodeselector.php';

        $this->client = $client ?? new Client(['timeout' => $this->getTimeout()]);
    }

    /**
     * Get an active mainnet node URL
     *
     * @return string|null URL of an active mainnet node, or null if none found
     */
    public function getActiveMainnetNode()
    {
        return $this->getActiveNode(
            $this->config['mainnet_symbol_nodes'] ?? [],
            'mainnet',
            '57F7DA205008026C776CB6AED843393F04CD458E0AA2D9F1D5F31A402072B2D6'
        );
    }

    /**
     * Get an active testnet node URL
     *
     * @return string|null URL of an active testnet node, or null if none found
     */
    public function getActiveTestnetNode()
    {
        return $this->getActiveNode(
            $this->config['testnet_symbol_nodes'] ?? [],
            'testnet',
            '49D6E1CE276A85B70EAFE52349AACCA389302E7A9754BCF1221E79494FC665A4'
        );
    }

    /**
     * Get the first active node that matches the specified conditions
     *
     * @param array $nodes Array of node URLs to check
     * @param string $networkType Network type ('mainnet' or 'testnet')
     * @param string $expectedHash Expected network generation hash
     * @return string|null URL of the first active node found, or null if none found
     */
    private function getActiveNode($nodes, $networkType, $expectedHash)
    {
        foreach ($nodes as $node) {
            if ($this->isNodeActive($node, $networkType, $expectedHash)) {
                return $node;
            }
        }
        return null; // Return null if no active node is found
    }

    /**
     * Check if the specified node is active and meets expected conditions
     *
     * @param string $nodeUrl URL of the node to check
     * @param string $networkType Expected network type
     * @param string $expectedHash Expected network generation hash
     * @return bool True if node is active and meets conditions
     */
    private function isNodeActive($nodeUrl, $networkType, $expectedHash)
    {
        try {
            // Check networkGenerationHashSeed from /node/info
            $response = $this->client->get("$nodeUrl/node/info");
            $nodeInfo = json_decode($response->getBody(), true);
            if (!isset($nodeInfo['networkGenerationHashSeed']) || $nodeInfo['networkGenerationHashSeed'] !== $expectedHash) {
                return false;
            }

            // Check API node and DB status from /node/health
            $response = $this->client->get("$nodeUrl/node/health");
            $health = json_decode($response->getBody(), true);
            if (($health['status']['apiNode'] ?? '') !== 'up' || ($health['status']['db'] ?? '') !== 'up') {
                return false;
            }

            // Check network identifier from /network/properties
            $response = $this->client->get("$nodeUrl/network/properties");
            $networkProps = json_decode($response->getBody(), true);
            if (($networkProps['network']['identifier'] ?? '') !== $networkType) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the HTTP client timeout value
     *
     * @return int Timeout value in seconds, defaults to 10
     */
    public function getTimeout()
    {
        return $this->config['timeout'] ?? 10;
    }
}