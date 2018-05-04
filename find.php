<?php
/**
 *
 * Copyright (C) 2007,2008  Arie Nugraha (dicarve@yahoo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

/* Item List */

// key to authenticate
define('INDEX_AUTH', '1');

// main system configuration
require 'sysconfig.inc.php';
// start the session
//require SENAYAN_BASE_DIR.'admin/default/session.inc.php';
//require SENAYAN_BASE_DIR.'admin/default/session_check.inc.php';
// privileges checking

require SIMBIO_BASE_DIR.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/form_maker/simbio_form_element.inc.php';
require SIMBIO_BASE_DIR.'simbio_DB/datagrid/simbio_dbgrid.inc.php';
require MODULES_BASE_DIR.'reporting/report_dbgrid.inc.php';

$page_title = 'Archive Quick View';
$reportView = false;
$num_recs_show = 20;
if (isset($_GET['reportView'])) {
    $reportView = true;
}
ob_start();

?>
    <!-- filter -->
    <fieldset style="margin-bottom: 3px;">
    <legend style="font-weight: bold"><?php echo strtoupper(__('Pencarian Surat')); ?> </legend>
    <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <div id="filterForm">
        <div class="divRow">
            <?php echo simbio_form_element::textField('text', 'search', '', 'style="width: 50%"'); ?>
    	    <input type="submit" name="applyFilter" value="<?php echo __("Cari"); ?>" />
        </div>
    </div>
    <div style="padding-top: 10px; clear: both;">
<!--    <input type="button" name="moreFilter" value="<?php echo __('Show More Filter Options'); ?>" />
-->
    <input type="hidden" name="reportView" value="true" />
    </div>
    </form>
    </fieldset>
    <!-- filter end -->
    <div class="dataListHeader" style="padding: 3px;"><span id="pagingBox"></span></div>
<?php
    // table spec
    $table_spec = 'recod as r';

    // create datagrid
    $reportgrid = new report_datagrid();
    $reportgrid->setSQLColumn('r.no_surat AS \''.__('Nomor Surat').'\'',
        'CONCAT(r.perihal,\'<br /><i>Notes: \',r.notes,\'</i>\') AS \''.__('Perihal').'\'',
        'r.tgl_surat AS \''.__('Tanggal Surat').'\'',
        'r.pengirim_id AS \''.__('Pengirim').'\'', 'r.recod_id');
    $reportgrid->setSQLorder('r.tgl_surat ASC');

    // is there any search
    $criteria = 'r.recod_id IS NOT NULL ';
    if (isset($_GET['search']) AND !empty($_GET['search'])) {
        $keyword = $dbs->escape_string(trim($_GET['search']));
        $words = explode(' ', $keyword);
        if (count($words) > 1) {
            $concat_sql = ' AND (';
            foreach ($words as $word) {
                $concat_sql .= " (r.perihal LIKE '%$word%' OR r.no_surat LIKE '%$word%' OR r.notes LIKE '%$word%' OR r.pengirim_id LIKE '%$word%' OR r.kategori_id LIKE '%$word%') AND";
            }
            // remove the last AND
            $concat_sql = substr_replace($concat_sql, '', -3);
            $concat_sql .= ') ';
            $criteria .= $concat_sql;
        } else {
            $criteria .= ' AND (r.perihal LIKE \'%'.$keyword.'%\' OR r.no_surat LIKE \'%'.$keyword.'%\' OR r.notes LIKE \'%'.$keyword.'%\' OR r.pengirim_id LIKE \'%'.$keyword.'%\' OR r.kategori_id LIKE \'%'.$keyword.'%\')';
        }
    }

    $reportgrid->setSQLCriteria($criteria);

    // callback function to show title and authors
    function showTitleAuthors($obj_db, $array_data)
    {
        if (!$array_data[4]) {
            return;
        }
        // author name query
        $_biblio_q = $obj_db->query('SELECT CONCAT(\'<a href="repository/\',f.file_name,\'">Lihat berkas</a>\'), r.file_att
	    FROM files as f
            LEFT JOIN biblio_attachment as rf ON f.file_id = rf.file_id
	    LEFT JOIN recod AS r ON r.recod_id = rf.biblio_id
            WHERE rf.biblio_id='.$array_data[4]);
        $_files = '';
	$_no_surat = '';
        while ($_biblio_d = $_biblio_q->fetch_row()) {
            $_no_surat = $_biblio_d[1];
            $_files .= $_biblio_d[0].'<br />';
        }
        $_files = substr_replace($_files, '', -6);
        $_output = $_no_surat.'<br /><i>'.$_files.'</i>'."\n";
        return $_output;
    }
    // modify column value
    $reportgrid->modifyColumnContent(4, 'callback{showTitleAuthors}');
    //$reportgrid->invisible_fields = array(4);

    // put the result into variables

    echo $reportgrid->createDataGrid($dbs, $table_spec, $num_recs_show);

//    echo '<script type="text/javascript">'."\n";
    echo $reportgrid->paging_set;
//    echo '</script>';

    $content = ob_get_clean();
    // include the page template
    require SENAYAN_BASE_DIR.'/admin/'.$sysconf['admin_template']['dir'].'/printed_page_tpl.php';

?>
