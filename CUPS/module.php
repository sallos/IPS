<?

	class CUPS extends IPSModule
	{

        var $IP_C;
        var $IP_D;
        var $NAME;

                public function Create()
                {
                        //Never delete this line!
                        parent::Create();
        
                        //These lines are parsed on Symcon Startup or Instance creation
                        //You cannot use variables here. Just static values.
                        $this->RegisterPropertyString("CURL_IP", "127.0.0.1");
                        $this->RegisterPropertyString("DRUCKER_IP", "127.0.0.1");
                        $this->RegisterPropertyString("DRUCKER_NA", "Test_Drucker");
       
                }

		
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
                        
                        $this->IP_C = $this->ReadPropertyString("CURL_IP");
                        $this->IP_D = $this->ReadPropertyString("DRUCKER_IP");
                        $this->NAME = $this->ReadPropertyString("DRUCKER_NA");

                        $this->RegisterVariableBoolean("Drucker_ON", "Drucker Online");
                        $this->RegisterVariableInteger("Offene_AUF", "Offene_Aufträge");

			$this->RegisterTimer("GetCUPS", 0, 'CUPS_GetCUPS($_IPS[\'TARGET\']);');
			$this->GetCUPS();
                      
			
		}

                public function GetCUPS()
                {
                        $this->IP_C = $this->ReadPropertyString("CURL_IP");
                        $this->IP_D = $this->ReadPropertyString("DRUCKER_IP");
                        $this->NAME = $this->ReadPropertyString("DRUCKER_NA");

                        $URL = "http://" . $this->IP_C . ":631/printers/" . $this->NAME;

                        $BUFFER = implode('', file($URL));
    
                        $Anfang = strpos($BUFFER, "Showing");
                        $Ende = strpos($BUFFER, "active job");
                        $Lang = $Ende - $Anfang;

                        $TEMP = substr($BUFFER, $Anfang, $Lang);
                        $DUMP = strtok($TEMP, " ");
                        $ERG = intval(strtok(" "));

                        SetValue($this->GetIDForIdent("Offene_AUF"), $ERG);
	
                }


        }

?>
