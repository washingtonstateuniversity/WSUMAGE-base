<?php
ob_start();

//note this is all just free wheel atm and needs to be refactored big time.
//just saying
//also it requires that you have the storeutilities ext installed.

/*
NOTE that this requires
	:: storeutilities
	:: fastsimpleimport
*/

//just as a guide, no real purpose
echo getcwd() . " (working from)\n";
$argv = $_SERVER['argv'];

//exit();die();
//set up the store instance
require_once "app/Mage.php";
umask(0);
Mage::app();
Mage::app()->getTranslator()->init('frontend');
Mage::getSingleton('core/session', array('name' => 'frontend'));
Mage::registry('isSecureArea'); // acting is if we are in the admin
Mage::app('admin')->setUseSessionInUrl(false);
Mage::getConfig()->init();
/**
 * Get the resource model
 */
$resource = Mage::getSingleton('core/resource');
 
/**
 * Retrieve the read connection
 */
$readConnection = $resource->getConnection('core_read');
 
/**
 * Retrieve the write connection
 */
$writeConnection = $resource->getConnection('core_write');

// switch off error reporting
error_reporting ( E_ALL & ~ E_NOTICE );
 
$cDat = new Mage_Core_Model_Config();
$settingsarray = Mage::helper('storeutilities/utilities')->csv_to_array('staging/scripts/settings.config');
foreach($settingsarray as $item){
    $val =  $item['value']=="NULL"?NULL:$item['value'];
    $cDat->saveConfig($item['path'], $val, 'default', 0);
}
$cDat->saveConfig('admin/url/custom', 'http://store.admin.mage.dev/', 'default', 0);
 
echo "Applying the default multi-store setup\n";


$defaultCmsPage = '
<div class="row main-ad-block">
	{$CMShtml}
	<div style="clear: both;"></div>
</div>
<div class="row ">
	<div class="column twelve-twelfths">
		{{block type="tag/popular" template="tag/popular.phtml"}}
	</div>
</div>
<div class="row ">
	<div class="column nine-twelfths home-spot">
		<h1>Sites in the center</h1>
		<p>{{block type="catalog/product" stores_per="5" products_per="2" panles_per="3" template="custom_block/site_list.phtml"}}</p>
	</div>
	<div class="column three-twelfths">
		<p class="home-callout"><a href="{{store direct_url="#"}}"> <img src="{{storemedia url="/feature_store_ad.jpg"}}" alt="" border="0" /> </a></p>
	</div>
</div>';

$SU_Helper = Mage::helper('storeutilities/utilities');

$newRootCat = $SU_Helper->make_category("General store root");
if($newRootCat>0){
    $SU_Helper->reparentCategory($newRootCat,18);
    $siteId = $SU_Helper->make_website(array('code'=>'generalstore','name'=>'General store'));
    if( $siteId>0 ){
        $storeGroupId = $SU_Helper->make_storeGroup( array('name'=>'General Store'), 'general.store.mage.dev', $siteId, $newRootCat );
        if( $storeGroupId>0 ){
            $storeId = $SU_Helper->make_store( $siteId, $storeGroupId, array('code'=>'generalstore','name'=>'base default veiw') );
            if( $storeId>0 ){
                $SU_Helper->moveStoreProducts( $siteId, $storeId, $newRootCat );
				
				
				$storeCmsLayouts = array(
					'col1'=>array(
						'twelfths'=>'seven-twelfths',
						'blocks'=>array(
							'blocktop'=>'<a href="{{store direct_url="#"}}"> <img src="{{storemedia url="/lefttop_ad_block.jpg"}}" alt="" border="0" /> </a>',
							'blockbottom'=>'<img src="{{storemedia url="/rightbottom_ad_block.jpg"}}" alt="" border="0" />'
						)
					),
					'col2'=>array(
						'twelfths'=>'five-twelfths',
						'blocks'=>array(
							'blocktop'=>'<img src="{{storemedia url="/home_main_callout.jpg"}}" alt=""  border="0" />',
							'blockbottom'=>'<img src="{{storemedia url="/free_shipping_callout.jpg"}}" alt=""  border="0" />'
						)
					)
				);
				$CMShtml="";
				foreach($storeCmsLayouts as $col=>$part){
					$CMShtml.="<div class='column ${$part['twelfths']}'>${$part['blocks']['blocktop']}${$part['blocks']['blockbottom']}</div>";
				}
				
                $SU_Helper->createCmsPage($storeId,array(
                    'title' => 'General store',
                    'identifier' => 'home',
                    'content_heading' => '',
                    'is_active' => 1,
                    'stores' => array($storeId),//available for all store views
                    'content' => str_replace('{$CMShtml}',$CMShtml,$defaultCmsPage)
                ));
                $cDat->saveConfig('wsu_themecontrol_layout/responsive/max_width', 'default', 'websites', $siteId);
                $cDat->saveConfig('wsu_themecontrol_layout/responsive/fluid_width', 'hybrid', 'websites', $siteId);
            }
        }
    }
}

$newRootCat = $SU_Helper->make_category("Student store root");
if($newRootCat>0){
    $SU_Helper->reparentCategory($newRootCat,10);
    $siteId = $SU_Helper->make_website(array('code'=>'studentstore','name'=>'Student store'));
    if( $siteId>0 ){
        $storeGroupId = $SU_Helper->make_storeGroup( array('name'=>'Student Store'), 'student.store.mage.dev', $siteId, $newRootCat );
        if( $storeGroupId>0 ){
            $storeId = $SU_Helper->make_store( $siteId, $storeGroupId, array('code'=>'studentstore','name'=>'base default veiw') );
            if( $storeId>0 ){
                $SU_Helper->moveStoreProducts( $siteId, $storeId, $newRootCat );
				$storeCmsLayouts = array(
					'col1'=>array(
						'twelfths'=>'seven-twelfths',
						'blocks'=>array(
							'blocktop'=>'<a href="{{store direct_url="#"}}"> <img src="{{storemedia url="/lefttop_ad_block.jpg"}}" alt="" border="0" /> </a>',
							'blockbottom'=>'<img src="{{storemedia url="/rightbottom_ad_block.jpg"}}" alt="" border="0" />'
						)
					),
					'col2'=>array(
						'twelfths'=>'five-twelfths',
						'blocks'=>array(
							'blocktop'=>'<img src="{{storemedia url="/home_main_callout.jpg"}}" alt=""  border="0" />',
							'blockbottom'=>'<img src="{{storemedia url="/free_shipping_callout.jpg"}}" alt=""  border="0" />'
						)
					)
				);
				$CMShtml="";
				foreach($storeCmsLayouts as $col=>$part){
					$CMShtml.="<div class='column ${$part['twelfths']}'>${$part['blocks']['blocktop']}${$part['blocks']['blockbottom']}</div>";
				}
                $SU_Helper->createCmsPage($storeId,array(
                    'title' => 'Student store',
                    'identifier' => 'home',
                    'content_heading' => '',
                    'is_active' => 1,
                    'stores' => array($storeId),//available for all store views
                    'content' => str_replace('{$CMShtml}',$CMShtml,$defaultCmsPage)
                ));
				$cDat->saveConfig('wsu_themecontrol_design/spine/spine_color', 'crimson', 'websites', $siteId);
				$cDat->saveConfig('wsu_themecontrol_design/spine/spine_tool_bar_color', 'lighter', 'websites', $siteId);
				$cDat->saveConfig('wsu_themecontrol_design/spine/spine_bleed', '0', 'websites', $siteId);
				$cDat->saveConfig('wsu_themecontrol_design/spine/max_width', '1188', 'websites', $siteId);
				$cDat->saveConfig('wsu_themecontrol_design/spine/fluid_width', 'hybrid', 'websites', $siteId);
            }
        }
    }
}


$newRootCat = $SU_Helper->make_category("Tech store root");
if($newRootCat>0){
    $SU_Helper->reparentCategory($newRootCat,13);
    $siteId = $SU_Helper->make_website(array('code'=>'techstore','name'=>'Tech store'));
    if( $siteId>0 ){
        $storeGroupId = $SU_Helper->make_storeGroup( array('name'=>'Tech Store'), 'tech.store.mage.dev', $siteId, $newRootCat );
        if( $storeGroupId>0 ){
            $storeId = $SU_Helper->make_store( $siteId, $storeGroupId, array('code'=>'techstore','name'=>'base default veiw') );
            if( $storeId>0 ){
                $SU_Helper->moveStoreProducts( $siteId, $storeId, $newRootCat );
				$storeCmsLayouts = array(
					'col1'=>array(
						'twelfths'=>'seven-twelfths',
						'blocks'=>array(
							'blocktop'=>'<a href="{{store direct_url="#"}}"> <img src="{{storemedia url="/lefttop_ad_block.jpg"}}" alt="" border="0" /> </a>',
							'blockbottom'=>'<img src="{{storemedia url="/rightbottom_ad_block.jpg"}}" alt="" border="0" />'
						)
					),
					'col2'=>array(
						'twelfths'=>'five-twelfths',
						'blocks'=>array(
							'blocktop'=>'<img src="{{storemedia url="/home_main_callout.jpg"}}" alt=""  border="0" />',
							'blockbottom'=>'<img src="{{storemedia url="/free_shipping_callout.jpg"}}" alt=""  border="0" />'
						)
					)
				);
				$CMShtml="";
				foreach($storeCmsLayouts as $col=>$part){
					$CMShtml.="<div class='column ${$part['twelfths']}'>${$part['blocks']['blocktop']}${$part['blocks']['blockbottom']}</div>";
				}
                $SU_Helper->createCmsPage($storeId,array(
                    'title' => 'Tech store',
                    'identifier' => 'home',
                    'content_heading' => '',
                    'is_active' => 1,
                    'stores' => array($storeId),//available for all store views
                    'content' => str_replace('{$CMShtml}',$CMShtml,$defaultCmsPage)
                ));
                $cDat->saveConfig('wsu_themecontrol_design/spine/spine_color', 'transparent', 'websites', $siteId);
                $cDat->saveConfig('wsu_themecontrol_design/spine/spine_tool_bar_color', 'darkest', 'websites', $siteId);
            }
        }
    }
}



$websiteCodes = 'eventstore';//array('eventstore');
$storeCodes = 'eventstore';//array('eventstore');
echo $websiteCodes.'::websiteCodes'."\n";
echo $storeCodes.'::storeCodes'."\n";

$newRootCat = $SU_Helper->make_category("Event store root");
if($newRootCat>0){
    $siteId = $SU_Helper->make_website(array('code'=>$websiteCodes,'name'=>'Event store'));
    if( $siteId>0 ){
        $storeGroupId = $SU_Helper->make_storeGroup( array('name'=>'Events Store'), 'events.store.mage.dev', $siteId, $newRootCat );
        if( $storeGroupId>0 ){
            $storeId = $SU_Helper->make_store( $siteId, $storeGroupId, array('code'=>$storeCodes,'name'=>'base default veiw') );
            if( $storeId>0 ){
                $SU_Helper->moveStoreProducts( $siteId, $storeId, $newRootCat );
				$storeCmsLayouts = array(
					'col1'=>array(
						'twelfths'=>'seven-twelfths',
						'blocks'=>array(
							'blocktop'=>'<a href="{{store direct_url="#"}}"> <img src="{{storemedia url="/lefttop_ad_block.jpg"}}" alt="" border="0" /> </a>',
							'blockbottom'=>'<img src="{{storemedia url="/rightbottom_ad_block.jpg"}}" alt="" border="0" />'
						)
					),
					'col2'=>array(
						'twelfths'=>'five-twelfths',
						'blocks'=>array(
							'blocktop'=>'<img src="{{storemedia url="/trasparent-placeholder-missing-image.png"}}" alt=""  border="0" />',
							'blockbottom'=>'<img src="{{storemedia url="/trasparent-placeholder-missing-image.png"}}" alt=""  border="0" />'
						)
					)
				);
				$CMShtml="";
                $SU_Helper->createCmsPage($storeId,array(
                    'title' => 'Event store',
                    'identifier' => 'home',
                    'content_heading' => '',
                    'is_active' => 1,
                    'stores' => array($storeId),//available for all store views
                    'content' => str_replace('{$CMShtml}',$CMShtml,$defaultCmsPage)
                ));
                include_once('staging/scripts/sample-events.php');
                $cDat->saveConfig('wsu_themecontrol_design/spine/spine_color', 'darkest', 'websites', $siteId);
                $cDat->saveConfig('wsu_themecontrol_design/spine/spine_tool_bar_color', 'crimson', 'websites', $siteId);
            }
        }
    }
}


$output = ob_get_clean();
echo "name=post-install-settings result=True changed=True comment='$output'";