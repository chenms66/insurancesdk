<?php

namespace Chenms\Insurance\Log;

class Logger
{
    // 日志等级常量
    const LEVEL_INFO = 'INFO';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_DEBUG = 'DEBUG';

    // 默认日志文件路径
    private $logFilePath;

    // 日志格式
    private $logFormat;

    // 构造函数，初始化日志文件路径
    public function __construct($logFilePath = '../logs/app.log', $logFormat = "[{date}] [{level}] {message}\n")
    {
        $this->logFilePath = $logFilePath;
        $this->logFormat = $logFormat;

        // 创建日志目录
        if (!file_exists(dirname($this->logFilePath))) {
            mkdir(dirname($this->logFilePath), 0777, true);
        }
    }

    // 记录日志
    private function writeLog($level, $message)
    {
        $date = date('Y-m-d H:i:s');
        $logMessage = str_replace(
            ['{date}', '{level}', '{message}'],
            [$date, $level, $message],
            $this->logFormat
        );

        // 写入日志文件
        file_put_contents($this->logFilePath, $logMessage, FILE_APPEND);
    }

    // 记录INFO级别的日志
    public function info($message)
    {
        $this->writeLog(self::LEVEL_INFO, $message);
    }

    // 记录ERROR级别的日志
    public function error($message)
    {
        $this->writeLog(self::LEVEL_ERROR, $message);
    }

    // 记录DEBUG级别的日志
    public function debug($message)
    {
        $this->writeLog(self::LEVEL_DEBUG, $message);
    }

    // 记录请求日志
    public function logRequest($request)
    {
        $message = "Request: " . json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->info($message);
    }

    // 记录响应日志
    public function logResponse($response, $startTime)
    {
        $responseTime = round(microtime(true) - $startTime, 4);
        $message = "Response: " . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $message .= " | Response Time: {$responseTime}s";
        $this->info($message);
    }

    // 记录异常日志
    public function logError($errorMessage)
    {
        $this->error($errorMessage);
    }

    // 自定义日志文件路径
    public function setLogFilePath($path)
    {
        $this->logFilePath = $path;
    }

    // 设置自定义日志格式
    public function setLogFormat($format)
    {
        $this->logFormat = $format;
    }
}