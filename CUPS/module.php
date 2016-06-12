<?

	class CUPS extends IPSModule
	{

        var $IP_C;
        var $IP_D;

                public function Create()
                {
                        //Never delete this line!
                        parent::Create();
        
                        //These lines are parsed on Symcon Startup or Instance creation
                        //You cannot use variables here. Just static values.
                        $this->RegisterPropertyString("CURL_IP", "127.0.0.1");
                        $this->RegisterPropertyString("DRUCKER_IP", "127.0.0.1");
       
                }

		
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
                        
                        $this->IP_C = $this->ReadPropertyString("CURL_IP");
                        $this->IP_D = $this->ReadPropertyString("DRUCKER_IP");
                      
			
		}
		

		
	
	}

?>
