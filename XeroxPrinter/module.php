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

        // Timer registrieren
        $this->RegisterTimer('UpdateTimer', 0, 'XEROX_UpdateStatus($_IPS[\'TARGET\']);');

        // Variablen registrieren
        $this->RegisterVariableFloat('TotalPages', 'Seiten insgesamt', '', 10);
        $this->RegisterVariableFloat('MonoPages', 'Schwarzweißseiten', '', 20);
        $this->RegisterVariableFloat('ColorPages', 'Farbseiten', '', 30);
        
        $this->RegisterVariableFloat('TonerCyan', 'Restseiten Cyan', '', 40);
        $this->RegisterVariableFloat('TonerMagenta', 'Restseiten Magenta', '', 50);
        $this->RegisterVariableFloat('TonerYellow', 'Restseiten Gelb', '', 60);
        $this->RegisterVariableFloat('TonerBlack', 'Restseiten Schwarz', '', 70);
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $interval = $this->ReadPropertyInteger('UpdateInterval');
        if ($interval > 0) {
            $this->SetTimerInterval('UpdateTimer', $interval * 1000);
            $this->UpdateStatus();
        } else {
            $this->SetTimerInterval('UpdateTimer', 0);
        }
    }

    public function UpdateStatus()
    {
        $host = $this->ReadPropertyString('Host');
        $community = $this->ReadPropertyString('Community');

        if (empty($host)) {
            $this->SendDebug("Update", "Kein Host konfiguriert.", 0);
            return;
        }

        // Standard-OIDs
        $oids = [
            'TotalPages' => '1.3.6.1.2.1.43.10.2.1.4.1.1',
            'MonoPages' => '1.3.6.1.4.1.253.8.53.13.2.1.6.1.20.34',
            'ColorPages' => '1.3.6.1.4.1.253.8.53.13.2.1.6.1.20.33',
            'TonerCyan' => '1.3.6.1.2.1.43.11.1.1.9.1.2',
            'TonerMagenta' => '1.3.6.1.2.1.43.11.1.1.9.1.3',
            'TonerYellow' => '1.3.6.1.2.1.43.11.1.1.9.1.4',
            'TonerBlack' => '1.3.6.1.2.1.43.11.1.1.9.1.1'
        ];

        foreach ($oids as $ident => $oid) {
            $cmd = sprintf('snmpget -v 2c -c %s -Oqv %s %s 2>&1', 
                escapeshellarg($community), 
                escapeshellarg($host), 
                escapeshellarg($oid)
            );
            
            $output = [];
            $returnVar = -1;
            exec($cmd, $output, $returnVar);
            
            if ($returnVar === 0 && isset($output[0])) {
                $value = trim($output[0]);
                // Bereinigen, falls doch Text drin steht
                $value = preg_replace('/[^0-9.]/', '', $value);
                
                if (is_numeric($value)) {
                    $this->SendDebug("SNMP", "$ident ($oid) = $value", 0);
                    $this->SetValue($ident, (float)$value);
                } else {
                    $this->SendDebug("SNMP", "$ident ($oid) = ungültiger Wert ($output[0])", 0);
                }
            } else {
                $error = implode(" ", $output);
                $this->SendDebug("SNMP-Error", "Befehl fehlgeschlagen (Code: $returnVar). Installiertes snmp-Paket im Docker-Container prüfen! Output: $error", 0);
            }
            
            // Kleine Pause
            IPS_Sleep(50);
        }
    }
}
