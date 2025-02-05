<?php
namespace Chenms\Insurance\Core;

class Config
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function get(string $key)
    {
        return $this->config[$key] ?? null;
    }
}