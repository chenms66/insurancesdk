<?php
namespace Chenms\Insurance;
use Chenms\Insurance\Exceptions\InsuranceException;
class Insurance
{
    protected $services = [];
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @throws InsuranceException
     */
    public function getService(string $company): object
    {
        if (!isset($this->services[$company])) {
            // 动态加载服务类
            $serviceClass = "Chenms\\Insurance\\Services\\" . ucfirst($company) . "Service";
            if (!class_exists($serviceClass)) {
                throw new InsuranceException("Unsupported company: $company");
            }

            $this->services[$company] = new $serviceClass($this->config[$company]);
        }

        return $this->services[$company];
    }

}