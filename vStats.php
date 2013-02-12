<?php
require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/simple-vCloud.php';

// Organization name    /// Beware of this, case sensitive. Must match whats in vCD web interface.
$org = 'AMC';    

echo("<PRE>");


/* ************************************************************************* */

$vCloud = VMware_VCloud_SDK_Service::getService();

if ( !($adminOrg = adminLogin($user, $pswd, $server, $org, $vCloud )) )
{
    die("Login Failed");
}



$vdcs = getVdcs($adminOrg,$vCloud);

// Loop through each VDC
foreach($vdcs as $vdc)
{

    // Print each vDC's info
    //print_r(getVdcInfo($vdc));
    
    if($vdc)
    {
        $resources =  getVdcResources($vCloud,$vdc);
    
        if($resources)
        {

            echo("Displaying vApps\n-----------------------\n");
            print_r($resources['vApp']);

            echo("\n\nDisplaying vAppTemplates\n-----------------------\n");
            print_r($resources['vAppTemplate']);
            
            echo("\n\nDisplaying Media\n-----------------------\n");
            print_r($resources['media']);

        }
    }
}



// Logout
if(isset($vCloud))
{
	// log out
	$vCloud->logout();
}


?>