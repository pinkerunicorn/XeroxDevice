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
        $this->RegisterVariableInteger('TotalPages', 'Seiten insgesamt', '', 10);
        $this->RegisterVariableInteger('MonoPages', 'Schwarzweißseiten', '', 20);
        $this->RegisterVariableInteger('ColorPages', 'Farbseiten', '', 30);
        
        $this->RegisterVariableInteger('TonerCyan', 'Restseiten Cyan', '', 40);
        $this->RegisterVariableInteger('TonerMagenta', 'Restseiten Magenta', '', 50);
        $this->RegisterVariableInteger('TonerYellow', 'Restseiten Gelb', '', 60);
        $this->RegisterVariableInteger('TonerBlack', 'Restseiten Schwarz', '', 70);
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

        // Standard-OIDs (werden falls ntig spter angepasst)
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
            // SNMPv2 Abfrage
            // snmp2_get liefert z.B. "INTEGER: 2520" oder "Counter32: 2520"
            $result = @snmp2_get($host, $community, $oid);
            
            if ($result !== false) {
                // Wert aus dem String extrahieren (alles nach dem Doppelpunkt)
                $parts = explode(':', $result);
                $value = count($parts) > 1 ? trim($parts[1]) : trim($result);
                
                // Ggf. "Gauge32" oder andere Prfixe entfernen falls Doppelpunkt fehlte
                $value = preg_replace('/[^0-9]/', '', $value);

                if (is_numeric($value)) {
                    $intValue = (int)$value;
                    $this->SendDebug("SNMP", "$ident ($oid) = $intValue", 0);
                    $this->SetValue($ident, $intValue);
                } else {
                    $this->SendDebug("SNMP", "$ident ($oid) = Parse Error ($result)", 0);
                }
            } else {
                $this->SendDebug("SNMP", "Fehler beim Abrufen von $ident ($oid)", 0);
            }
            
            // Kleine Pause um den Drucker nicht zu spammen
            IPS_Sleep(50);
        }
    }
}
