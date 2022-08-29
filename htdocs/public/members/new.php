<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2001-2002  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2006-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2021       Waël Almoman            <info@almoman.com>
 * Copyright (C) 2022       Yoan Mollard	        <opensource@aubrune.eu>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/public/members/new.php
 *	\ingroup    member
 *	\brief      Example of form to add a new member
 *
 *  Note that you can add following constant to change behaviour of page
 *  MEMBER_NEWFORM_AMOUNT               Default amount for auto-subscribe form
 *  MEMBER_NEWFORM_EDITAMOUNT           0 or 1 = Amount can be edited
 *  MEMBER_MIN_AMOUNT                   Minimum amount
 *  MEMBER_NEWFORM_PAYONLINE            Suggest payment with paypal, paybox or stripe
 *  MEMBER_NEWFORM_DOLIBARRTURNOVER     Show field turnover (specific for dolibarr foundation)
 *  MEMBER_URL_REDIRECT_SUBSCRIPTION    Url to redirect once subscribe submitted
 *  MEMBER_NEWFORM_FORCETYPE            Force type of member
 *  MEMBER_NEWFORM_FORCEMORPHY          Force nature of member (mor/phy)
 *  MEMBER_NEWFORM_FORCECOUNTRYCODE     Force country
 */

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';

// Init vars
$errmsg = '';
$num = 0;
$error = 0;
$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'aZ09');

// Load translation files
$langs->loadLangs(array("main", "members", "companies", "install", "other"));

// Security check
if (empty($conf->adherent->enabled)) {
	accessforbidden('', 0, 0, 1);
}

if (empty($conf->global->MEMBER_ENABLE_PUBLIC)) {
	print $langs->trans("Auto subscription form for public visitors has not been enabled");
	exit;
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('publicnewmembercard', 'globalcard'));

$extrafields = new ExtraFields($db);

$user->loadDefaultValues();

/**
 * Show header for new member
 *
 * @param 	string		$title				Title
 * @param 	string		$head				Head array
 * @param 	int    		$disablejs			More content into html header
 * @param 	int    		$disablehead		More content into html header
 * @param 	array  		$arrayofjs			Array of complementary js files
 * @param 	array  		$arrayofcss			Array of complementary css files
 * @return	void
 */
function llxHeaderVierge($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '')
{
	global $user, $conf, $langs, $mysoc;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss); // Show html headers

	print '<body id="mainbody" class="publicnewmemberform">';

	// Define urllogo
	$urllogo = DOL_URL_ROOT.'/theme/common/login_logo.png';

	if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small)) {
		$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_small);
	} elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo)) {
		$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/'.$mysoc->logo);
	} elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.svg')) {
		$urllogo = DOL_URL_ROOT.'/theme/dolibarr_logo.svg';
	}

	print '<div class="center">';

	// Output html code for logo
	if ($urllogo) {
		print '<div class="backgreypublicpayment">';
		print '<div class="logopublicpayment">';
		print '<img id="dolpaymentlogo" src="'.$urllogo.'">';
		print '</div>';
		if (empty($conf->global->MAIN_HIDE_POWERED_BY)) {
			print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">'.$langs->trans("PoweredBy").'<br><img class="poweredbyimg" src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
		}
		print '</div>';
	}

	if (!empty($conf->global->MEMBER_IMAGE_PUBLIC_REGISTRATION)) {
		print '<div class="backimagepublicregistration">';
		print '<img id="idEVENTORGANIZATION_IMAGE_PUBLIC_INTERFACE" src="'.$conf->global->MEMBER_IMAGE_PUBLIC_REGISTRATION.'">';
		print '</div>';
	}

	print '</div>';

	print '<div class="divmainbodylarge">';
}

/**
 * Show footer for new member
 *
 * @return	void
 */
function llxFooterVierge()
{
	print '</div>';

	printCommonFooter('public');

	print "</body>\n";
	print "</html>\n";
}

/**
 * State machine for $action
 */


if ($action == '') {
	$filling_started = !empty(GETPOST('email')) || !empty(GETPOST('firstname')) || !empty(GETPOST('lastname'));
	$show_table = !$filling_started && empty($conf->global->MEMBER_SKIP_TABLE) && empty($conf->global->MEMBER_NEWFORM_FORCETYPE);
	$action = $show_table? 'table' : 'create';
}

$ref = GETPOST('ref');
$urltoken = GETPOST('urltoken');
$adh = new Adherent($db);
$adht = new AdherentType($db);

if(!empty($ref) || $action == 'renew' || $action == 'update' || $action == 'update_confirm') {
	if(!empty('urltoken')) {
		$res = $adh->fetch($db->escape($ref));
		if($res > 0 && GETPOST('urltoken') == $adh->urltoken && $adh->urltokenexpiringdate && $adh->urltokenexpiringdate > dol_now()) {
			if ($action == 'create' || $action == 'table') {
				$action = 'renew'; // This is a renewal link so load the renew action
			}
		} else {
			$url = DOL_MAIN_URL_ROOT."/public/members/new.php?action=identify&notype=1";
			$errmsg = $langs->trans("InvalidRenewalLink", $url, $conf->global->MAIN_INFO_SOCIETE_MAIL);
			$adh = new Adherent($db); // Important: Reset values previously fetched
			$error++;
		}
	}
}

if($action == 'renew_add' && !empty(GETPOST("submitupdate"))) {
	$action = 'update_confirm';
}

// End of state machine, print $action for debug
print("ACTION=".$action);

$form_morphy = GETPOST('morphy')? GETPOST('morphy') : $adh->morphy;
$form_email = GETPOST('email')? GETPOST('email') : $adh->email;
$form_typeid = GETPOST('typeid')? GETPOST('typeid') : $adh->typeid;
$form_societe = GETPOST('societe')? GETPOST('societe') : $adh->company;
$form_address = GETPOST('address')? GETPOST('address') : $adh->address;
$form_zipcode = GETPOST('zipcode')? GETPOST('zipcode') : $adh->zip;
$form_town = GETPOST('town')? GETPOST('town') : $adh->town;
$form_country_id = GETPOST('country_id')? GETPOST('country_id', 'int') : $adh->country_id;
$form_civility_id = GETPOST('civility_id')? GETPOST('civility_id') : $adh->civility_id;
$form_gender = GETPOST('gender')? GETPOST('gender') : $adh->gender;
$form_lastname = GETPOST('lastname')? GETPOST('lastname') : $adh->lastname;
$form_firstname = GETPOST('firstname')? GETPOST('firstname') : $adh->firstname;
$form_login = GETPOST('login')? GETPOST('login') : $adh->login;
$form_password = GETPOST('password')? GETPOST('password') : $adh->password;
$form_photo = GETPOST('photo')? GETPOST('photo') : $adh->photo;
$form_public = GETPOST('public')? GETPOSTISSET('public'): !empty($adh->public);
$form_amount = GETPOST('amount')? GETPOST('amount') : $adh->amount;
$form_comment = GETPOST('note_private')? GETPOST('note_private', 'restricthtml') : $adh->note_private;
$birthday = GETPOST('birthmonth')? dol_mktime(GETPOST("birthhour", 'int'), GETPOST("birthmin", 'int'), GETPOST("birthsec", 'int'),
            GETPOST("birthmonth", 'int'), GETPOST("birthday", 'int'), GETPOST("birthyear", 'int')) : $adh->birth;

/*
 * Actions
 */

$parameters = array();
// Note that $action and $object may have been modified by some hooks
$object = new Adherent($db);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// Action called when page is submitted: check all inputs for add, renew_add, or update
if (empty($reshook) && ($action == 'add' || $action == 'renew_add' || $action == 'update_confirm')) {
	$error = 0;
	$urlback = '';

	$db->begin();

	if (!empty($conf->global->ADHERENT_MAIL_REQUIRED) && empty(GETPOST('email'))) {
		$error++;
		$errmsg .= $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Email'))."<br>\n";
	} elseif (GETPOST("email") && !isValidEmail(GETPOST("email"))) {
		$langs->load('errors');
		$error++;
		$errmsg .= $langs->trans("ErrorBadEMail", GETPOST("email"))."<br>\n";
	}

	// test if login already exists
	if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) {
		if (!GETPOST('login')) {
			$error++;
			$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Login"))."<br>\n";
		}
		$sql = "SELECT login FROM ".MAIN_DB_PREFIX."adherent WHERE login='".$db->escape(GETPOST('login'))."'";
		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
		}
		if ($num != 0) {
			$error++;
			$langs->load("errors");
			$errmsg .= $langs->trans("ErrorLoginAlreadyExists")."<br>\n";
		}
		if (!GETPOSTISSET("pass1") || !GETPOSTISSET("pass2") || GETPOST("pass1", 'none') == '' || GETPOST("pass2", 'none') == '' || GETPOST("pass1", 'none') != GETPOST("pass2", 'none')) {
			$error++;
			$langs->load("errors");
			$errmsg .= $langs->trans("ErrorPasswordsMustMatch")."<br>\n";
		}
		if (!GETPOST('email')) {
			$error++;
			$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("EMail"))."<br>\n";
		}
	}
	if (GETPOST('typeid') <= 0) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type"))."<br>\n";
	}
	if($action == 'update_confirm' && $adh->typeid != $form_typeid) {
		$error++;
		$errmsg .= $langs->trans("CannotEditMemberInfoLong")."<br>\n";
	}
	if (!in_array(GETPOST('morphy'), array('mor', 'phy'))) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Nature'))."<br>\n";
	}
	if (!GETPOST('lastname')) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Lastname"))."<br>\n";
	}
	if (!GETPOST('firstname')) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Firstname"))."<br>\n";
	}
	if (GETPOST("birthmonth") && empty($birthday)) {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorBadDateFormat")."<br>\n";
	}
	if (!empty($conf->global->MEMBER_NEWFORM_DOLIBARRTURNOVER)) {
		if (GETPOST("morphy") == 'mor' && GETPOST('budget') <= 0) {
			$error++;
			$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TurnoverOrBudget"))."<br>\n";
		}
	}

	$public = GETPOSTISSET('public') ? 1 : 0;

	if (!$error) {
		$previousid = $adh->id; 
		$adh = new Adherent($db);

		$adh->statut      = -1;
		$adh->public      = $public;
		$adh->firstname   = GETPOST('firstname');
		$adh->lastname    = GETPOST('lastname');
		$adh->gender      = GETPOST('gender');
		$adh->civility_id = GETPOST('civility_id');
		$adh->societe     = GETPOST('societe');
		$adh->address     = GETPOST('address');
		$adh->zip         = GETPOST('zipcode');
		$adh->town        = GETPOST('town');
		$adh->email       = GETPOST('email');
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) {
			$adh->login       = GETPOST('login');
			$adh->pass        = GETPOST('pass1');
		}
		$adh->photo       = GETPOST('photo');
		$adh->country_id  = $conf->global->MEMBER_NEWFORM_FORCECOUNTRYCODE ? $conf->global->MEMBER_NEWFORM_FORCECOUNTRYCODE : GETPOST('country_id', 'int');
		$adh->state_id    = GETPOST('state_id', 'int');
		$adh->typeid      = $conf->global->MEMBER_NEWFORM_FORCETYPE ? $conf->global->MEMBER_NEWFORM_FORCETYPE : GETPOST('typeid', 'int');
		$adh->note_private = GETPOST('note_private');
		$adh->morphy      = $conf->global->MEMBER_NEWFORM_FORCEMORPHY ? $conf->global->MEMBER_NEWFORM_FORCEMORPHY : GETPOST('morphy');
		$adh->birth       = $birthday;

		// Fill array 'array_options' with data from add form
		$extrafields->fetch_name_optionals_label($adh->table_element);
		$ret = $extrafields->setOptionalsFromPost(null, $adh);
		if ($ret < 0) {
			$error++;
		}

		$result = -1;
		if ($action == 'add') {
			// Start membership now
			$adht = new AdherentType($db);
			$adht->fetch($adh->typeid);
			$now = dol_now();
			$adh->datefin = $adh->get_end_date($now, $adht);
			$result = $adh->create($user);
		}
		elseif ($action == 'update_confirm' || $action == 'renew_add') {
			// Update member information previously in database with updated info
			$adh->id = $previousid;
			$adh->ref = strval($previousid);
			$result = $adh->update($user);
		}

		if($result == 0) {
			$error++;
			$errmsg .= $langs->trans("NothingToDo"); // Update has affected 0 row
		}

		elseif ($result > 0) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
			$object = $adh;

			if ($object->email) {
				$subject = '';
				$msg = '';

				// Send subscription email
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
				$formmail = new FormMail($db);
				// Set output language
				$outputlangs = new Translate('', $conf);
				$outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
				// Load traductions files required by page
				$outputlangs->loadLangs(array("main", "members"));
				// Get email content from template
				$arraydefaultmessage = null;
				$labeltouse = $conf->global->ADHERENT_EMAIL_TEMPLATE_AUTOREGISTER;

				if (!empty($labeltouse)) {
					$arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
				}

				if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
					$subject = $arraydefaultmessage->topic;
					$msg     = $arraydefaultmessage->content;
				}

				$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
				complete_substitutions_array($substitutionarray, $outputlangs, $object);
				$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
				$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnValid()), $substitutionarray, $outputlangs);

				if ($subjecttosend && $texttosend) {
					$moreinheader = 'X-Dolibarr-Info: send_an_email by public/members/new.php'."\r\n";

					$result = $object->send_an_email($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
				}
				/*if ($result < 0) {
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
				}*/
			}

			// Send email to the foundation to say a new member subscribed with autosubscribe form
			if ($action != 'update_confirm' && !empty($conf->global->MAIN_INFO_SOCIETE_MAIL) && !empty($conf->global->ADHERENT_AUTOREGISTER_NOTIF_MAIL_SUBJECT) &&
				!empty($conf->global->ADHERENT_AUTOREGISTER_NOTIF_MAIL)) {
				// Define link to login card
				$appli = constant('DOL_APPLICATION_TITLE');
				if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
					$appli = $conf->global->MAIN_APPLICATION_TITLE;
					if (preg_match('/\d\.\d/', $appli)) {
						if (!preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) {
							$appli .= " (".DOL_VERSION.")"; // If new title contains a version that is different than core
						}
					} else {
						$appli .= " ".DOL_VERSION;
					}
				} else {
					$appli .= " ".DOL_VERSION;
				}

				$to = $adh->makeSubstitution($conf->global->MAIN_INFO_SOCIETE_MAIL);
				$from = $conf->global->ADHERENT_MAIL_FROM;
				$mailfile = new CMailFile(
					'['.$appli.'] '.$conf->global->ADHERENT_AUTOREGISTER_NOTIF_MAIL_SUBJECT,
					$to,
					$from,
					$adh->makeSubstitution($conf->global->ADHERENT_AUTOREGISTER_NOTIF_MAIL),
					array(),
					array(),
					array(),
					"",
					"",
					0,
					-1
				);

				if (!$mailfile->sendfile()) {
					dol_syslog($langs->trans("ErrorFailedToSendMail", $from, $to), LOG_ERR);
				}
			}

			if (!empty($backtopage)) {
				$urlback = $backtopage;
			} elseif (!empty($conf->global->MEMBER_URL_REDIRECT_SUBSCRIPTION)) {
				$urlback = $conf->global->MEMBER_URL_REDIRECT_SUBSCRIPTION;
				// TODO Make replacement of __AMOUNT__, etc...
			} else {
				if ($action == 'add') 				 $next_action = 'added';
				elseif ($action == 'renew_add') 	 $next_action = 'renew_added';
				elseif ($action == 'update_confirm') $next_action = 'updated';
				else 								 $next_action = 'identify';
				$urlback = $_SERVER["PHP_SELF"]."?action=".$next_action."&token=".newToken();
			}

			$amount = GETPOST('amount')? GETPOST('amount'):-1;
			if ($action != 'update_confirm' && !empty($conf->global->MEMBER_NEWFORM_PAYONLINE) && $conf->global->MEMBER_NEWFORM_PAYONLINE != '-1' && $amount>0) {
				if (empty($conf->global->MEMBER_NEWFORM_EDITAMOUNT)) {			// If edition of amount not allowed
					// TODO Check amount is same than the amount required for the type of member or if not defined as the defeault amount into $conf->global->MEMBER_NEWFORM_AMOUNT
					// It is not so important because a test is done on return of payment validation.
				}

				$urlback = getOnlinePaymentUrl(0, 'member', $adh->ref, price2num(GETPOST('amount', 'alpha'), 'MT'), '', 0);

				if (GETPOST('email')) {
					$urlback .= '&email='.urlencode(GETPOST('email'));
				}
				if ($conf->global->MEMBER_NEWFORM_PAYONLINE != '-1' && $conf->global->MEMBER_NEWFORM_PAYONLINE != 'all') {
					$urlback .= '&paymentmethod='.urlencode($conf->global->MEMBER_NEWFORM_PAYONLINE);
				}
			} else {
				if (!empty($entity)) {
					$urlback .= '&entity='.((int) $entity);
				}
			}

			dol_syslog("member ".$adh->ref." was created, we redirect to ".$urlback);
		} else {
			$error++;
			$errmsg .= join('<br>', $adh->errors);
		}
	}

	if (!$error) {
		$db->commit();

		Header("Location: ".$urlback);
		exit;
	} else {
		$errmsg = empty($errmsg)? $langs->trans('ErrorSavingChanges') : $errmsg;
		$db->rollback();
		if($action == 'add') 				$action = 'create';
		elseif($action == 'renew_add') 		$action = 'renew';
		elseif($action == 'update_confirm') $action = 'update';
		print("now action=".$action);
	}

}
elseif ($action == 'identified') {
	if (!empty($conf->global->ADHERENT_MAIL_REQUIRED) && empty(GETPOST('email'))) {
		$error++;
		$errmsg .= $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Email'))."<br>\n";
	} elseif (GETPOST("email") && !isValidEmail(GETPOST("email"))) {
		$langs->load('errors');
		$error++;
		$errmsg .= $langs->trans("ErrorBadEMail", GETPOST("email"))."<br>\n";
	}
	else {
		$rowid = 0; // Will store the existing adherent id if found, or 0
		$sql = "SELECT rowid, datec FROM ".MAIN_DB_PREFIX."adherent WHERE email='".$db->escape(GETPOST('email'))."' ORDER BY datec DESC";
		$resql = $db->query($sql);
		if ($resql) {
			$num_found = $db->num_rows($resql);
			if($num_found>0) {
				$row = $db->fetch_row($resql);
				$rowid = $row[0];
				$res = $adh->fetch($rowid);
				if($res <= 0) $error++;
			}
		}
	
		$langs->load("mails");
	
		$subject = '';
		$msg = '';
	
		// Send the search result to the email
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		// Set output language
		$outputlangs = new Translate('', $conf);
		$outputlangs->setDefaultLang($mysoc->default_lang);
		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "members"));
		// Get email content from template
		$arraydefaultmessage = null;
		$labeltouse = ($rowid>0)? $conf->global->ADHERENT_EMAIL_TEMPLATE_SEARCHED_AND_FOUND: $conf->global->ADHERENT_EMAIL_TEMPLATE_SEARCHED_AND_NOT_FOUND;
	
		if (!empty($labeltouse)) {
			$arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', null, $outputlangs, 0, 1, $labeltouse);
		}

		if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
			$subject = $arraydefaultmessage->topic;
			$msg     = $arraydefaultmessage->content;
		}
	
		$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $adh);
		complete_substitutions_array($substitutionarray, $outputlangs);
		$url = $adh->getRenewalLink(null, true);
		$substitutionarray['(MembershipRenewalURL)'] = $url;
		$substitutionarray['(NewMembershipURL)'] = DOL_MAIN_URL_ROOT."/public/members/new.php?action=create&email=".urlencode($form_email)."&typeid=".urlencode($form_typeid);
		// FIXME: a wild user could start with this previous URL and skip e-mail validation: need a database token insertion

		$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
		$texttosend = make_substitutions($msg, $substitutionarray, $outputlangs);
		$outputlangs->load("members");
	
		$moreinheader = 'X-Dolibarr-Info: send_an_email by public/members/new.php'."\r\n";
	
		$adh->email = GETPOST('email');   // FIXME: we are trusting this e-mail coming from POST. Add captcha to prevent spam?
		$adh->id = 0;
		$result = $adh->send_an_email($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
		if ($result < 0) {
			$errmsg = $langs->trans("ErrorFailedToSendEmail");
			$error++;
		}
	}
}


// Action called after a submitted was send and member created successfully
// If MEMBER_URL_REDIRECT_SUBSCRIPTION is set to url we never go here because a redirect was done to this url.
// backtopage parameter with an url was set on member submit page, we never go here because a redirect was done to this url.
if (empty($reshook) && ($action == 'added' || $action == 'updated' || $action == 'renew_added' || $action == 'search_confirm' || $action == 'identified')) {
	if($action == 'added')				$endmsg = $langs->trans("NewMemberbyWeb");
	elseif($action == 'updated')		$endmsg = $langs->trans("UpdatedMemberbyWeb");
	elseif($action == 'renew_added')	$endmsg = $langs->trans("RenewedMemberbyWeb");
	elseif($action == 'identified')     $endmsg = $langs->trans("SearchResultSentByMail", GETPOST('email'), $_SERVER["PHP_SELF"]);

	llxHeaderVierge($langs->trans("NewMemberForm"));

	// If we haven't been redirected so far
	print '<br><br>';
	print '<div class="center">';
	print $endmsg;
	print '</div>';
	dol_htmloutput_errors($errmsg);

	llxFooterVierge();
	exit;
}



/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);
$extrafields->fetch_name_optionals_label($object->table_element); // fetch optionals attributes and labels

llxHeaderVierge($langs->trans(empty($ref)? "NewSubscriptionTitle" : "NewSubscriptionRenewalTitle"));
print load_fiche_titre($langs->trans(empty($ref)? "NewSubscriptionTitle" : "NewSubscriptionRenewalTitle"), '', '', 0, 0, 'center');

print '<div align="center">';
print '<div id="divsubscribe">';

// Print introduction message
print '<div class="center subscriptionformhelptext justify">';
if (!empty($conf->global->MEMBER_NEWFORM_TEXT) && empty($ref)) {
	print $langs->trans($conf->global->MEMBER_NEWFORM_TEXT)."<br>\n";
}
elseif (!empty($conf->global->MEMBER_NEWFORM_RENEW_TEXT) && !empty($ref)) {
	print $langs->trans($conf->global->MEMBER_NEWFORM_RENEW_TEXT)."<br>\n";
}
else {
	if(empty($ref)) {
		$urlsearch = DOL_MAIN_URL_ROOT."/public/members/new.php?action=identify&notype=1";
		print $langs->trans("NewSubscriptionDesc", $conf->global->MAIN_INFO_SOCIETE_NOM, $urlsearch, $conf->global->MAIN_INFO_SOCIETE_MAIL)."<br>\n";
	} else {
		print $langs->trans("NewSubscriptionRenewDesc", $conf->global->MAIN_INFO_SOCIETE_NOM, $conf->global->MAIN_INFO_SOCIETE_MAIL)."<br>\n";
	}
}
print '</div>';


dol_htmloutput_errors($errmsg);

// Print form
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="newmember">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'" / >';
print '<input type="hidden" name="entity" value="'.$entity.'" />';

if ($action == 'renew' || $action == 'update') {
	print '<input type="hidden" name="ref" value="'.$ref.'" />';
	print '<input type="hidden" name="urltoken" value="'.$urltoken.'" />';
}

if ($action == 'create' || $action == 'renew' || $action == 'update' || $action == 'identify') {
	if ($action == 'renew') 	   						$next_action = 'renew_add';
	elseif ($action == 'update')   						$next_action = 'update_confirm';
	elseif ($action == 'create' && !empty(GETPOST('email'))) $next_action = 'add';
	elseif ($action == 'identify') 						$next_action = 'identified';
	else 						   						$next_action = 'identify';

	print '<input type="hidden" name="action" value="'.$next_action.'" />';
	print '<br>';
	print '<br><span class="opacitymedium">'.$langs->trans("FieldsWithAreMandatory", '*').'</span><br>';
	//print $langs->trans("FieldsWithIsForPublic",'**').'<br>';

	print dol_get_fiche_head('');

	print '<script type="text/javascript">
	jQuery(document).ready(function () {
		jQuery(document).ready(function () {
			function initmorphy()
			{
				console.log("Call initmorphy");
				if (jQuery("#morphy").val() == \'phy\') {
					jQuery("#trcompany").hide();
				}
				if (jQuery("#morphy").val() == \'mor\') {
					jQuery("#trcompany").show();
				}
			};
			initmorphy();
			jQuery("#morphy").change(function() {
				initmorphy();
			});
			jQuery("#selectcountry_id").change(function() {
			document.newmember.action.value="'.$action.'";
			document.newmember.submit();
			});
			jQuery("#typeid").change(function() {
			document.newmember.action.value="'.$action.'";
			document.newmember.submit();
			});
		});
	});
	</script>';

	print '<table class="border" summary="form to subscribe" id="tablesubscribe">'."\n";
	// EMail
	print '<tr><td>'.$langs->trans("Email").($conf->global->ADHERENT_MAIL_REQUIRED ? ' <span style="color:red;">*</span>' : '').'</td><td>';
	//print img_picto('', 'email', 'class="pictofixedwidth"');
	$emailcanbeedited = !empty($conf->global->MEMBER_SKIP_TABLE) || ($action == 'identify' || $action == 'renew');
	$emailtagdisabled = $emailcanbeedited? '' : ' disabled';
	print '<input type="text" name="email" maxlength="255" class="minwidth200" value="'.dol_escape_htmltag($form_email).'" '.$emailtagdisabled.'>'."\n";

	// Type
	if (empty($conf->global->MEMBER_NEWFORM_FORCETYPE) && empty(GETPOST('notype'))) {
		$opentypes = $adht->liste_array(1);
		$listoftype = $adht->liste_array();
		$tmp = array_keys($listoftype);
		$defaulttype = '';
		$isempty = 1;
		if (count($listoftype) == 1) {
			$defaulttype = $tmp[0];
			$isempty = 0;
		}
		$type_available = array_key_exists($form_typeid, $opentypes);
		print '<tr><td class="titlefield">'.$langs->trans("Type").' <span style="color: red">*</span></td><td>';
		print $form->selectarray("typeid", $opentypes, ($form_typeid && $type_available)? $form_typeid : $defaulttype, $isempty, 0, 0, '', 0, 0, $emailcanbeedited?0:1);

		if(!$type_available) {
			$form_typeid = null;
			print " ".img_warning($langs->trans("MembershipTypeNoLongerAvailable"));
			print " ".$langs->trans("MembershipTypeNoLongerAvailableShort");
		}
		print '</td></tr>'."\n";
	} elseif (empty(GETPOST('notype'))) {
		$adht->fetch($conf->global->MEMBER_NEWFORM_FORCETYPE);
		print '<input type="hidden" id="typeid" name="typeid" value="'.$conf->global->MEMBER_NEWFORM_FORCETYPE.'">';
	}

	if(!$emailcanbeedited) {   // Pass values to POST if e-mail and typeid are disabled in page action=create
		print '<input type="hidden" id="typeid" name="typeid" value="'.$form_typeid.'">';
		print '<input type="hidden" id="email" name="email" value="'.$form_email.'">';
	}

	if($action != 'identify') { // Identification only shows e-mail and type in the form
		// Moral/Physic attribute
		$morphys["phy"] = $langs->trans("Physical");
		$morphys["mor"] = $langs->trans("Moral");
		print '<tr class="morphy"><td class="titlefield">'.$langs->trans('MemberNature').' <span style="color: red">*</span></td><td>'."\n";

		if (empty($conf->global->MEMBER_NEWFORM_FORCEMORPHY)) {
			print $form->selectarray("morphy", $morphys, $form_morphy);
			print '</td></tr>'."\n";
		} else {
			print $morphys[$conf->global->MEMBER_NEWFORM_FORCEMORPHY];
			print '<input type="hidden" id="morphy" name="morphy" value="'.$conf->global->MEMBER_NEWFORM_FORCEMORPHY.'">';
		}

		// Company
		print '<tr id="trcompany" class="trcompany"><td>'.$langs->trans("Company").'</td><td>';
		print img_picto('', 'company', 'class="pictofixedwidth"');
		print '<input type="text" name="societe" class="minwidth150 widthcentpercentminusx" value="'.dol_escape_htmltag(GETPOST('societe')).'"></td></tr>'."\n";
		// Title
		print '<tr><td class="titlefield">'.$langs->trans('UserTitle').'</td><td>';
		print $formcompany->select_civility($form_civility_id, 'civility_id').'</td></tr>'."\n";
		// Lastname
		print '<tr><td>'.$langs->trans("Lastname").' <span style="color: red">*</span></td><td><input type="text" name="lastname" class="minwidth150" value="'.dol_escape_htmltag($form_lastname).'"></td></tr>'."\n";
		// Firstname
		print '<tr><td>'.$langs->trans("Firstname").' <span style="color: red">*</span></td><td><input type="text" name="firstname" class="minwidth150" value="'.dol_escape_htmltag($form_firstname).'"></td></tr>'."\n";
		// Login
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) {
			print '<tr><td>'.$langs->trans("Login").' <span style="color: red">*</span></td><td><input type="text" name="login" maxlength="50" class="minwidth100"value="'.dol_escape_htmltag($form_login).'"></td></tr>'."\n";
			print '<tr><td>'.$langs->trans("Password").' <span style="color: red">*</span></td><td><input type="password" maxlength="128" name="pass1" class="minwidth100" value="'.dol_escape_htmltag(GETPOST("pass1", "none", 2)).'"></td></tr>'."\n";
			print '<tr><td>'.$langs->trans("PasswordAgain").' <span style="color: red">*</span></td><td><input type="password" maxlength="128" name="pass2" class="minwidth100" value="'.dol_escape_htmltag(GETPOST("pass2", "none", 2)).'"></td></tr>'."\n";
		}
		// Gender
		print '<tr><td>'.$langs->trans("Gender").'</td>';
		print '<td>';
		$arraygender = array('man'=>$langs->trans("Genderman"), 'woman'=>$langs->trans("Genderwoman"));
		print $form->selectarray('gender', $arraygender, $form_gender? $form_gender : $object->gender, 1);
		print '</td></tr>';
		// Address
		print '<tr><td>'.$langs->trans("Address").'</td><td>'."\n";
		print '<textarea name="address" id="address" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.dol_escape_htmltag($form_address, 0, 1).'</textarea></td></tr>'."\n";
		// Zip / Town
		print '<tr><td>'.$langs->trans('Zip').' / '.$langs->trans('Town').'</td><td>';
		print $formcompany->select_ziptown($form_zipcode, 'zipcode', array('town', 'selectcountry_id', 'state_id'), 0, 1, '', 'width75');
		print ' / ';
		print $formcompany->select_ziptown($form_town, 'town', array('zipcode', 'selectcountry_id', 'state_id'), 0, 1);
		print '</td></tr>';
		// Country
		print '<tr><td>'.$langs->trans('Country').'</td><td>';
		print img_picto('', 'country', 'class="pictofixedwidth"');
		$country_id = $form_country_id;
		if (!$country_id && !empty($conf->global->MEMBER_NEWFORM_FORCECOUNTRYCODE)) {
			$country_id = getCountry($conf->global->MEMBER_NEWFORM_FORCECOUNTRYCODE, 2, $db, $langs);
		}
		if (!$country_id && !empty($conf->geoipmaxmind->enabled)) {
			$country_code = dol_user_country();
			//print $country_code;
			if ($country_code) {
				$new_country_id = getCountry($country_code, 3, $db, $langs);
				//print 'xxx'.$country_code.' - '.$new_country_id;
				if ($new_country_id) {
					$country_id = $new_country_id;
				}
			}
		}
		$country_code = getCountry($country_id, 2, $db, $langs);
		print $form->select_country($country_id, 'country_id');
		print '</td></tr>';
		// State
		if (!empty($conf->global->SOCIETE_DISABLE_STATE)) {
			print '<tr><td>'.$langs->trans('State').'</td><td>';
			if ($country_code) {
				print $formcompany->select_state(GETPOST("state_id"), $country_code);
			}
			print '</td></tr>';
		}
		// Birthday
		print '<tr id="trbirth" class="trbirth"><td>'.$langs->trans("DateOfBirth").'</td><td>';
		print $form->selectDate($birthday, 'birth', 0, 0, 1, "newmember", 1, 0);
		print '</td></tr>'."\n";
		// Photo
		print '<tr><td>'.$langs->trans("URLPhoto").'</td><td><input type="text" name="photo" class="minwidth150" value="'.dol_escape_htmltag($form_photo).'"></td></tr>'."\n";
		// Public
		$ispublic_checked = $form_public? "checked='1'" : "";
		print '<tr><td>'.$langs->trans("Public").'</td><td><input type="checkbox" name="public" '.$ispublic_checked.'></td></tr>'."\n";
		// Other attributes
		$tpl_context = 'public'; // define template context to public
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';
		// Comments
		print '<tr>';
		print '<td class="tdtop">'.$langs->trans("Comments").'</td>';
		print '<td class="tdtop"><textarea name="note_private" id="note_private" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.dol_escape_htmltag($form_comment, 0, 1).'</textarea></td>';
		print '</tr>'."\n";

		// Add specific fields used by Dolibarr foundation for example
		// TODO Move this into generic feature.
		if (!empty($conf->global->MEMBER_NEWFORM_DOLIBARRTURNOVER)) {
			$arraybudget = array('50'=>'<= 100 000', '100'=>'<= 200 000', '200'=>'<= 500 000', '300'=>'<= 1 500 000', '600'=>'<= 3 000 000', '1000'=>'<= 5 000 000', '2000'=>'5 000 000+');
			print '<tr id="trbudget" class="trcompany"><td>'.$langs->trans("TurnoverOrBudget").' <span style="color: red">*</span></td><td>';
			print $form->selectarray('budget', $arraybudget, GETPOST('budget'), 1);
			print ' € or $';

			print '<script type="text/javascript">
			jQuery(document).ready(function () {
				initturnover();
				jQuery("#morphy").click(function() {
					initturnover();
				});
				jQuery("#budget").change(function() {
						if (jQuery("#budget").val() > 0) { jQuery(".amount").val(jQuery("#budget").val()); }
						else { jQuery("#budget").val(\'\'); }
				});
				/*jQuery("#typeid").change(function() {
					if (jQuery("#typeid").val()==1) { jQuery("#morphy").val(\'mor\'); }
					if (jQuery("#typeid").val()==2) { jQuery("#morphy").val(\'phy\'); }
					if (jQuery("#typeid").val()==3) { jQuery("#morphy").val(\'mor\'); }
					if (jQuery("#typeid").val()==4) { jQuery("#morphy").val(\'mor\'); }
					initturnover();
				});*/
				function initturnover() {
					if (jQuery("#morphy").val()==\'phy\') {
						jQuery(".amount").val(20);
						jQuery("#trbudget").hide();
						jQuery("#trcompany").hide();
					}
					if (jQuery("#morphy").val()==\'mor\') {
						jQuery(".amount").val(\'\');
						jQuery("#trcompany").show();
						jQuery("#trbirth").hide();
						jQuery("#trbudget").show();
						if (jQuery("#budget").val() > 0) { jQuery(".amount").val(jQuery("#budget").val()); }
						else { jQuery("#budget").val(\'\'); }
					}
				}
			});
			</script>';
			print '</td></tr>'."\n";
		}

		if (!empty($conf->global->MEMBER_NEWFORM_PAYONLINE)) {
			$typeid = $conf->global->MEMBER_NEWFORM_FORCETYPE ? $conf->global->MEMBER_NEWFORM_FORCETYPE : $form_typeid;
			$adht = new AdherentType($db);
			$adht->fetch($typeid);
			$caneditamount = $adht->caneditamount;

			// Set amount for the subscription:
			// - First check the amount of the member type.
			$amountbytype = $adht->amountByType(1);		// Load the array of amount per type
			$amount = empty($amountbytype[$typeid]) ? (isset($amount) ? $amount : 0) : $amountbytype[$typeid];
			// - If not found, take the default amount only of the user is authorized to edit it
			if ($caneditamount && empty($amount) && !empty($conf->global->MEMBER_NEWFORM_AMOUNT)) {
				$amount = $conf->global->MEMBER_NEWFORM_AMOUNT;
			}
			// - If not set, we accept ot have amount defined as parameter (for backward compatibility).
			if (empty($amount)) {
				$amount = (GETPOST('amount') ? price2num(GETPOST('amount', 'alpha'), 'MT', 2) : '');
			}

			// Clean the amount
			$amount = price2num($amount);
			$showedamount = $amount>0? $amount: 0;
			// $conf->global->MEMBER_NEWFORM_PAYONLINE is 'paypal', 'paybox' or 'stripe'
			print '<tr><td>'.$langs->trans("Subscription");
			if (!empty($conf->global->MEMBER_EXT_URL_SUBSCRIPTION_INFO)) {
				print ' - <a href="'.$conf->global->MEMBER_EXT_URL_SUBSCRIPTION_INFO.'" rel="external" target="_blank" rel="noopener noreferrer">'.$langs->trans("SeeHere").'</a>';
			}
			print '</td><td class="nowrap">';

			if (empty($amount) && !empty($conf->global->MEMBER_NEWFORM_AMOUNT)) {
				$amount = $conf->global->MEMBER_NEWFORM_AMOUNT;
			}

			if (!empty($conf->global->MEMBER_NEWFORM_EDITAMOUNT) || $caneditamount) {
				print '<input type="text" name="amount" id="amount" class="flat amount width50" value="'.$showedamount.'">';
				print ' '.$langs->trans("Currency".$conf->currency).'<span class="opacitymedium"> – ';
				print $amount>0? $langs->trans("AnyAmountWithAdvisedAmount", $amount, $langs->trans("Currency".$conf->currency)): $langs->trans("AnyAmountWithoutAdvisedAmount");
				print '</span>';
			} else {
				print '<input type="hidden" name="amount" id="amount" class="flat amount" value="'.$showedamount.'">';
				print '<input type="text" name="amount" id="amounthidden" class="flat amount width50" disabled value="'.$showedamount.'">';
				print ' '.$langs->trans("Currency".$conf->currency);
			}
			print '</td></tr>';
		}

		if($action == 'renew' || $action == 'update') {
			$displayed_end_date = empty($adh->last_subscription_date_end)? $langs->trans('NoEndSubscription') : dol_print_date($adh->last_subscription_date_end, 'day');
			if (!$adh->getExpired()) {
				print '<tr><td colspan="2"><span class="amountpaymentcomplete">'.$langs->trans("MembershipPaid", $displayed_end_date).'</span><br>';
				print '<div class="opacitymedium margintoponly">'.$langs->trans("PaymentWillBeRecordedForNextPeriodOrUpdateMemberInfo").'</div></td></tr>';
			} else {
				print '<tr><td colspan="2"><span class="amountpaymentneutral">'.$langs->trans("MembershipExpired", $displayed_end_date).'</span><br>';
				print '</td></tr>';
			}
		}
	}

	print "</table>\n";

	print dol_get_fiche_end();

	// Save
	print '<div class="center">';
	$label = ($action == 'create' || $action == 'identify')? $langs->trans("GetMembershipButtonLabel") : $langs->trans("RenewMembershipButtonLabel");
	print '<input type="submit" value="'.$label.'" id="submitsave" name="submitsave" class="button">';
	if ($action == 'renew' || $action == 'update') {
		if ($adh->typeid != $form_typeid) {
			print "<br>".$form->textwithpicto($langs->trans("CannotEditMemberInfo"), $langs->transnoentities("CannotEditMemberInfoLong"));
		}
		else {
			print '<input type="submit" value="'.$langs->trans("UpdateMembershipInfo").'" id="submitupdate" name="submitupdate" class="button">';
		}
	}
	if (!empty($backtopage)) {
		print '<input type="submit" value="'.$langs->trans("Cancel").'" id="submitcancel" class="button button-cancel">';
	}
	print '</div>';


	print "</form>\n";
	print "<br>";
	print '</div></div>';
}
elseif ($action == 'table') {  // Show the table of membership types
	// Get units
	$cunits = new CUnits($db);
	$units = $cunits->fetchAllAsObject();

	$publiccounters = $conf->global->MEMBER_COUNTERS_ARE_PUBLIC;

	$sql = "SELECT d.rowid, d.libelle as label, d.subscription, d.amount, d.caneditamount, d.vote, d.note, d.duration, d.statut as status, d.morphy, COUNT(a.rowid) AS membercount";
	$sql .= " FROM ".MAIN_DB_PREFIX."adherent_type as d";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."adherent as a";
	$sql .= " ON d.rowid = a.fk_adherent_type AND a.statut>0";
	$sql .= " WHERE d.entity IN (".getEntity('member_type').")";
	$sql .= " AND d.statut=1 GROUP BY d.rowid";

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);

		print '<br><div class="div-table-responsive">';
		print '<table class="tagtable liste">'."\n";
		print '<input type="hidden" name="action" value="identify">'; // Go from 'table' to 'identify'

		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans("Label").'</th>';
		print '<th class="center">'.$langs->trans("MembershipDuration").'</th>';
		print '<th class="center">'.$langs->trans("Amount").'</th>';
		print '<th class="center">'.$langs->trans("MembersNature").'</th>';
		print '<th class="center">'.$langs->trans("VoteAllowed").'</th>';
		if($publiccounters) print '<th class="center">'.$langs->trans("Members").'</th>';
		print '<th class="center">'.$langs->trans("NewSubscription").'</th>';
		print "</tr>\n";

		$i = 0;
		while ($i < $num) {
			$objp = $db->fetch_object($result);

			print '<tr class="oddeven">';
			print '<td>'.dol_escape_htmltag($objp->label).'</td>';
			print '<td class="nowrap">';

			if(!empty($objp->duration)) {
				$unit = preg_replace("/[^a-zA-Z]+/", "", $objp->duration);
				print max(1, intval($objp->duration)).' '.$units[$unit];
			} else {
				print $langs->trans("NoEndSubscription");
			}

			print '</td>';
			print '<td class="center"><span class="amount nowrap">';
			$displayedamount = max(intval($objp->amount), intval($conf->global->MEMBER_MIN_AMOUNT));
			$caneditamount = !empty($conf->global->MEMBER_NEWFORM_EDITAMOUNT) || $objp->caneditamount;
			if ($objp->subscription) {
				if ($displayedamount > 0 || !$caneditamount) {
					print $displayedamount.' '.strtoupper($conf->currency);
				}
				if ($caneditamount && $displayedamount>0) {
					print $form->textwithpicto('', $langs->transnoentities("CanEditAmountShortForValues"), 1, 'help', '', 0, 3);
				} elseif ($caneditamount) {
					print $langs->transnoentities("CanEditAmountShort");
				}
			} else {
				print "–"; // No subscription required
			}
			print '</span></td>';
			print '<td class="center">';
			if ($objp->morphy == 'phy') {
				print $langs->trans("Physical");
			} elseif ($objp->morphy == 'mor') {
				print $langs->trans("Moral");
			} else {
				print $langs->trans("MorAndPhy");
			}
			print '</td>';
			print '<td class="center">'.yn($objp->vote).'</td>';
			$membercount = $objp->membercount>0? $objp->membercount: "–";
			if($publiccounters) print '<td class="center">'.$membercount.'</td>';
			print '<td class="center"><button class="button button-save reposition" name="typeid" type="submit" name="submit" value="'.$objp->rowid.'">'.$langs->trans("GetMembershipButtonLabel").'</button></td>';
			print "</tr>";
			$i++;
		}

		// If no record found
		if ($num == 0) {
			$colspan = 8;
			print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
		}

		print "</table>";
		print '</div>';

		print '</form>';
	} else {
		dol_print_error($db);
	}
}


llxFooterVierge();

$db->close();
