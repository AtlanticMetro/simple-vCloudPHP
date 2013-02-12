<?php



/* ************************************************************************************************************ */

// Get an array of an organization's VDCs. This is their object, of type ..

function getVdcs($sdkOrg, $service)
{
    
    // create an SDK vDC object
    $vdcRefs = $sdkOrg->getAdminVdcRefs();
    if (0 == count($vdcRefs))
    {
        ifDebug("No vDCs found");
    }
   
    $vDcs = array();
    
    for($vdcCnt = 0; $vdcCnt < count($vdcRefs); $vdcCnt++)
    {        
        $sdkVdc = $service->createSDKObj($vdcRefs[$vdcCnt]);    
        
        $vDcs[] = $sdkVdc->getAdminVdc($vdcRefs[$vdcCnt]);
    }
	
    return $vDcs;

}

/* ************************************************************************************************************ */

// Does an administrative login to the specified server and organization.
//
// You pass a service object (VMware_VCloud_SDK_Service::getService()) to it and it returns byreference but 
// returns true or false for success.
// 

function adminLogin($user, $pswd, $server, $orgName, &$service)
{
    global $httpConfig;
    
    // parameters validation
    if (!isset($server) || !isset($user) || !isset($pswd) || !isset($orgName))
    {
	ifDebug("Incomplete credentials specified");
        return false;
    }


    // Do the actual login
    try {
        $service->login($server, array('username'=>$user, 'password'=>$pswd), $httpConfig);	
    } catch (Exception $e) {
        ifDebug("Caught " . $e->getMessage());
        return false;
    }
	
    
    /////////////////////////
    
    // create an SDK Admin object
    try
    {
        $sdkAdmin = $service->createSDKAdminObj();
    } catch (Exception $e) {
        ifDebug("Caught " . $e->getMessage());
        return false;
    }
    
    // get references to administrative organization entities
    $adminOrgRefs = $sdkAdmin->getAdminOrgRefs($orgName);
    if (0 == count($adminOrgRefs))
    {
        ifDebug("No organization with $orgName is found");
    }
	
    $adminOrgRef = $adminOrgRefs[0];
    $sdkOrg = $service->createSDKObj($adminOrgRef);
    
    return $sdkOrg;    
}

/* ************************************************************************** */

function getVdcInfo($vdc)
{
    $computeCapacity = $vdc->getComputeCapacity();       
    $cpuDetails = $computeCapacity->getCpu();
    $ramDetails = $computeCapacity->getMemory();

    $vdcInfo = array(
                     
                    'name' => $vdc->get_name(),
                    'description' => $vdc->getDescription(),
                    'status' => $vdc->getIsEnabled(),
                    'allocation_model' => $vdc->getAllocationModel(),
                    'maximum_vm' => $vdc->getVmQuota(),
                    'compute_capacity' => array(
                        'cpu_size' => $vdc->getVCpuInMhz(), // in Mhz
                        'cpu_used' => $cpuDetails->getUsed(),
                        'cpu_overhead' => $cpuDetails->getOverhead(),
                        'cpu_reserved' => $cpuDetails->getReserved()
                    ),
                    'memory_capacity' => array(
                        'memory_used' => $ramDetails->getUsed(),
                        'memory_overhead' => $ramDetails->getOverhead(),
                        'memory_reserved' => $ramDetails->getReserved()
                    )
    );
    
    
    return $vdcInfo;
}

/* ************************************************************************** */

function getVdcResources($vCloud,$vdc)
{
        
    $resources = $vdc->getResourceEntities();

    $vApps = array();
    $vAppTemplates = array();
    $media = array();
        
    foreach($resources->getResourceEntity() as $obj)
    {
    //ifDebug("Resource Type: [" . $obj->get_type() . "]");

        if(preg_match("/\.vApp\+/",$obj->get_type()))
        {
            $curVapp = null;

            $sdkvApp = $vCloud->createSDKObj($obj->get_href());
        
            $vApp = $sdkvApp->getVApp();
            $curVapp['name'] = $vApp->get_name();
            $curVapp['totals']['cpu'] = 0;
            $curVapp['totals']['memory'] = 0;
            $curVapp['totals']['storage'] = 0;
            
            $myVms = array();
            
            $children = $vApp->getChildren();
            
            foreach($children->getVm() as $vm)
            {

                $curVm['name'] = $vm->get_name();
                $curVm['href'] = $vm->get_href();
                $curVm['type'] = $vm->get_type();
                
                $curVm['totals']['cpu'] = 0;                
                $curVm['totals']['memory'] = 0;
                $curVm['totals']['storage'] = 0;
                
                $sdkVm = $vCloud->createSDKObj($vm->get_href());


                $cpuZ = $sdkVm->getVirtualCpu();
                $cpuY = $cpuZ->getVirtualQuantity();
                $curVm['cpu'] = $cpuY->valueOf;
                
                $memZ = $sdkVm->getVirtualMemory();
                $memY = $memZ->getVirtualQuantity();
                $curVm['memory'] = $memY->valueOf;            
           
                $curVm['totals']['cpu'] = $curVm['cpu'];
                $curVm['totals']['memory'] = $curVm['memory'];

                $curVapp['totals']['cpu'] += $curVm['cpu'];
                $curVapp['totals']['memory'] += $curVm['memory'];

                
                $diskZ = $sdkVm->getVirtualDisks();
                
                $myDisks = array();

                foreach($diskZ->getItem() as $disk)
                {  
                    if(preg_match("/Hard\ disk/",$disk->getDescription()->valueOf))
                    {
                        
                        $hostResource = $disk->getHostResource();
                        $hr = $hostResource[0]->get_anyAttributes();

                        $myDisks[] = $hr['capacity'];
                        $curVm['totals']['storage'] += $hr['capacity'];                    
                        $curVapp['totals']['storage'] += $hr['capacity'];                    
                    }
                }
            
                $curVm['storage'] = $myDisks;
            
            
                $nics = $sdkVm->getVirtualNetworkCards();
                
                //ifDebug("\t\t\t\tNICS: " . $nics);
                
                $curVapp['vm'][] = $curVm;
        }
        $vApps[] = $curVapp;                
    }
    elseif(preg_match("/\.vAppTemplate\+/",$obj->get_type()))
    {
        $vAppTemplate['name'] = $obj->get_name();
        $vAppTemplate['href'] = $obj->get_href();
        $vAppTemplate['type'] = $obj->get_type();        
        $vAppTemplates[] = $vAppTemplate;
    }
    elseif(preg_match("/\.media\+/",$obj->get_type()))
    {
        $media_item['name'] = $obj->get_name();
        $media_item['href'] = $obj->get_href();
        $media_item['type'] = $obj->get_type();
        
        $media[] = $media_item;
    }
    else
    {
        //print_r(array('name' => $obj->get_name, 'href'=> $obj->get_href(), 'type' => $obj->get_type()));
    }

}
    
    if($vApps || $vAppTemplates || $media)
    {
        return array('vApp' => $vApps, 'vAppTemplate' => $vAppTemplates, 'media' => $media);
    }
    else
        return false;
    
}

/* ************************************************************************** */
function ifDebug($msg = "")
{
    global $debug;
    
    if($debug) { echo($msg . "\n"); }	
}

?>