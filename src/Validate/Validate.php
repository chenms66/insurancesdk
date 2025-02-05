<?php
namespace Chenms\Insurance\Validate;

class Validate
{
    // 存储验证规则
    protected $rules = [];
    // 存储场景规则
    protected $scenes = [];
    // 存储错误信息
    protected $errors = [];
    // 当前验证场景
    protected $currentScene = null;

    /**
     * 设置验证规则
     * @param string $field 字段名
     * @param array  $rule  验证规则
     */
    public function rule($field, $rule)
    {
        $this->rules[$field] = $rule;
        return $this;
    }

    /**
     * 设置验证场景
     * @param string $scene 场景名
     * @param array  $fields 要验证的字段
     */
    public function scene($scene, $fields)
    {
        $this->scenes[$scene] = $fields;
        return $this;
    }

    /**
     * 设置当前验证场景
     * @param string $scene 场景名
     */
    public function setScene($scene)
    {
        $this->currentScene = $scene;
        return $this;
    }

    /**
     * 执行验证
     * @param array $data 需要验证的数据
     * @return bool 验证是否通过
     */
    public function check(array $data)
    {
        // 获取当前场景的字段
        $fields = $this->currentScene ? $this->scenes[$this->currentScene] : array_keys($this->rules);

        foreach ($fields as $field) {
            if (!isset($data[$field])) {
                $this->errors[$field] = "{$field}不能为空";
                continue;
            }
            // 获取字段对应的验证规则
            $rule = $this->rules[$field];
            // 根据规则进行验证
            if (!$this->applyRule($data[$field], $rule)) {
                $this->errors[$field] = "{$field}验证失败";
            }
        }

        return empty($this->errors);
    }

    /**
     * 应用验证规则
     * @param mixed $value 数据值
     * @param mixed $rule  验证规则
     * @return bool
     */
    protected function applyRule($value, $rule)
    {
        // 可以根据不同规则进行不同验证
        switch ($rule) {
            case 'required':
                return !empty($value);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'number':
                return is_numeric($value);
            // 更多规则可以自行扩展
            default:
                return true;
        }
    }

    /**
     * 获取错误信息
     * @return array 错误信息
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * 清除错误信息
     */
    public function clearErrors()
    {
        $this->errors = [];
    }
}
