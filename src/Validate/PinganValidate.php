<?php
namespace Chenms\Insurance\Validate;
class PinganValidate extends Validate{
    /**
     * 验证规则
     */
    protected $rule = [
        'num_id' => 'required',
        'policy_money' => 'required',
        'mp_plan_id' => 'required',
        's_time' => 'required',
        'e_time' => 'required',
    ];

    /**
     * 提示消息
     */
    protected $message  =   [
        'num_id.require' => '订单号不能为空',
        'policy_money.require' => '保费不能为空',
        'mp_plan_id.require' => '平安mp值不能为空',
        's_time.require' => '起保时间不能为空',
        'e_time.require' => '终保时间不能为空',
    ];

    /**
     * 验证场景
     */
    protected $scenes = [
        'underwriting'  => ['num_id','policy_money','mp_plan_id','s_time','e_time'],
    ];
}