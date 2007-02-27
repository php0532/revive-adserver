<?php

/*
+---------------------------------------------------------------------------+
| Max Media Manager v0.3                                                    |
| =================                                                         |
|                                                                           |
| Copyright (c) 2003-2006 m3 Media Services Limited                         |
| For contact details, see: http://www.m3.net/                              |
|                                                                           |
| Copyright (c) 2000-2003 the phpAdsNew developers                          |
| For contact details, see: http://www.phpadsnew.com/                       |
|                                                                           |
| This program is free software; you can redistribute it and/or modify      |
| it under the terms of the GNU General Public License as published by      |
| the Free Software Foundation; either version 2 of the License, or         |
| (at your option) any later version.                                       |
|                                                                           |
| This program is distributed in the hope that it will be useful,           |
| but WITHOUT ANY WARRANTY; without even the implied warranty of            |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
| GNU General Public License for more details.                              |
|                                                                           |
| You should have received a copy of the GNU General Public License         |
| along with this program; if not, write to the Free Software               |
| Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA |
+---------------------------------------------------------------------------+
$Id$
*/

// Require the initialisation file
require_once '../../init.php';

// Required files
require_once MAX_PATH . '/www/admin/config.php';
require_once MAX_PATH . '/www/admin/lib-statistics.inc.php';
require_once MAX_PATH . '/www/admin/lib-size.inc.php';
require_once MAX_PATH . '/www/admin/lib-zones.inc.php';
require_once MAX_PATH . '/lib/max/Delivery/cache.php';

// Register input variables
phpAds_registerGlobal ('listorder', 'orderdirection');

/*-------------------------------------------------------*/
/* Affiliate interface security                          */
/*-------------------------------------------------------*/

MAX_Permission::checkAccess(phpAds_Admin + phpAds_Agency + phpAds_Affiliate);
MAX_Permission::checkAccessToObject('affiliates', $affiliateid);

/*-------------------------------------------------------*/
/* Get preferences                                       */
/*-------------------------------------------------------*/

if (!isset($listorder)) {
    if (isset($session['prefs']['affiliate-zones.php']['listorder'])) {
        $listorder = $session['prefs']['affiliate-zones.php']['listorder'];
    } else {
        $listorder = '';
    }
}

if (!isset($orderdirection)) {
    if (isset($session['prefs']['affiliate-zones.php']['orderdirection'])) {
        $orderdirection = $session['prefs']['affiliate-zones.php']['orderdirection'];
    } else {
        $orderdirection = '';
    }
}

/*-------------------------------------------------------*/
/* HTML framework                                        */
/*-------------------------------------------------------*/

if (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency)) {
    if (isset($session['prefs']['affiliate-index.php']['listorder'])) {
        $navorder = $session['prefs']['affiliate-index.php']['listorder'];
    } else {
        $navorder = '';
    }
    
    if (isset($session['prefs']['affiliate-index.php']['orderdirection'])) {
        $navdirection = $session['prefs']['affiliate-index.php']['orderdirection'];
    } else {
        $navdirection = '';
    }
    
    // Get other affiliates
    $doAffiliates = MAX_DB::factoryDO('affiliates');
    $doAffiliates->addListorderBy($navorder, $navdirection);
    if (phpAds_isUser(phpAds_Admin)) {
    } elseif (phpAds_isUser(phpAds_Agency)) {
        $doAffiliates->agencyid = $agencyid;
    } elseif (phpAds_isUser(phpAds_Affiliate)) {
        $doAffiliates->affiliateid = $affiliateid;
    }
    
    $doAffiliates->find();
    
    while ($doAffiliates->fetch() && $row = $doAffiliates->toArray()) {
        phpAds_PageContext (
            phpAds_buildAffiliateName ($row['affiliateid'], $row['name']),
            "affiliate-zones.php?affiliateid=".$row['affiliateid'],
            $affiliateid == $row['affiliateid']
        );
    }
    
    phpAds_PageShortcut($strAffiliateHistory, 'stats.php?entity=affiliate&breakdown=history&affiliateid='.$affiliateid, 'images/icon-statistics.gif');    
    
    phpAds_PageHeader("4.2.3");
    echo "<img src='images/icon-affiliate.gif' align='absmiddle'>&nbsp;<b>".phpAds_getAffiliateName($affiliateid)."</b><br /><br /><br />";
    
    phpAds_ShowSections(array("4.2.2", "4.2.3", "4.2.4", "4.2.5"));
} else {
    $sections = array();
    $sections[] = "2.1";
    if (phpAds_isAllowed(MAX_AffiliateGenerateCode)) {
        $sections[] = "2.2";
    }    
    phpAds_PageHeader('2.1');
    phpAds_ShowSections($sections);
}

/*-------------------------------------------------------*/
/* Main code                                             */
/*-------------------------------------------------------*/

// Get clients & campaign and build the tree
$doZones = MAX_DB::factoryDO('zones');
$doZones->affiliateid = $affiliateid;
$doZones->addListorderBy($navorder, $navdirection);
$doZones->find();

if (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency) || phpAds_isAllowed(phpAds_AddZone))
{
    echo "<img src='images/icon-zone-new.gif' border='0' align='absmiddle'>&nbsp;";
    echo "<a href='zone-edit.php?affiliateid=".$affiliateid."' accesskey='".$keyAddNew."'>".$strAddNewZone_Key."</a>&nbsp;&nbsp;";
    phpAds_ShowBreak();
}



echo "<br /><br />";
echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>";    


echo "<tr height='25'>";
echo '<td height="25"><b>&nbsp;&nbsp;<a href="affiliate-zones.php?affiliateid='.$affiliateid.'&listorder=name">'.$GLOBALS['strName'].'</a>';

if (($listorder == "name") || ($listorder == ""))
{
    if  (($orderdirection == "") || ($orderdirection == "down"))
    {
        echo ' <a href="affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=up">';
        echo '<img src="images/caret-ds.gif" border="0" alt="" title="">';
    }
    else
    {
        echo ' <a href="affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=down">';
        echo '<img src="images/caret-u.gif" border="0" alt="" title="">';
    }
    echo '</a>';
}

echo '</b></td>';
echo '<td height="25"><b><a href="affiliate-zones.php?affiliateid='.$affiliateid.'&listorder=id">'.$GLOBALS['strID'].'</a>';

if ($listorder == "id")
{
    if  (($orderdirection == "") || ($orderdirection == "down"))
    {
        echo ' <a href="affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=up">';
        echo '<img src="images/caret-ds.gif" border="0" alt="" title="">';
    }
    else
    {
        echo ' <a href="affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=down">';
        echo '<img src="images/caret-u.gif" border="0" alt="" title="">';
    }
    echo '</a>';
}

echo '</b>&nbsp;&nbsp;&nbsp;</td>';
echo '<td height="25"><b><a href="affiliate-zones.php?affiliateid='.$affiliateid.'&listorder=size">'.$GLOBALS['strSize'].'</a>';

if ($listorder == "size")
{
    if  (($orderdirection == "") || ($orderdirection == "down"))
    {
        echo ' <a href="affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=up">';
        echo '<img src="images/caret-ds.gif" border="0" alt="" title="">';
    }
    else
    {
        echo ' <a href="affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=down">';
        echo '<img src="images/caret-u.gif" border="0" alt="" title="">';
    }
    echo '</a>';
}

echo "</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
echo "<td height='25'>&nbsp;</td>";
echo "</tr>";

echo "<tr height='1'><td colspan='4' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";


if ($doZones->getNumRows() == 0)
{
    echo "<tr height='25' bgcolor='#F6F6F6'><td height='25' colspan='4'>";
    echo "&nbsp;&nbsp;".$strNoZones;
    echo "</td></tr>";
    
    echo "<td colspan='4' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td>";
}

$i=0;
while ($doZones->fetch() && $row_zones = $doZones->toArray())
{
    if ($i > 0) echo "<td colspan='4' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td>";
    echo "<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"").">";
    
    echo "<td height='25'>&nbsp;&nbsp;";
    
    $aZoneAds = MAX_cacheGetZoneLinkedAds($row_zones['zoneid'], false);
    if ($aZoneAds['count_active'] > 0) {
        if ($row_zones['delivery'] == phpAds_ZoneBanner) {
            if (count($aZoneAds['lAds']) == 0) {
                // There are linked ads, but no low-priority ads (this will result in serving blanks)
                echo "<acronym title='Warning - There are no low-priority ads linked to this zone'><img src='images/icon-zone-w.gif' align='absmiddle' />&nbsp;</acronym>";
            } else {
                echo "<img src='images/icon-zone.gif' align='absmiddle'>&nbsp;";
            }
        } elseif ($row_zones['delivery'] == phpAds_ZoneInterstitial) {
            echo "<img src='images/icon-interstitial.gif' align='absmiddle'>&nbsp;";
        } elseif ($row_zones['delivery'] == phpAds_ZonePopup) {
            echo "<img src='images/icon-popup.gif' align='absmiddle'>&nbsp;";
        } elseif ($row_zones['delivery'] == phpAds_ZoneText) {
            echo "<img src='images/icon-textzone.gif' align='absmiddle'>&nbsp;";
        } elseif ($row_zones['delivery'] == MAX_ZoneEmail) {
            echo "<img src='images/icon-zone-email.gif' align='absmiddle'>&nbsp;";
        } elseif ($row_zones['delivery'] == MAX_ZoneClick) {
            echo "<img src='images/icon-zone-click.gif' align='absmiddle'>&nbsp;";
        }
    } else {
        if ($row_zones['delivery'] == phpAds_ZoneBanner) {
            echo "<img src='images/icon-zone-d.gif' align='absmiddle'>&nbsp;";
        } elseif ($row_zones['delivery'] == phpAds_ZoneInterstitial) {
            echo "<img src='images/icon-interstitial-d.gif' align='absmiddle'>&nbsp;";
        } elseif ($row_zones['delivery'] == phpAds_ZonePopup) {
            echo "<img src='images/icon-popup-d.gif' align='absmiddle'>&nbsp;";
        } elseif ($row_zones['delivery'] == phpAds_ZoneText) {
            echo "<img src='images/icon-textzone-d.gif' align='absmiddle'>&nbsp;";
        } elseif ($row_zones['delivery'] == MAX_ZoneEmail) {
            echo "<img src='images/icon-zone-email-d.gif' align='absmiddle'>&nbsp;";
        } elseif ($row_zones['delivery'] == MAX_ZoneClick) {
            echo "<img src='images/icon-zone-click-d.gif' align='absmiddle'>&nbsp;";
        }
    }
    
    if (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency) || phpAds_isAllowed(phpAds_EditZone))
        echo "<a href='zone-edit.php?affiliateid=".$affiliateid."&zoneid=".$row_zones['zoneid']."'>".$row_zones['zonename']."</a>";
    else
        echo $row_zones['zonename'];
    
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    echo "</td>";
    
    // ID
    echo "<td height='25'>".$row_zones['zoneid']."</td>";
    
    // Size
    if ($row_zones['delivery'] == phpAds_ZoneText)
    {
        echo "<td height='25'>".$strCustom." (".$strTextAdZone.")</td>";
    }
    else
    {
        if ($row_zones['width'] == -1) $row_zones['width'] = '*';
        if ($row_zones['height'] == -1) $row_zones['height'] = '*';
        
        echo "<td height='25'>".phpAds_getBannerSize($row_zones['width'], $row_zones['height'])."</td>";
    }
    
    echo "<td>&nbsp;</td>";
    echo "</tr>";
    
    // Description
    echo "<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"").">";
    echo "<td>&nbsp;</td>";
    echo "<td height='25' colspan='3'>".stripslashes($row_zones['description'])."</td>";
    echo "</tr>";
    
    echo "<tr height='1'>";
    echo "<td ".($i%2==0?"bgcolor='#F6F6F6'":"")."><img src='images/spacer.gif' width='1' height='1'></td>";
    echo "<td colspan='3' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td>";
    echo "</tr>";
    echo "<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"").">";
    
    // Empty
    echo "<td>&nbsp;</td>";
    
    // Button 1, 2 & 3
    echo "<td height='25' colspan='3'>";
    if (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency) || phpAds_isAllowed(phpAds_LinkBanners)) echo "<a href='zone-include.php?affiliateid=".$affiliateid."&zoneid=".$row_zones['zoneid']."'><img src='images/icon-zone-linked.gif' border='0' align='absmiddle' alt='$strIncludedBanners'>&nbsp;$strIncludedBanners</a>&nbsp;&nbsp;&nbsp;&nbsp;";
    echo "<a href='zone-probability.php?affiliateid=".$affiliateid."&zoneid=".$row_zones['zoneid']."'><img src='images/icon-zone-probability.gif' border='0' align='absmiddle' alt='$strProbability'>&nbsp;$strProbability</a>&nbsp;&nbsp;&nbsp;&nbsp;";
    if (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency) || phpAds_isAllowed(MAX_AffiliateGenerateCode)) echo "<a href='zone-invocation.php?affiliateid=".$affiliateid."&zoneid=".$row_zones['zoneid']."'><img src='images/icon-generatecode.gif' border='0' align='absmiddle' alt='$strInvocationcode'>&nbsp;$strInvocationcode</a>&nbsp;&nbsp;&nbsp;&nbsp;";
    if (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency) || phpAds_isAllowed(phpAds_DeleteZone)) echo "<a href='zone-delete.php?affiliateid=".$affiliateid."&zoneid=".$row_zones['zoneid']."&returnurl=affiliate-zones.php'".phpAds_DelConfirm($strConfirmDeleteZone)."><img src='images/icon-recycle.gif' border='0' align='absmiddle' alt='$strDelete'>&nbsp;$strDelete</a>&nbsp;&nbsp;&nbsp;&nbsp;";
    echo "</td></tr>";
    
    $i++;
}

if (phpAds_dbNumRows($res_zones) > 0)
{
    echo "<tr height='1'><td colspan='4' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
}

echo "</table>";
echo "<br /><br />";



/*-------------------------------------------------------*/
/* Store preferences                                     */
/*-------------------------------------------------------*/

$session['prefs']['affiliate-zones.php']['listorder'] = $listorder;
$session['prefs']['affiliate-zones.php']['orderdirection'] = $orderdirection;

phpAds_SessionDataStore();



/*-------------------------------------------------------*/
/* HTML framework                                        */
/*-------------------------------------------------------*/

phpAds_PageFooter();

?>
