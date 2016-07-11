<?
class E2VTI extends IPSModule {

  public function Create() {
    parent::Create();
    $this->RegisterPropertyString("Host", "");
    $this->RegisterPropertyInteger("UpdateInterval", 15);
  }

  public function ApplyChanges() {
    parent::ApplyChanges();

    $stateId = $this->RegisterVariableBoolean("STATE", "Zustand", "~Switch", 1);
    $this->EnableAction("STATE");
    $serviceNameId = $this->RegisterVariableString("SERVICE_NAME", "Servicename", "", 3);
    $serviceReferenceId = $this->RegisterVariableString("SERVICE_REFERENCE", "Servicereferenz", "", 4);
	$volumeid = $this->RegisterVariableInteger("VOLUME", "Lautstärke", "~Intensity.100",0);
    $this->RegisterTimer('INTERVAL', $this->ReadPropertyInteger('UpdateInterval'), 'E2VTI_RequestData($id)');
  }

  protected function CleanVariablesOnInactive() {
    SetValueBoolean($this->GetIDForIdent('STATE'), false);
    SetValueString($this->GetIDForIdent('SERVICE_NAME'), '');
    SetValueString($this->GetIDForIdent('SERVICE_REFERENCE'), '');
  }

  public function RequestAction($ident, $value) {
    switch ($ident) {
      case 'STATE':
         $value = $value == 1;
         $this->SetState($value);
         break;
    }
  }

  protected function RegisterTimer($ident, $interval, $script) {
    $id = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);

    if ($id && IPS_GetEvent($id)['EventType'] <> 1) {
      IPS_DeleteEvent($id);
      $id = 0;
    }

    if (!$id) {
      $id = IPS_CreateEvent(1);
      IPS_SetParent($id, $this->InstanceID);
      IPS_SetIdent($id, $ident);
    }

    IPS_SetName($id, $ident);
    IPS_SetHidden($id, true);
    IPS_SetEventScript($id, "\$id = \$_IPS['TARGET'];\n$script;");

    if (!IPS_EventExists($id)) throw new Exception("Ident with name $ident is used for wrong object type");

    if (!($interval > 0)) {
      IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, 1);
      IPS_SetEventActive($id, false);
    } else {
      IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $interval);
      IPS_SetEventActive($id, true);
    }
  }

  public function RequestData() {
    $data = array();

    // Read data
    $this->ReadState();
    $this->ReadService();
	$this->ReadVol();

    // Build hash for return
    $data['state'] = GetValueBoolean($this->GetIDForIdent('STATE'));
    $data['service']['name'] = GetValueString($this->GetIDForIdent('SERVICE_NAME'));
    $data['service']['reference'] = GetValueString($this->GetIDForIdent('SERVICE_REFERENCE'));
    return $data;
  }

  public function ReadState() {
    $state = @trim(@$this->request('/web/powerstate')->e2instandby) == 'false';
    SetValueBoolean($this->GetIDForIdent('STATE'), $state);
    return $state;
  }

  public function ReadService() {
    $serviceData = $this->request('/web/subservices');
    if ($serviceData) {
      $serviceName = utf8_decode((string)$serviceData->e2service->e2servicename);
      $serviceReference = utf8_decode((string)$serviceData->e2service->e2servicereference);
      if ($serviceName == 'N/A') $serviceName = '';
      if ($serviceReference == 'N/A') $serviceReference = '';
      SetValueString($this->GetIDForIdent('SERVICE_REFERENCE'), $serviceReference);
      SetValueString($this->GetIDForIdent('SERVICE_NAME'), $serviceName);
    }
  }
  
   public function ReadVol() {
	$voldata = $this->request('/web/vol');
	if ($voldata) { 
		$vol = intval($voldata->e2current);
		SetValueInterger($this->GetIDForIdent('VOLUME'),$vol);
	}
  }
		

  public function Request($path) {
    $host = $this->ReadPropertyString('Host');
    if ($host == '') {
      $this->SetStatus(104);
      return false;
    }
    $client = curl_init();
    curl_setopt($client, CURLOPT_URL, "http://{$host}$path");
    curl_setopt($client, CURLOPT_POST, false);
    curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($client, CURLOPT_USERAGENT, "SymconE2");
    curl_setopt($client, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($client, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($client);
    $status = curl_getinfo($client, CURLINFO_HTTP_CODE);
    curl_close($client);
    if ($status == '0') {
      $this->SetStatus(201);
      return false;
    } elseif ($status != '200') {
      $this->SetStatus(201);
      return false;
    } else {
      $this->SetStatus(102);
      return simplexml_load_string($result);
    }
  }

  public function GetValue($key) {
    return GetValue($this->GetIDForIdent($key));
  }

  public function SetState($state) {
    SetValueBoolean($this->GetIDForIdent('STATE'), $state);
    $state = $state ? '4' : '5';
    return $this->SetPowerState($state);
  }

  public function PowerOn() {
    SetValueBoolean($this->GetIDForIdent('STATE'), true);
    return $this->SetPowerState(4);
  }

  public function Standby() {
    SetValueBoolean($this->GetIDForIdent('STATE'), false);
    return $this->SetPowerState(5);
  }

  public function PowerOff() {
    SetValueBoolean($this->GetIDForIdent('STATE'), false);
    return $this->SetPowerState(1);
  }

  public function PowerToggle() {
    return $this->SetPowerState(0);
  }

  public function Reboot() {
    return $this->SetPowerState(2);
  }

  public function RestartGUI() {
    return $this->SetPowerState(3);
  }

  public function SetPowerState($id) {
    return $this->request("/web/powerstate?newstate=$id");
  }

  public function Zap($reference) {
    $result = $this->request("/web/zap?sRef=$reference");
    $this->ReadService();
    return $result;
  }

}
?>
