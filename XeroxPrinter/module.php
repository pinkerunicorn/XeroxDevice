<?php

class XeroxPrinter extends IPSModule
{
    public function Create()
    {
        parent::Create();

        // Eigenschaften registrieren
        $this->RegisterPropertyString('Host', '10.1.20.30');
        $this->RegisterPropertyString('Community', 'public');
        $this->RegisterPropertyInteger('UpdateInterval', 60);
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $host = $this->ReadPropertyString('Host');
        $community = $this->ReadPropertyString('Community');
        $interval = $this->ReadPropertyInteger('UpdateInterval');

        if (empty($host)) {
            $this->SetStatus(104); // IS_INACTIVE
            return;
        }

        $this->SetStatus(102); // IS_ACTIVE

        $oids = [
            'TotalPages' => ['name' => 'Seiten insgesamt', 'oid' => '1.3.6.1.2.1.43.10.2.1.4.1.1'],
            'MonoPages' => ['name' => 'Schwarzweißseiten', 'oid' => '1.3.6.1.4.1.253.8.53.13.2.1.6.1.20.34'],
            'ColorPages' => ['name' => 'Farbseiten', 'oid' => '1.3.6.1.4.1.253.8.53.13.2.1.6.1.20.33'],
            'TonerCyan' => ['name' => 'Restseiten Cyan', 'oid' => '1.3.6.1.2.1.43.11.1.1.9.1.2'],
            'TonerMagenta' => ['name' => 'Restseiten Magenta', 'oid' => '1.3.6.1.2.1.43.11.1.1.9.1.3'],
            'TonerYellow' => ['name' => 'Restseiten Gelb', 'oid' => '1.3.6.1.2.1.43.11.1.1.9.1.4'],
            'TonerBlack' => ['name' => 'Restseiten Schwarz', 'oid' => '1.3.6.1.2.1.43.11.1.1.9.1.1']
        ];

        foreach ($oids as $ident => $data) {
            $this->MaintainSNMPInstance($ident, $data['name'], $data['oid'], $host, $community, $interval);
        }
    }

    private function MaintainSNMPInstance($ident, $name, $oid, $host, $community, $interval)
    {
        $iid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
        if ($iid === false) {
            // Instanz erstellen
            $iid = IPS_CreateInstance("{EC3FE0F1-F688-3D36-95BE-5E3A97ED65A2}");
            IPS_SetParent($iid, $this->InstanceID);
            IPS_SetIdent($iid, $ident);
            IPS_SetName($iid, $name);
        }

        // Konfiguration setzen (Float = Type 2, Integer = Type 1)
        IPS_SetProperty($iid, "IPAddress", $host);
        IPS_SetProperty($iid, "Community", $community);
        IPS_SetProperty($iid, "Version", 1); // SNMPv2c
        IPS_SetProperty($iid, "OID", $oid);
        IPS_SetProperty($iid, "UpdateInterval", $interval * 1000);
        IPS_SetProperty($iid, "Type", 2); // Der Screenshot zeigte Float an, daher Type 2
        
        IPS_ApplyChanges($iid);
    }
}
