<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Bariley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015 	   Claudio Aschieri     <c.aschieri@19.coop>
 * Copyright (C) 2018 	   Ferran Marcet	    <fmarcet@2byte.es>
 * Copyright (C) 2019 	   Juanjo Menent	    <jmenent@2byte.es>
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
 *   	\file       preventionplan_list.php
 *		\ingroup    digiriskdolibarr
 *		\brief      List page for prevention plan
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

dol_include_once('/custom/digiriskdolibarr/class/digiriskdocuments/preventionplan.class.php');

// Load translation files required by the page
$langs->loadLangs(array('projects', 'companies', 'commercial'));
global $conf, $db;
$action      = GETPOST('action', 'alpha');
$massaction  = GETPOST('massaction', 'alpha');
$show_files  = GETPOST('show_files', 'int');
$confirm     = GETPOST('confirm', 'alpha');
$toselect    = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'projectlist';

$title = $langs->trans("PreventionPlan");

$preventionplan = new PreventionPlan($db);
$societe        = new Societe($db);
$contact        = new Contact($db);
$usertmp        = new User($db);

$limit     = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", "alpha");
$sortorder = GETPOST("sortorder", 'alpha');
$page      = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$page      = is_numeric($page) ? $page : 0;
$page      = $page == -1 ? 0 : $page;

if (!$sortfield) $sortfield = "t.ref";
if (!$sortorder) $sortorder = "ASC";

$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml') ? trim(GETPOST('search_all', 'alphanohtml')) : trim(GETPOST('sall', 'alphanohtml'));
$search = array();
foreach ($preventionplan->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha') !== '') $search[$key] = GETPOST('search_'.$key, 'alpha');
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($preventionplan->fields as $key => $val)
{
	if ($val['searchall']) $fieldstosearchall['t.'.$key] = $val['label'];
}

// Definition of fields for list
$arrayfields = array();

foreach ($preventionplan->fields as $key => $val)
{
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) $arrayfields['t.'.$key] = array('label'=>$val['label'], 'checked'=>(($val['visible'] < 0) ? 0 : 1), 'enabled'=>($val['enabled'] && ($val['visible'] != 3)), 'position'=>$val['position']);
}

$arrayfields['t.date_debut']['label'] = 'StartDate';
$arrayfields['t.date_debut']['checked'] = 1;
$arrayfields['t.date_debut']['enabled'] = 1;
$arrayfields['t.date_debut']['position'] = 12;

$arrayfields['t.date_fin']['label'] = 'EndDate';
$arrayfields['t.date_fin']['checked'] = 1;
$arrayfields['t.date_fin']['enabled'] = 1;
$arrayfields['t.date_fin']['position'] = 14;

$arrayfields['t.maitre_oeuvre']['label'] = 'MaitreOeuvre';
$arrayfields['t.maitre_oeuvre']['checked'] = 1;
$arrayfields['t.maitre_oeuvre']['enabled'] = 1;
$arrayfields['t.maitre_oeuvre']['position'] = 15;

$arrayfields['t.ext_society']['label'] = 'ExtSociety';
$arrayfields['t.ext_society']['checked'] = 1;
$arrayfields['t.ext_society']['enabled'] = 1;
$arrayfields['t.ext_society']['position'] = 16;

$arrayfields['t.ext_society_responsible']['label'] = 'ExtSocietyResponsible';
$arrayfields['t.ext_society_responsible']['checked'] = 1;
$arrayfields['t.ext_society_responsible']['enabled'] = 1;
$arrayfields['t.ext_society_responsible']['position'] = 17;

$arrayfields['t.nb_intervenants']['label'] = 'NbIntervenants';
$arrayfields['t.nb_intervenants']['checked'] = 1;
$arrayfields['t.nb_intervenants']['enabled'] = 1;
$arrayfields['t.nb_intervenants']['position'] = 18;

$arrayfields['t.nb_interventions']['label'] = 'NbInterventions';
$arrayfields['t.nb_interventions']['checked'] = 1;
$arrayfields['t.nb_interventions']['enabled'] = 1;
$arrayfields['t.nb_interventions']['position'] = 19;

$arrayfields['t.location']['label'] = 'Location';
$arrayfields['t.location']['checked'] = 1;
$arrayfields['t.location']['enabled'] = 1;
$arrayfields['t.location']['position'] = 21;

// Load Digipreventionplan_element object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

//Permission for digiriskelement_preventionplan
$permissiontoread = $user->rights->digiriskdolibarr->preventionplan->read;
$permissiontoadd = $user->rights->digiriskdolibarr->preventionplan->write;
$permissiontodelete = $user->rights->digiriskdolibarr->preventionplan->delete;

// Security check - Protection if external user
if (!$user->rights->digiriskdolibarr->lire) accessforbidden();

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_createbills') { $massaction = ''; }

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

	$backtopage = dol_buildpath('/digiriskdolibarr/preventionplan_list.php', 1);

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		foreach ($preventionplan->fields as $key => $val) {
			$search[$key] = '';
		}

		$toselect = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	$error = 0;
	if (!$error && ($massaction == 'delete' || ($action == 'delete' && $confirm == 'yes')) && $permissiontodelete) {
		if (!empty($toselect)) {
			foreach ($toselect as $toselectedid) {

				$preventionplantodelete = $preventionplan;
				$preventionplantodelete->fetch($toselectedid);

				$preventionplantodelete->status = 0;
				$result = $preventionplantodelete->update($user, true);

				if ($result < 0) {
					// Delete risk KO
					if (!empty($risk->errors)) setEventMessages(null, $risk->errors, 'errors');
					else  setEventMessages($risk->error, null, 'errors');
				}
			}

			// Delete risk OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		}
	}


}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

$title = $langs->trans("PreventionPlan");
$help_url = 'FR:Module_DigipreventionplanDolibarr';

llxHeader("", $title, $help_url);

// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions = array();
//if($user->rights->societe->creer) $arrayofmassactions['createbills']=$langs->trans("CreateInvoiceForThisCustomer");
if ($user->rights->projet->creer) $arrayofmassactions['close'] = $langs->trans("Close");
if ($user->rights->societe->supprimer) $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
if (in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();

$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$newcardbutton = '';
if ($user->rights->projet->creer)
{
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewPreventionPlan'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/custom/digiriskdolibarr/preventionplan_card.php?action=create');
}

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="type" value="'.$type.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print_barre_liste($form->textwithpicto($title, $texthelp), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'preventionplan', 0, $newcardbutton, '', $limit, 0, 0, 1);

include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

// Build and execute select
// --------------------------------------------------------------------

	$sql = 'SELECT ';
	foreach ($preventionplan->fields as $key => $val)
	{
		$sql .= 't.'.$key.', ';
	}
	// Add fields from extrafields
	if (!empty($extrafields->attributes[$preventionplan->table_element]['label'])) {
		foreach ($extrafields->attributes[$preventionplan->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$preventionplan->table_element]['type'][$key] != 'separate' ? "ef.".$key.' as options_'.$key.', ' : '');
	}
	// Add fields from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $preventionplan); // Note that $action and $preventionplan may have been modified by hook
	$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
	$sql = preg_replace('/,\s*$/', '', $sql);
	$sql .= " FROM ".MAIN_DB_PREFIX.$preventionplan->table_element." as t";

	if (is_array($extrafields->attributes[$preventionplan->table_element]['label']) && count($extrafields->attributes[$preventionplan->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$preventionplan->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";
	if ($preventionplan->ismultientitymanaged == 1) $sql .= " WHERE t.entity IN (".getEntity($preventionplan->element).")";
	else $sql .= " WHERE 1 = 1";
	$sql .= " AND t.type = '".$preventionplan->element . "'";
	$sql .= " AND t.status = 1";


foreach ($search as $key => $val)
	{
		if ($key == 'status' && $search[$key] == -1) continue;
		$mode_search = (($preventionplan->isInt($preventionplan->fields[$key]) || $preventionplan->isFloat($preventionplan->fields[$key])) ? 1 : 0);
		if (strpos($preventionplan->fields[$key]['type'], 'integer:') === 0) {
			if ($search[$key] == '-1') $search[$key] = '';
			$mode_search = 2;
		}
		if ($search[$key] != '') $sql .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
	}
	if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
	// Add where from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $preventionplan); // Note that $action and $preventionplan may have been modified by hook
	$sql .= $hookmanager->resPrint;

	$sql .= $db->order($sortfield, $sortorder);

	// Count total nb of records
	$nbtotalofrecords = '';
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
	{
		$resql = $db->query($sql);

		$nbtotalofrecords = $db->num_rows($resql);
		if (($page * $limit) > $nbtotalofrecords)	// if total of record found is smaller than page * limit, goto and load page 0
		{
			$page = 0;
			$offset = 0;
		}
	}
	// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
	if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit)))
	{
		$num = $nbtotalofrecords;
	}
	else
	{
		if ($limit) $sql .= $db->plimit($limit + 1, $offset);

		$resql = $db->query($sql);
		if (!$resql)
		{
			dol_print_error($db);
			exit;
		}

		$num = $db->num_rows($resql);
	}

	// Direct jump if only one record found
	if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all && !$page)
	{
		$obj = $db->fetch_object($resql);
		$id = $obj->rowid;
		header("Location: ".dol_buildpath('/digiriskdolibarr/digiriskelement_preventionplan.php', 1).'?id='.$id);
		exit;
	}

if ($search_all)
{
	foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
if ($massactionbutton) $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";
print '<tr class="liste_titre">';

$preventionplan->fields['date_debut'] = '';
$preventionplan->fields['date_fin'] = '';
$preventionplan->fields['maitre_oeuvre'] = '';
$preventionplan->fields['ext_society'] = '';
$preventionplan->fields['ext_society_responsible'] = '';
$preventionplan->fields['nb_intervenants'] = '';
$preventionplan->fields['nb_interventions'] = '';
$preventionplan->fields['location'] = '';

foreach ($preventionplan->fields as $key => $val)
{
	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	if (!empty($arrayfields['t.'.$key]['checked']))
	{
		print '<td class="liste_titre'.($cssforfield ? ' '.$cssforfield : '').'">';
		if ($key == 'maitre_oeuvre') {
			print '';
		} elseif ($key == 'date_debut') {
			print '';
		} elseif ($key == 'date_fin') {
			print '';
		} elseif ($key == 'ext_society') {
			print '';
		}  elseif ($key == 'ext_society_responsible') {
			print '';
		} elseif ($key == 'nb_intervenants') {
			print '';
		} elseif ($key == 'nb_interventions') {
			print '';
		} elseif ($key == 'location') {
			print '';
		}
		elseif (is_array($val['arrayofkeyval'])) print $form->selectarray('search_'.$key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth75');
		elseif (strpos($val['type'], 'integer:') === 0) {
			print $preventionplan->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth150', 1);
		}
		elseif (!preg_match('/^(date|timestamp)/', $val['type'])) print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'">';
		print '</td>';
	}
}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $preventionplan); // Note that $action and $preventionplan may have been modified by hook
print $hookmanager->resPrint;

// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>'."\n";

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';

foreach ($preventionplan->fields as $key => $val)
{
	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	if (!empty($arrayfields['t.'.$key]['checked'])) {
		if (preg_match('/MaitreOeuvre/', $arrayfields['t.'.$key]['label']) || preg_match('/StartDate/', $arrayfields['t.'.$key]['label']) || preg_match('/EndDate/', $arrayfields['t.'.$key]['label']) || preg_match('/ExtSociety/', $arrayfields['t.'.$key]['label']) || preg_match('/NbIntervenants/', $arrayfields['t.'.$key]['label']) || preg_match('/NbInterventions/', $arrayfields['t.'.$key]['label']) || preg_match('/Location/', $arrayfields['t.'.$key]['label'])) {
			$disablesort = 1;
		}
		else {
			$disablesort = 0;
		}
		print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''), $disablesort)."\n";

	}

}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $preventionplan); // Note that $action and $preventionplan may have been modified by hook
print $hookmanager->resPrint;

// Action column
print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
print '</tr>'."\n";

$arrayofselected = is_array($toselect) ? $toselect : array();

// Loop on record
// --------------------------------------------------------------------

// contenu
$i = 0;
$totalarray = array();

while ($i < ($limit ? min($num, $limit) : $num)) {

	$obj = $db->fetch_object($resql);

	if (empty($obj)) break; // Should not happen


	// Store properties in $preventionplan
	$preventionplan->setVarsFromFetchObj($obj);

	$json = json_decode($preventionplan->json, false, 512, JSON_UNESCAPED_UNICODE)->PreventionPlan;

	// Show here line of result
	print '<tr class="oddeven preventionplan-row preventionplan_row_'. $preventionplan->id .' preventionplan-row-content-'. $preventionplan->id . '" id="preventionplan_row_'. $preventionplan->id .'">';
	foreach ($preventionplan->fields as $key => $val) {
		$cssforfield = (empty($val['css']) ? '' : $val['css']);
		if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
		elseif ($key == 'ref') $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
		elseif ($key == 'category') $cssforfield .= ($cssforfield ? ' ' : '') . 'preventionplan-category';
		elseif ($key == 'description') $cssforfield .= ($cssforfield ? ' ' : '') . 'preventionplan-description';
		if (!empty($arrayfields['t.' . $key]['checked'])) {
			print '<td' . ($cssforfield ? ' class="' . $cssforfield . '"' : '') . ' style="width:2%">';
			if ($key == 'status') print $preventionplan->getLibStatut(5);
			elseif($key == 'maitre_oeuvre') {
				$usertmp->fetch($json->maitre_oeuvre->user_id);
				print $usertmp->getNomUrl();
			} elseif ($key == 'date_debut') {
				print $json->date->debut;
			} elseif ($key == 'date_fin') {
				print $json->date->fin;
			} elseif ($key == 'ext_society') {
				$societe->fetch($json->society_outside->id);
				print $societe->getNomUrl();
			} elseif ($key == 'ext_society_responsible') {
				$contact->fetch($json->responsable_exterieur->id);
				print $contact->getNomUrl();
			} elseif ($key == 'nb_intervenants') {
				print count((array)$json->intervenant_exterieur);
			} elseif ($key == 'nb_interventions') {
				print 0;
			} elseif ($key == 'location') {
				print $json->location->name;
			}
		else print $preventionplan->showOutputField($val, $key, $preventionplan->$key, '');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
			if (!empty($val['isameasure'])) {
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 't.' . $key;
				$totalarray['val']['t.' . $key] += $preventionplan->$key;
			}
		}
	}
	// Action column
	print '<td class="nowrap center">';
	if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
	{
		$selected = 0;
		if (in_array($preventionplan->id, $arrayofselected)) $selected = 1;
		print '<input id="cb'.$preventionplan->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$preventionplan->id.'"'.($selected ? ' checked="checked"' : '').'>';
	}

	print '</td>';
	if (!$i) $totalarray['nbfield']++;
	print '</tr>'."\n";
	$i++;
}
// If no record found
if ($num == 0)
{
	$colspan = 1;
	foreach ($arrayfields as $key => $val) { if (!empty($val['checked'])) $colspan++; }
	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}
$db->free($resql);

$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $risk); // Note that $action and $risk may have been modified by hook
print $hookmanager->resPrint;

print "</table>\n";
print '</div>';
print "</form>\n";

// End of page
llxFooter();
$db->close();
