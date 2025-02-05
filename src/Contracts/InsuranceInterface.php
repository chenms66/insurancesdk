<?php
namespace Chenms\Insurance\Contracts;

interface InsuranceInterface
{
    public function underwriting(array $params): array; // 承保
    public function cancel(array $params): array;      // 退保
}