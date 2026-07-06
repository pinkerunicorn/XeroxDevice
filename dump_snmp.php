<?php
foreach(IPS_GetInstanceList() as $id) {
    $inst = IPS_GetInstance($id);
    if(strpos($inst["ModuleInfo"]["ModuleName"], "SNMP") !== false) {
        echo "Module: " . $inst["ModuleInfo"]["ModuleName"] . " (GUID: " . $inst["ModuleInfo"]["ModuleID"] . ")\n";
        echo "Config: " . IPS_GetConfiguration($id) . "\n\n";
        break;
    }
}
?>
