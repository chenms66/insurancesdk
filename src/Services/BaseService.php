<?php
namespace Chenms\Insurance\Services;

use Chenms\Insurance\Contracts\InsuranceInterface;
use Chenms\Insurance\Exceptions\InsuranceException;
use Chenms\Insurance\Core\HttpClient;

abstract class BaseService implements InsuranceInterface
{
    protected $httpClient;
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->httpClient = new HttpClient($config);
    }

    // 统一参数校验
    /**
     * @throws InsuranceException
     */
    protected function validateParams(array $params, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (!isset($params[$field])) {
                throw new InsuranceException($field.'不能为空');
            }
        }
    }
}