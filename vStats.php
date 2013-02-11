<?php

echo("<PRE>");

require_once dirname(__FILE__) . '/config.php';

require_once 'simple-vCloud.php';

$vCloud = VMware_VCloud_SDK_Service::getService();

if ( !($adminOrg = adminLogin($user, $pswd, $server, $org, $vCloud )) )
{
    die("Login Failed");
}

$vdcs = getVdcs($adminOrg,$vCloud,$org);

foreach($vdcs as $vdc)
{
    ifDebug("Processing Org VDC : " . $vdc->get_name());
    
    ifDebug("\tDescription : " . $vdc->getDescription());
    ifDebug("\tStatus : " . $vdc->getIsEnabled());
    ifDebug("\tAllocation Model: " . $vdc->getAllocationModel());
    
    ifDebug("\tCompute Capacity -- ");
    ifDebug("\t\tCPU Size : " . $vdc->getVCpuInMhz() . "Mhz");

    $computeCapacity = $vdc->getComputeCapacity();       

    $cpuDetails = $computeCapacity->getCpu();
    
    ifDebug("\t\tCPU Used : " . $cpuDetails->getUsed());
    ifDebug("\t\tCPU Overhead : " . $cpuDetails->getOverhead());
    ifDebug("\t\tCPU Reserved : " . $cpuDetails->getReserved());
    
    $ramDetails = $computeCapacity->getMemory();
    
    ifDebug("\t\tRAM Used : " . $ramDetails->getUsed());
    ifDebug("\t\tRAM Overhead : " . $ramDetails->getOverhead());
    ifDebug("\t\tRAM Reserved : " . $ramDetails->getReserved());   

    $totals = array('ram' => 0, 'cpu' => 0, 'storage' => 0);
    
    $resourceEntities =  $vdc->getResourceEntities();
    ifDebug();    
foreach($resourceEntities->getResourceEntity() as $obj)
{

    if(preg_match("/vApp/",$obj->get_type()))
    {

        
        $sdkvApp = $vCloud->createSDKObj($obj->get_href());
        
        $vApp = $sdkvApp->getVApp();
        ifDebug("\t--- Resource :: vApp : " . $vApp->get_name());        
        
        $children = $vApp->getChildren();
        
        foreach($children->getVm() as $vm)
        {


            ifDebug("\t\t\tVirtual Machine : " . $vm->get_name());
            
            $sdkVm = $vCloud->createSDKObj($vm->get_href());

            $cpuZ = $sdkVm->getVirtualCpu();
            $cpuY = $cpuZ->getVirtualQuantity();
            $cpu = $cpuY->valueOf;

            $memZ = $sdkVm->getVirtualMemory();
            $memY = $memZ->getVirtualQuantity();
            $mem = $memY->valueOf;            
           
            $diskZ = $sdkVm->getVirtualDisks();

           
            foreach($diskZ->getItem() as $disk)
            {
                
  
                if(preg_match("/Hard\ disk/",$disk->getDescription()->valueOf))
                {
                    $hostResource = $disk->getHostResource();
                    $hr = $hostResource[0]->get_anyAttributes();
                    ifDebug("\t\t\t\tHard Disk : " . $hr['capacity'] . "MB");
                    $totals['storage'] += $hr['capacity'];
                    
                }
                
                
            }
            
            
            
            
            $nics = $sdkVm->getVirtualNetworkCards();
            
            ifDebug("\t\t\t\tCPU: " . $cpu . " vCPU");
            ifDebug("\t\t\t\tMemory: " . $mem . "MB");
            //ifDebug("\t\t\t\tDisks: " . $disks);
            //ifDebug("\t\t\t\tNICS: " . $nics);

            
            $totals['cpu'] += $cpu;
            $totals['ram'] += $mem;

        }
        
        
    } elseif(preg_match("/media/",$obj->get_type()))
    {
        ifDebug("\t--- Resource :: Media Image : " . $obj->get_name());    
    }

}
    
    //ifDebug("\t\Storage Used : " . $storageDetails->getUsed());
    //ifDebug("\t\tStorage Overhead : " . $storageDetails->getOverhead());
    //ifDebug("\t\tStorage Reserved : " . $storageDetails->getReserved());   
    
    ifDebug("\tMaximum VMs: " . $vdc->getVmQuota());    
    ifDebug();

    
    ifDebug(print_r($totals,true));
}






if(isset($vCloud))
{
	// log out
	$vCloud->logout();
}


?>