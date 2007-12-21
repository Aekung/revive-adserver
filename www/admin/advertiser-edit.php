<?php

/*
+---------------------------------------------------------------------------+
| Openads v${RELEASE_MAJOR_MINOR}                                                              |
| ============                                                              |
|                                                                           |
| Copyright (c) 2003-2007 Openads Limited                                   |
| For contact details, see: http://www.openads.org/                         |
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
require_once MAX_PATH . '/lib/OA/Dal.php';
require_once MAX_PATH . '/lib/max/Admin/Languages.php';
require_once MAX_PATH . '/lib/max/Admin/Redirect.php';
require_once MAX_PATH . '/lib/OA/Admin/Menu.php';
require_once MAX_PATH . '/www/admin/config.php';
require_once MAX_PATH . '/www/admin/lib-statistics.inc.php';

// Register input variables
phpAds_registerGlobalUnslashed(
     'errormessage'
    ,'clientname'
    ,'contact'
    ,'comments'
    ,'email'
    ,'clientlanguage'
    ,'clientreportlastdate'
    ,'clientreportprevious'
    ,'clientreportdeactivate'
    ,'clientreport'
    ,'clientreportinterval'
    ,'submit'
);


// Security check
OA_Permission::enforceAccount(OA_ACCOUNT_ADVERTISER, OA_ACCOUNT_MANAGER);
OA_Permission::enforceAccessToObject('clients', $clientid);

/*-------------------------------------------------------*/
/* Process submitted form                                */
/*-------------------------------------------------------*/

if (isset($submit)) {
    $errormessage = array();
    // Get previous values
    if (!empty($clientid)) {
        $doClients = OA_Dal::factoryDO('clients');
        if ($doClients->get($clientid)) {
            $client = $doClients->toArray();
        }
    }
    // Name
    if ( OA_Permission::isAccount(OA_ACCOUNT_ADMIN) || OA_Permission::isAccount(OA_ACCOUNT_MANAGER) ) {
        $client['clientname'] = trim($clientname);
    }
    // Default fields
    $client['contact']      = trim($contact);
    $client['email']      = trim($email);
    $client['language']   = trim($clientlanguage);
    $client['comments']  = trim($comments);

    // Reports
    $client['report'] = isset($clientreport) ? 't' : 'f';
    $client['reportdeactivate'] = isset($clientreportdeactivate) ? 't' : 'f';
    $client['reportinterval'] = (int)$clientreportinterval;
    if ($clientreportlastdate == '' || $clientreportlastdate == '0000-00-00' ||  $clientreportprevious != $client['report']) {
        $client['reportlastdate'] = date ("Y-m-d");
    }
    if (count($errormessage) == 0) {
        if (empty($clientid)) {
            $doClients = OA_Dal::factoryDO('clients');
            $doClients->setFrom($client);
            $doClients->updated = OA::getNow();

            // Insert
            $clientid = $doClients->insert();

            // Go to next page
            MAX_Admin_Redirect::redirect("campaign-edit.php?clientid=$clientid");
        } else {
            $doClients = OA_Dal::factoryDO('clients');
            $doClients->get($clientid);
            $doClients->setFrom($client);
            $doClients->updated = OA::getNow();
            $doClients->update();

            // Go to next page
            if (OA_Permission::isAccount(OA_ACCOUNT_ADVERTISER)) {
                // Set current session to new language
                $session['language'] = $clientlanguage;
                phpAds_SessionDataStore();
                MAX_Admin_Redirect::redirect('index.php');
            } else {
                MAX_Admin_Redirect::redirect("advertiser-campaigns.php?clientid=$clientid");
            }
        }
        exit;
    }
}

/*-------------------------------------------------------*/
/* HTML framework                                        */
/*-------------------------------------------------------*/

if ($clientid != "") {
    if (OA_Permission::isAccount(OA_ACCOUNT_ADMIN) || OA_Permission::isAccount(OA_ACCOUNT_MANAGER)) {
        OA_Admin_Menu::setAdvertiserPageContext($clientid, 'advertiser-index.php');
        phpAds_PageShortcut($strClientHistory, 'stats.php?entity=advertiser&breakdown=history&clientid='.$clientid, 'images/icon-statistics.gif');
        phpAds_PageHeader("4.1.2");
        echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;<b>".phpAds_getClientName($clientid)."</b><br /><br /><br />";
        phpAds_ShowSections(array("4.1.2", "4.1.3", "4.1.5"));
    } else {
        phpAds_PageHeader("4");
    }

    // Do not get this information if the page
    // is the result of an error message
    if (!isset($client)) {
        $doClients = OA_Dal::factoryDO('clients');
        if ($doClients->get($clientid)) {
            $client = $doClients->toArray();
        }

        // Set password to default value
        if ($client['clientpassword'] != '') {
            $client['clientpassword'] = '********';
        }
    }
} else {
    phpAds_PageHeader("4.1.1");
    echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;<b>".phpAds_getClientName($clientid)."</b><br /><br /><br />";
    phpAds_ShowSections(array("4.1.1"));
    // Do not set this information if the page
    // is the result of an error message
    if (!isset($client)) {
        $client['clientname']            = $strUntitled;
        $client['contact']                = '';
        $client['comments']                = '';
        $client['email']                = '';
        $client['reportdeactivate']     = 't';
        $client['report']                 = 'f';
        $client['reportinterval']         = 7;
    }
}
$tabindex = 1;

/*-------------------------------------------------------*/
/* Main code                                             */
/*-------------------------------------------------------*/

echo "<br /><br />";
echo "<form name='clientform' method='post' action='advertiser-edit.php' onSubmit='return max_formValidate(this);'>";
echo "<input type='hidden' name='clientid' value='".(isset($clientid) && $clientid != '' ? $clientid : '')."'>";


// Header
echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>";
echo "<tr><td height='25' colspan='3'><b>".$strBasicInformation."</b></td></tr>";
echo "<tr height='1'><td width='30'><img src='images/break.gif' height='1' width='30'></td>";
echo "<td width='200'><img src='images/break.gif' height='1' width='200'></td>";
echo "<td width='100%'><img src='images/break.gif' height='1' width='100%'></td></tr>";
echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>";

// Clientname
echo "<tr><td width='30'>&nbsp;</td><td width='200'>".$strName."</td>";

if (OA_Permission::isAccount(OA_ACCOUNT_MANAGER)) {
    echo "<td><input onBlur='max_formValidateElement(this);' class='flat' type='text' name='clientname' size='25' value='".phpAds_htmlQuotes($client['clientname'])."' style='width: 350px;' tabindex='".($tabindex++)."'></td>";
} else {
    echo "<td>".(isset($client['clientname']) ? $client['clientname'] : '')."</td>";
}

echo "</tr><tr><td><img src='images/spacer.gif' height='1' width='100%'></td>";
echo "<td colspan='2'><img src='images/break-l.gif' height='1' width='200' vspace='6'></td></tr>";

// Contact
echo "<tr><td width='30'>&nbsp;</td><td width='200'>".$strContact."</td><td>";
echo "<input onBlur='max_formValidateElement(this);' class='flat' type='text' name='contact' size='25' value='".phpAds_htmlQuotes($client['contact'])."' style='width: 350px;' tabindex='".($tabindex++)."'>";
echo "</td></tr><tr><td><img src='images/spacer.gif' height='1' width='100%'></td>";
echo "<td colspan='2'><img src='images/break-l.gif' height='1' width='200' vspace='6'></td></tr>";

// Email
echo "<tr><td width='30'>&nbsp;</td><td width='200'>".$strEMail."</td><td>";
echo "<input onBlur='max_formValidateElement(this);' class='flat' type='text' name='email' size='25' value='".phpAds_htmlQuotes($client['email'])."' style='width: 350px;' tabindex='".($tabindex++)."'>";
echo "</td></tr><tr><td><img src='images/spacer.gif' height='1' width='100%'></td>";
echo "<td colspan='2'><img src='images/break-l.gif' height='1' width='200' vspace='6'></td></tr>";

// Language
echo "<tr><td width='30'>&nbsp;</td><td width='200'>".$strLanguage."</td><td>";
echo "<select name='clientlanguage' tabindex='".($tabindex++)."'>";
echo "<option value='' SELECTED>".$strDefault."</option>";

$languages = MAX_Admin_Languages::AvailableLanguages();
foreach ($languages as $k => $v) {
    if (isset($client['language']) && $client['language'] == $k) {
        echo "<option value='$k' selected>$v</option>";
    } else {
        echo "<option value='$k'>$v</option>";
    }
}

echo "</select></td></tr><tr><td height='10' colspan='3'>&nbsp;</td></tr>";

// Footer
echo "</table>";

// Header
echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>";
echo "<tr><td height='25' colspan='3'><b>".$strMailSubject."</b></td></tr>";
echo "<tr height='1'><td width='30'><img src='images/break.gif' height='1' width='30'></td>";
echo "<td width='200'><img src='images/break.gif' height='1' width='200'></td>";
echo "<td width='100%'><img src='images/break.gif' height='1' width='100%'></td></tr>";
echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>";

// Reports
echo "<input type='hidden' name='clientreportlastdate' value='".(isset($client['reportlastdate']) ? $client['reportlastdate'] : '')."'>";
echo "<input type='hidden' name='clientreportprevious' value='".(isset($client['report']) ? $client['report'] : '')."'>";

echo "<tr><td width='30'>&nbsp;</td><td colspan='2'>";
echo "<input type='checkbox' name='clientreportdeactivate' value='t'".($client['reportdeactivate'] == 't' ? ' CHECKED' : '')." tabindex='".($tabindex++)."'>&nbsp;";
echo $strSendDeactivationWarning;
echo "</td></tr>";

// Interval
echo "<tr><td width='30'>&nbsp;</td><td colspan='2'>";
echo "<input type='checkbox' name='clientreport' value='t'".($client['report'] == 't' ? ' CHECKED' : '')." tabindex='".($tabindex++)."'>&nbsp;";
echo $strSendAdvertisingReport;
echo "</td></tr>";

echo "<tr><td><img src='images/spacer.gif' height='1' width='100%'></td>";
echo "<td colspan='2'><img src='images/break-l.gif' height='1' width='200' vspace='6'></td></tr>";
echo "<tr><td width='30'>&nbsp;</td><td width='200'>".$strNoDaysBetweenReports."</td><td>";
echo "<input onBlur='max_formValidateElement(this);' class='flat' type='text' name='clientreportinterval' size='25' value='".$client['reportinterval']."' tabindex='".($tabindex++)."'>";
echo "</td></tr><tr><td height='10' colspan='3'>&nbsp;</td></tr>";

// Footer
echo "</table>";

// Header
echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>";

// Error message?
if (isset($errormessage) && count($errormessage)) {
    echo "<tr><td>&nbsp;</td><td height='10' colspan='2'>";
    echo "<table cellpadding='0' cellspacing='0' border='0'><tr><td>";
    echo "<img src='images/error.gif' align='absmiddle'>&nbsp;";
    foreach ($errormessage as $k => $v)
        echo "<font color='#AA0000'><b>".$v."</b></font><br />";

    echo "</td></tr></table></td></tr><tr><td height='10' colspan='3'>&nbsp;</td></tr>";
    echo "<tr><td><img src='images/spacer.gif' height='1' width='100%'></td>";
    echo "<td colspan='2'><img src='images/break-l.gif' height='1' width='200' vspace='6'></td></tr>";
}

echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>"."\n";
echo "<tr><td height='25' colspan='3'><b>".$strMiscellaneous."</b></td></tr>"."\n";
echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>"."\n";
echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>"."\n";

echo "<tr>"."\n";

echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>";
echo "<tr><td width='30'>&nbsp;</td>";
echo "<td width='200'>".$strComments."</td>";

echo "<td><textarea class='code' cols='45' rows='6' name='comments' wrap='off' dir='ltr' style='width:350px;";
echo "' tabindex='".($tabindex++)."'>".htmlspecialchars($client['comments'])."</textarea></td></tr>";
echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>";

echo "<tr><td height='10' colspan='2'>&nbsp;</td></tr>";
echo "</table>";

echo "<br /><br />";
echo "<input type='submit' name='submit' value='".(isset($clientid) && $clientid != '' ? $strSaveChanges : $strNext.' >')."' tabindex='".($tabindex++)."'>";
echo "</form>";

/*-------------------------------------------------------*/
/* Form requirements                                     */
/*-------------------------------------------------------*/

// Get unique clientname
$doClients = OA_Dal::factoryDO('clients');
$unique_names = $doClients->getUniqueValuesFromColumn('clientname', $client['clientname']);

?>

<script language='JavaScript'>
<!--
    max_formSetRequirements('contact', '<?php echo addslashes($strContact); ?>', true);
    max_formSetRequirements('email', '<?php echo addslashes($strEMail); ?>', true, 'email');
    max_formSetRequirements('clientreportinterval', '<?php echo addslashes($strNoDaysBetweenReports); ?>', true, 'number+');
<?php if (OA_Permission::isAccount(OA_ACCOUNT_ADMIN)) { ?>
    max_formSetRequirements('clientname', '<?php echo addslashes($strName); ?>', true, 'unique');

    max_formSetUnique('clientname', '|<?php echo addslashes(implode('|', $unique_names)); ?>|');
<?php } ?>
//-->
</script>

<?php

/*-------------------------------------------------------*/
/* HTML framework                                        */
/*-------------------------------------------------------*/

phpAds_PageFooter();

?>
