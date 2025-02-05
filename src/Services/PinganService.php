<?php
namespace Chenms\Insurance\Services;
use Chenms\Insurance\Exceptions\InsuranceException;
use Chenms\Insurance\Log\Logger;
use Chenms\Insurance\Utils\Comm;

class PinganService extends BaseService
{
    public function __construct($config)
    {
        parent::__construct($config);
    }

    /**
     * @throws InsuranceException
     * 承保
     */
    public function underwriting(array $params): array
    {
        $order = $params['order'];
        $applicant = $params['applicant'];
        $assured = $params['assured'];
        $data['partnerName'] = $this->config['partner_name'];
        $data['departmentCode'] = $this->config['department_code'];
        $data['applicantInfo'] = $this->applicantInfoList($applicant,$order);

        $baseInfo=array(//【必填】
            'transactionNo' => $order['num_id'],//【必填】"交易号(合作伙伴订单号，用于幂等性控制) String"
            'totalActualPremium' => $order['policy_money'],
            'productCode' =>$order['mp_plan_id'],//  [非空]产品编码,//【必填：平安业务提供】":"产品编码,通过某一险种定义出来的产品的编码String"
            'insuranceBeginDate' => $order['s_time'],//[非空]保险起期
            'insuranceEndDate' => $order['e_time'],// [非空]保险止期
            'coinsuranceMark' => '0',//【选填】共保标志 0：非共保 1：共保String
            'businessType' => '1',//【选填，默认个人】业务类型/个团属性：1-个人，2-团体 String",
        );

        $data['productInfoList'][]= array(
            'baseInfo'=>$baseInfo,
            'riskGroupInfoList'=>$this->riskGroupInfoList($assured,$order,$applicant),
        );
        $request_id = 'applyPolicyNoPay' . time();
        $response = $this->comm_url($this->config['url'],$request_id,$data,'平安承保');

        if (!isset($response['data'][0]['resultCode']) || $response['data'][0]['resultCode'] != '200') {
            throw new InsuranceException($response['data'][0]['resultMessage'] ?? '平安承保失败');
        }

        return $response;
    }

    /**
     * @throws InsuranceException
     */
    public function cancel(array $params): array
    {
        $policy_num = $params['policy_num'];
        if($params['s_time'] > date('Y-m-d H:i:s')){//生效前
            $type = 'write_off_policy';
        }else{
            $type = 'cancel_url_pe';
        }
        $param = $this->surrParams($policy_num);
        $request_id = $type . time();
        $result = $this->comm_url($this->config[$type],$request_id,$param,'平安退保');

        if (!isset($result['data']['resultCode']) || $result['data']['resultCode'] != "Y") {
            throw new InsuranceException($response['data']['resultMessage'] ?? '平安退保失败');
        }
        return $result['data'] ?? $result;
    }

    public function surrParams($policy_num){
        return array(
            'partnerName'=>$this->config['partner_name'],
            'departmentCode'=>$this->config['department_code_insure'],
            'requestId'=>$this->config['partner_name'].time(),
            'partnerCode'=>$this->config['partner_name'],
            'refundType'=>'CG',//常规退保
//            'refundType'=>'XY',//通融退保  XY -指定时间生效退保
            'isBeforePayFee'=>'false',
            'isEffectiveInTime'=>'true',
            'refundEffectTime'=>date('Y-m-d H:i:s'),
            'policyNo'=> $policy_num,
        );
    }

    /**
     * @param array $applicant
     * @param array $order
     * @return array
     * 投保人信息
     */
    private function applicantInfoList(array $applicant, array $order): array
    {
        $arr =  array(//[非空]投保人,只有一个投保人
            'name' => $applicant['name'],//[非空]名称
            'certificateNo' => $applicant['paper_num'],//// [非空]证件号
            'certificateType' => $this->get_paper_type_convert($applicant['paper_type']),//[非空]证件类型
            'personnelType' => $order['personnelType'],// [非空]个团标志[1个人,0团体]
            "sexCode" => $this->get_sex_convert($applicant['sex']),
            "birthday" => $applicant['birth'],
            "address" => "",
            "linkManName" => "",
            "email" => "",
            "homeTelephone" => "$applicant[tel]"
        );
        if($order['personnelType'] == 1){
            $arr['certificateType'] = $this->get_paper_type_convert($applicant['paper_type'],true);
            $arr['personnelType'] = '0';
        }
        return $arr;
    }

    /**
     * @param $paper_type
     * @param false $is_group 是否团体
     * @return string
     * 证件类型表
     */
    public function get_paper_type_convert($paper_type, bool $is_group = false): string
    {
        if($is_group){
            switch ($paper_type) {
                case 1://组织机构代码证
                    return "01";
                case 2://税务登记证
                    return "02";
                case 4://工商营业执照
                    return "04";
                case 5://统一社会信用代码证
                    return "05";
            }
        }else{
            switch ($paper_type) {
                case 1://身份证
                    return "01";
                case 2://护照
                    return "02";
                case 3://军人证
                    return "03";
                case 5:
                case 10:
                    return "06";
                case 9:     //港澳台居民居住证
                    return "09";
                case 11://港澳通行证
                    return "04";
                default:
                    return "99";
            }
        }

    }

    /**
     * @param string $sex
     * @return string
     * 性别
     */
    public function get_sex_convert(string $sex): string
    {
        if ($sex == "1") {
            $sexs = "M";
        } else {
            $sexs = "F";
        }
        return $sexs;
    }

    /**
     * 获取平安与投保人关系
     * @param string $type  证件代码
     * @param string $t_sex 投保人性别
     * @param string $b_sex 被保人性别
     * @return string
     */
    public function get_relation_type(string $type, string $t_sex, string $b_sex): string
    {
        if($type == '99'){
            return '9';
        }
        //01本人，10配偶，40子女，50父母
        if ($type == '1') {//本人
            $cardType = '1';
        } elseif ($type == '10') {//配偶
            $cardType = '2';
        }elseif ($type== '50'){//父母
            if ( $t_sex == '1' and $b_sex == '1') {
                $cardType = '3';//父子
            } elseif ($t_sex == '2' and $b_sex == '1') {
                $cardType = '4';//父女
            } elseif ($t_sex == '1' and $b_sex == '2') {
                $cardType = 'A';//母子
            } else {
                $cardType = 'B';//母女
            }
        }else{//子女
            $cardType = "I";
        }
        return $cardType;
    }

    /**
     * @param array $assured
     * @param array $order
     * @param array $applicant
     * @return array
     * 被保人信息
     */
    public function riskGroupInfoList(array $assured, array $order, array $applicant): array
    {
        $arr = [];
        foreach ($assured as $k=>$v){
            $arr[$k] = [
                'applyNum'=>$k+1,
                'productPackageType'=>$order['plan_id'],
                'riskPersonInfoList'=> array(
                    'name' => $v['name'],//"名称"
                    'birthday' => $v['birth'],//【非身份证时必填】"出生日期 Date",
                    'age' => Comm::datediffage(strtotime($v['birth']), time()),//【非身份证时必填】"年龄 Integer",
                    'sexCode' => $this->get_sex_convert($v['sex']),//【非身份证时必填】"性别F,M"
                    'certificateNo' => $v['paper_num'],//【必填】":"证件号码",
                    'certificateType' => $this->get_paper_type_convert($v['paper_type']),//【必填】":"证件类型 ,01:身份证、02：护照、03：军人证、04：港澳通行证，05：驾驶证、06：港澳回乡证或台胞证，07：临时身份证、99：其他",
                    'totalActualPremium' => $order['policy_money'],//【必填】":"实交保费 Double",
                    'relationshipWithApplicant' => $this->get_relation_type($v['relation_type'],$applicant['sex'],$v['sex']),//":"被保人与投保人关系[详细见文档附录]",
                    'personnelAttribute' => 100,//【选填，默认100】":"人员属性200虚拟被保人100真实被保人010连带被保人 ",
                ),
            ];
        }
        return $arr;
    }

    /**
     * @param $url
     * @param $request_id
     * @param $params
     * @param $msg
     * @return mixed
     *公共请求地址
     */
    public  function comm_url($url,$request_id,$params,$msg){
        $logger = new Logger('logs/'.date('Ymd').'/cb.log');
        $access_token_url = $this->config['access_token'] . '?client_id=' . $this->config['client_id'] . '&grant_type=' . $this->config['grant_type'] . '&client_secret=' . $this->config['client_secret'];
        $res = $this->http_post($access_token_url);
        $result = json_decode($res, true);
        $url = $url . '?access_token=' . $result['data']['access_token'] . '&request_id=' . $request_id;
        $logger->info('【' . $msg . '请求路径】' . $url);
        $logger->info('【' . $msg . '请求报文】' . var_export(json_encode($params,256),true));
        $result = $this->post_curl($url, $params);
        $data = json_decode($result, true);
        if ($msg !== '下载保单' && $msg !== '打印发票') {
            $logger->info('【' . $msg . '返回原始报文】' . var_export($result,true));
        }
        if ($msg == '打印发票' && !empty($data['ret'])) {
            $logger->info('【' . $msg . '响应信息】' . var_export($result,true));
        }
        return $data;
    }

    /**
     *curl请求
     * @param string $url 请求url
     * @return string
     */
    public function http_post($url, $data = '', $method = 'GET')
    {
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
            if ($data != '') {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
            }
        }
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 300);
        curl_setopt($curl, CURLOPT_TIMEOUT, 300); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }

    public function post_curl($url, $data)
    {
        $headers = array(
            "Content-Type:application/json;charset=utf-8",
            "Accept:application/json;charset=utf-8",
        );
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
}