<?php
use Chenms\Insurance\Insurance;
use PHPUnit\Framework\TestCase;
class InsuranceTests extends TestCase
{
    public function testUnderwriting()
    {
        $sdk = new Insurance([
            'pingan' => [

            ],
        ]);

        $response = $sdk->getService('pingan')->underwriting([
            'order'=>[
                'num_id'=>'250124145451481442221',
                'policy_money'=>'0.72',
                'mp_plan_id'=>'MP03022064',
                'plan_id'=>'PK00081399',
                's_time'=>'2025-02-25 00:00:00',
                'e_time'=>'2025-02-26 00:00:00',
                'personnelType'=>'1',//1个人,0团体
            ],
            'applicant'=>[
                'name'=>'王大大',
                'birth'=>'1982-09-05',
                'sex'=>'1',
                'paper_num'=>'340123198209050039',
                'paper_type'=>'1',
                'tel'=>'18715125115',
            ],
            'assured'=>[
                [
                    'name'=>'王大大',
                    'birth'=>'1994-02-06',
                    'sex'=>'1',
                    'paper_num'=>'110101199402061315',
                    'paper_type'=>'1',
                    'tel'=>'18715125115',
                    'relation_type'=>'1',
                ]
            ],
        ]);
        var_dump($response);
    }

    public function testcancel()
    {
        $sdk = new Insurance([
            'pingan' => [

            ],
        ]);

        $response = $sdk->getService('pingan')->cancel([
                'policy_num'=>'10240006600505752941',
                's_time'=>'2025-02-25 00:00:00',
        ]);
        var_dump($response);
    }
}