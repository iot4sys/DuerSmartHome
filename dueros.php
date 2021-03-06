<?php

class dueros{
    private $obj, $devices, $hassURL, $hassPASS;

    public function __construct($obj, $devices, $hassURL, $hassPASS){
        $this->obj = $obj;
        $this->devices = $devices;
        $this->hassURL = $hassURL;
        $this->hassPASS = $hassPASS;
    }

    //获得messageID
		public function getMessageID(){
        $chars = md5(uniqid(mt_rand(), true));
        $uuid  = substr($chars,0,8) . '-';
        $uuid .= substr($chars,8,4) . '-';
        $uuid .= substr($chars,12,4) . '-';
        $uuid .= substr($chars,16,4) . '-';
        $uuid .= substr($chars,20,12);
        return $uuid;
    }

		//设备发现
		public function discovery(){
			$header = array(
				"namespace"           =>    "DuerOS.ConnectedHome.Discovery",
				"name"                       =>    "DiscoverAppliancesResponse",
				"messageId "            =>    $this->getMessageID(),
				"payloadVersion"  =>    "1"
			);
			return json_encode(array("header" => $header, "payload" => $this->devices));
		}

	//设备控制
	public function control(){
		$payload = array();
		$applianceId=$this->obj->payload->appliance->applianceId;
		$action = '';
		$additionalApplianceDetails = $this->object2array($this->obj->payload->appliance->additionalApplianceDetails);
		$name = substr( $this->obj->header->name, 0, -7);
		$deviceType = substr( $applianceId, 0, stripos($applianceId,".") );
		switch($name){
			case 'TurnOn':
				$action = 'turn_on';
				$payload["entity_id"] = $applianceId;
				break;
			case 'TurnOff':
				$action='turn_off';
				$payload["entity_id"] = $applianceId;
				break;
			case 'TimingTurnOn':
				$nowTime = time();
				$actionTime = $this->obj->payload->timestamp->value;
				sleep(int($actionTime) - int($nowTime));
				$action='turn_on';
				$payload["entity_id"] = $applianceId;
				break;
			case 'TimingTurnOff':
				$nowTime = time();
				$actionTime = $this->obj->payload->timestamp->value;
				sleep( int($actionTime) - int($nowTime) );
				$action='turn_off';
				$payload["entity_id"] = $applianceId;
				break;
			case 'Pause':
				//$action =
				break;
			case 'Continue':
				//$action =
				break;
			case 'SetBrightnessPercentage':
				//$privious = $this->priviousState();
				if(!empty($additionalApplianceDetails["setBrightnessPercentage"]) && !empty($this->obj->payload->brightness->value)){
					$detail = $additionalApplianceDetails["setBrightnessPercentage"];
					$payload[$detail] = $this->obj->payload->brightness->value;
				}
				$payload["entity_id"] = $applianceId;
				$action = 'turn_on';
				break;
			case 'IncrementBrightnessPercentage':
			//$privious = $this->priviousState();
			//$action='brightness_up';
				break;
			case 'DecrementBrightnessPercentage':
				//$privious = $this->priviousState();
				//$action='brightness_down';
				break;
			case 'SetColor':
				if(!empty($additionalApplianceDetails["setColor"]) && !empty($this->obj->payload->color)){
					$detail = $additionalApplianceDetails["setColor"];
					$payload[$detail] = $this->HSVtoRGB( $this->object2array($this->obj->payload->color) );
				}
				$payload["entity_id"] = $applianceId;
				$action='turn_on';
				break;
			case 'IncrementTemperature':
				//$action='temperature_up';
				break;
			case 'IncrementTemperature':
				//$action='temperature_up';
				break;
			//more
			default:
				break;
		}
		if(!empty($action) && !empty($deviceType)){
			return $this->response($deviceType, $action, $payload, "services");
		}else{
			return false;
		}
	}

	//查询状态
	public function status(){
		$payload = array();
		$applianceId=$this->obj->payload->appliance->applianceId;
		$action = '';
		$additionalApplianceDetails = $this->object2array($this->obj->payload->appliance->additionalApplianceDetails);
		$name = substr( $this->obj->header->name, 3, -7);
		$deviceType =  ""; //substr( $applianceId, 0, stripos($applianceId,".") );
		$action = "";
		$payload = $applianceId;  //初始化payload
		switch($name){
			case "GetAirQualityIndex":
				break;
			case "GetAirPM25":
				break;
			case "GetAirPM10":
				break;
			case "GetCO2Quantity":
				break;
			case "GetHumidity":
				break;
			case "GetTemperatureReading":
				break;
			case "GetTargetTemperature":
				break;
			case "GetRunningTime":
				break;
			case "GetTimeLeft":
				break;
			case "GetRunningStatus":
				break;
			case "GetElectricityCapacity":
				break;
			case "GetWaterQuality":
				break;
			default:
				break;
		}
		return $this->response($deviceType, $action, $payload, "states");
	}

	//与hass传输
	public function response($deviceType, $action, $payload, $callName){
		if($callName == "services"){
			// $http_url = $this->hassURL."/api/".$callName."/".$deviceType."/".$action."?api_password=".$this->hassPASS;
			// error_log($http_url);
			// $ch = curl_init($http_url);
			// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			// curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			// curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($payload));
			// curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
			// curl_setopt($ch, CURLOPT_HEADER, 0);
			// curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: '.strlen(json_encode($payload))));
			// $result = curl_exec($ch);
			// if(curl_errno($ch)){
				// print curl_error($ch);
			// }
			// curl_close($ch);
            //-----------
			//支持Long-Lived Access Tokens
            $opts = array(
                'http' => array(
                    'method' => "POST",
                    'header' => "Content-Type: application/json\r\n" . "Authorization: Bearer " . $this->hassPASS . "\r\n",
                    'content' => json_encode($payload)
                )
            );
            $context = stream_context_create($opts);
            $http_url = $this->hassURL . "/api/" . $callName . "/" . $deviceType . "/" . $action;
            $result = file_get_contents($http_url, false, $context);
            //-------- 			
		}elseif($callName == "states") {
			// $http_url = $this->hassURL."/api/".$callName."/".$payload."?api_password=".$this->hassPASS;
			// error_log($http_url);
			// $ch = curl_init($http_url);
			// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			// curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
			// curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
			// curl_setopt($ch, CURLOPT_HEADER, 0);
			// //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: '.strlen($payload)));
			// $result = curl_exec($ch);
			// $result = json_decode($result, true);
			// if(curl_errno($ch)){
				// print curl_error($ch);
			// }
			// curl_close($ch);
            //-----------
			//支持Long-Lived Access Tokens
            $opts = array(
                "http" => array(
                    "method" => "GET",
                    "header" => "Content-Type: application/json\r\n" . "Authorization: Bearer " . $this->hassPASS . "\r\n"
                )
            );
            $context = stream_context_create($opts);
            $http_url = $this->hassURL . "/api/" . $callName . "/" . $payload;
            $result = file_get_contents($http_url, false, $context);
            //--------			
		}
		return $result;
	}

	//如名字
	public function object2array($object) {
		$array = array(); //避免提示变量不存在
		if (is_object($object)) {
			foreach ($object as $key => $value) {
				$array[$key] = $value;
			}
		}else {
			$array = $object;
		}
		return $array;
	}
	//如名字
  //公式：https://en.wikipedia.org/wiki/HSL_and_HSV
  public function HSVtoRGB(array $hsv){
    $keys = array_keys($hsv);
		$H = $hsv[$keys[0]];
		$S = $hsv[$keys[1]];
		$V = $hsv[$keys[2]];
    $Hi = floor($H/60);
    $f = $H/60.0 - $Hi;
    $p = $V*(1-$S);
    $q = $V*(1-$S*$f);
    $t = $V*(1-$S*(1-$f));
    if($S === 0){
    	return array(255*$V, 255*$V, 255*$V);
    }
    switch ($Hi){
    	case 0:
    		$color = array(255*$V, 255*$t, 255*$p);
    		break;
    	case 1:
    		$color = array(255*$q, 255*$V, 255*$p);
    		break;
    	case 2:
    		$color = array(255*$p, 255*$V, 255*$t);
    		break;
    	case 3:
    		$color = array(255*$p, 255*$q, 255*$V);
    		break;
    	case 4:
    		$color = array(255*$t, 255*$p, 255*$V);
    		break;
    	case 5:
    		$color = array(255*$V, 255*$p, 255*$q);
    		break;
    	case 6:
    		$color = array(255*$V, 255*$t, 255*$p);
    		break;
    }
    return $color;
  }
	// private function HSVtoRGB(array $hsv) {
	// 	$keys = array_keys($hsv);
	// 	$H = $hsv[$keys[0]];
	// 	$S = $hsv[$keys[1]];
	// 	$V = $hsv[$keys[2]];
	// 	//1
	// 	$H *= 6;
	// 	//2
	// 	$I = floor($H);
	// 	$F = $H - $I;
	// 	//3
	// 	$M = $V * (1 - $S);
	// 	$N = $V * (1 - $S * $F);
	// 	$K = $V * (1 - $S * (1 - $F));
	// 	//4
	// 	switch ($I) {
	// 		case 0:
	// 			list($R,$G,$B) = array($V,$K,$M);
	// 			break;
	// 		case 1:
	// 			list($R,$G,$B) = array($N,$V,$M);
	// 			break;
	// 		case 2:
	// 			list($R,$G,$B) = array($M,$V,$K);
	// 			break;
	// 		case 3:
	// 			list($R,$G,$B) = array($M,$N,$V);
	// 			break;
	// 		case 4:
	// 			list($R,$G,$B) = array($K,$M,$V);
	// 			break;
	// 		case 5:
	// 		case 6: //for when $H=1 is given
	// 			list($R,$G,$B) = array($V,$M,$N);
	// 			break;
	// 	}
	// 	return array(255*$R, 255*$G, 255*$B);
	// }
}
