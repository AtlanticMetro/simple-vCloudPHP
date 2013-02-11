<?php



/* ************************************************************************************************************ */

// Get an array of an organization's VDCs. This is their object, of type ..

function getVdcs($sdkOrg, $service, $orgName)
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


function ifDebug($msg = "")
{
    global $debug;
    
    if($debug) { echo($msg . "\n"); }	
}

?>