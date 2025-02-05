<?php
namespace Chenms\Insurance\Exceptions;

class InsuranceException extends \Exception
{
    protected $errorCode;
    protected $data;

    public function __construct($message = "", $code = 0, $errorCode = 0, $data = null, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->data = $data;
    }

    /**
     * 返回统一错误格式
     */
    public function toResponse(): array
    {
        return [
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
            'errorCode' => $this->errorCode,
            'data' => $this->data,
        ];
    }

    /**
     * 自动转换为 JSON 字符串
     */
    public function __toString(): string
    {
        return json_encode($this->toResponse(), JSON_UNESCAPED_UNICODE);
    }
}