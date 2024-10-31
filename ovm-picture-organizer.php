<?php
/* Plugin Name: OVM Picture Organizer
 * Version: 2.0.1
 * Text Domain: picture-organizer
 * Plugin URI: http://www.picture-organizer.com
 * Description: Erweiterte Medienverwaltung um Kategorien, Tags, Daten zur Ausgabe der Urheberrechtsnachweise um kostenpflichtige Abmahnungen zu vermeiden
 * Projekt: ovm-picture-organizer
 * Author: Rudolf Fiedler 
 * Author URI: http://www.picture-organizer.com
 * License: GPLv2 or later

    Copyright (C)  2014-2015 Rudolf Fiedler

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; eit$her version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/



// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if (!class_exists('OVM_global')) {
    class OVM_global
    {
        #public function ($h)
        #{
        #    add_action('admin_notices', 'my_admin_notice');
        #}
        public function updated($h, $e = true)
        {
            $msg = "<div class=\"updated\"><p>{$h}</p></div>";
            if ($e === true)
                echo($msg);
            else
                return msg;
        }

        public function error($h)
        {
            $msg = "<div class=\"error\"><p>{$h}</p></div>";
            if ($e === true)
                echo($msg);
            else
                return msg;
        }

        public function msg($h)
        {
            $msg = "<div class=\"update-nag\"><p>{$h}</p></div>";
            if ($e === true)
                echo($msg);
            else
                return msg;
        }
    }
}

if (!isset($OVM)) {
    $OVM = new OVM_global;
}


/*  Definition der metas für wp_options und post_meta
 *  @since   1.1
 *
 */
define('PO','picture-organizer');                   //Text-domain für Übersetzungsfunktion
define('PO_EMAIL','r.fiedler@ovm.de');
define('OVM_PO_OPTIONS_TAB','ovm_po_options_tab');   //Tab for options-page to save uninstall-settings
define('OVM_PO_SUPPORT_TAB','ovm_po_support_tab');   //Tab for options-page to save uninstall-settings
define('OVM_PO_PREMIUM_TAB','ovm_po_premium_tab');   //Tab for options-page to save uninstall-settings
define('OVM_PO_BACKUP_TAB','ovm_po_backup_tab');     //Tab for managing backups
define('OVM_PO_OUTPUT_OPTIONS_TAB','ovm_po_output_options_tab');  //Tab for output-options
define('OVM_PO_PICTUREDATA_LIZENZ','ovm_picturedata_lizenz');   //meta-key zum Speichern der PIC-Lizenz-Nr., ist Kriterium für das Vorhandensein von Meta-Daten
define('OVM_PO_PUBLISH_CREDIT','ovm_po_picturedata_publish_credit');   //meta-key zum Speichern der PIC-Lizenz-Nr., ist Kriterium für das Vorhandensein von Meta-Daten
define('OVM_PO_PICTUREDATA','ovm_picturedata');   //meta-key zum Speichern der zusätzlichen Lizenzdaten serialized
define('OVM_PO_PREMIUM_SOURCE','http://www.picture-organizer.com/?');
define('OVM_PO_SUPPORT_LINK','http://com.profi-blog.com/po_support');
define('OVM_PO_LINK_TO_PREMIUM','http://www.picture-organizer.com/licensekey');
define('OVM_PO_DB_VERSION',1);   //aktueller Wert dieser Scriptversion
define('OVM_PO_AFFILIATE_INFO','ovm_po_affiliate_info');    //option-key



if (get_option("siteurl")=='http://po') {
    define('OVM_PO_URI', 'http://basic/?ovm_po_info=1&log=1'); //test/development
    define('OVM_PO_PREMIUM_FILE_URI','http://www.picture-organizer.com/?ovm_po_info=load_premium');
}
  else {
   define('OVM_PO_URI','http://com.profi-blog.com/?ovm_po_info=1&log=1'); //production-environment
   define('OVM_PO_PREMIUM_FILE_URI','http://www.picture-organizer.com/?ovm_po_info=load_premium');
 }


class OVM_Picture_organizer
{
    public $plugin_data, $blogurl, $checked, $blog_language, $db_db_version;
    public $dashboard_warning, $dashboard_info, $commercial, $support_info;
    public $plugins_path, $plugins_url, $premium_version,$affiliate_info;

    /**
     * Konstruktor der Klasse
     *
     * @since   1.1
     */
    public function __construct()
    {
        $path = dirname(plugin_basename(__FILE__)) . "/languages/";
        load_plugin_textdomain(PO, false, $path);

        $this->blogurl = get_bloginfo('url');
        $this->plugins_path = plugin_dir_path(__FILE__);
        $this->img_path = $this->plugins_path."/img/";
        $this->plugins_url = plugins_url("/", __FILE__);
        $this->only_premium_info = __("Only available in Premium-Version", PO);
        $this->premium_file = $this->plugins_path . "inc/ovm_po_premium.php";

        register_activation_hook(__FILE__, array($this, 'picture_organizer_activate'));  //Anlegen der Affiliate-Info
        $this->affiliate_info = get_option(OVM_PO_AFFILIATE_INFO);
        if (false ===$this->affiliate_info) {
            $this->picture_organizer_activate();
            $this->affiliate_info = get_option(OVM_PO_AFFILIATE_INFO);
        }

        $promotion_title = __("Better media-management with picture-organizer", PO);
        $this->promotion_link = "<a href=\"http://www.picture-organizer.com/bestellung/?aff=[aff_id]\" target=\"_blank\" title=\"{$promotion_title}\">&copy; picture-organizer.com</a>";

        $this->blog_language = substr(get_bloginfo('language'), 0, 2);

        //check, ob ovm_po_dbversion schon gesetzt ist, falls nicht, setzen auf 0
        $this->db_db_version = get_option('ovm_po_db_version');
        if ($this->db_db_version === false) add_option('ovm_po_db_version', '0');

        $this->check_db_updates();

        $this->po_premium_info=maybe_unserialize(get_option("po_premium_info"));  //einlesen der Premium-infos
        if ($this->po_premium_info==false){
            $this->init_premium_info();  //deletes all premium_info
            $this->po_premium_info=maybe_unserialize(get_option("po_premium_info"));  //erneut in die Eigenschaft einlesen mit definierten Werten.
        }

        if ($this->is_premium()){
            $pfe = file_exists($this->premium_file);
            if ($pfe==false)
              $this->get_po_file();
            //echo("truetrue");
        }
        else{
            //echo("falsefalse");

        }




        $this->po_output_options = get_option(OVM_PO_OUTPUT_OPTIONS_TAB);
        $plugin_init=$this->po_output_options;
        if (false === $plugin_init) {
            $this->plugin_init();
        }

        if (is_admin()) { //actions for backend
            switch ($this->blog_language) {
                case "de":
                    $this->href_order = "http://www.picture-organizer.com/bestellung-download/";
                    break;
                default:
                    $this->href_order = "http://www.picture-organizer.com/order/";
            }
            $this->href_order .= "?aff=" . $this->affiliate_info["ovm_po_affiliate_id"];

            add_action('admin_head', array($this, 'css_for_mediadetails'));
            add_action('admin_head', array($this, 'get_po_links'));
            add_action('admin_menu', array($this, 'my_plugin_menu'));
            add_filter("attachment_fields_to_edit", array($this, "add_image_attachment_fields_to_edit"), 10, 2);
            add_filter("attachment_fields_to_save", array($this, "add_image_attachment_fields_to_save"), 10, 2);

            add_action('wp_dashboard_setup', array($this, 'show_dashboard_box'));
            add_action('admin_notices', array($this, 'show_dashboard_warning'));

            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'ovm_po_mediacategory_add_plugin_action_links'));
            add_action("admin_init", array($this, "media_meta_boxen"));

        } else {//frontend
            add_shortcode('ovm_picture-organizer', array($this, 'show_lizenzinformationen'));
        }
    }


    /** is_development() gibt true zurück, wenn es die lokale Entwicklungsumgebung ist.
     * @return bool
     */
    private function is_development() {
        if ($_SERVER["SERVER_NAME"] == "po")
           return true;
        else
            return false;
    }


   private function init_premium_info() {
       $args=array(
           "license"=>'',
           "email"=>'',
           "version"=>0,
           "start_date"=>''
       );
       update_option('po_premium_info',$args);
       $this->po_premium_info=$args;

        if ($this->is_development()) {
            echo("<p>Entwicklungsversion: Hier würde das Premium-File gelöscht....</p>");
        }
       else {
           unlink($this->premium_file);
       }
   }


    /** Checks, weather the premium-license is activated or not
     * @return bool
     */
    public function is_premium() {
        if ($this->po_premium_info['license']>'')
            return true;
        else
            return false;
    }


    public function my_admin_error_notice() {
        $screen = get_current_screen();
        //Ausgabe der Statusmeldung nur auf diesen Seiten!
        $pos = array_search($screen->base,array("plugins","settings_page_picture-organizer-options","upload","post"));
        if ($pos > -1)
          echo"<div class=\"error\"> <p>$this->admin_notice</p></div>";

    }


    /**  check_db_updates
     *   prüft, ob die tatsächliche db_version mit der notwendigen db_version der scripte zusammenpasst,
     *   und führt im Bedarfsfall die einzelnen datenbankupdates durch
     *   Die Datenbankversion ist jeweils ganzzahlig
     *   für jeden Versionssprung wird in der perform_db_update ein Bereich ausgeführt,
     *   so dass u.U. mehrere notwendigen Updates in der richtigen Reihenfolge durchgeführt werden,
     *   und so kein update doppelt gemacht wird oder fehlt.
     */
    private function check_db_updates()
    {
        //$i ist dann die aktuelle db-version, in perform_db_update ist $ definiert als db_version, für die ein update gefahren werden muss

        $db_plugin_version = (int)OVM_PO_DB_VERSION;  //quellcodeversion
        for ($i = (int)$this->db_db_version; $i <= $db_plugin_version; $i++) {
            $this->perform_db_update($i);
        }
    }


    /**
     * @param $i
     * $i ist die Datenbankversion, für die ein update oder mehrere Updates gefahren werden.
     * $i wird dann dem case-block zugeordnet
     * d.h. für die datenbankvesion $i muss case $i gemacht werden, um die datenbankversion $i um 1 zu erhöhen.
     */
    private function perform_db_update($i)
    {
        $i = (int)$i;
        switch ($i) {
            case 1:
                //echo("\nupdaten version {$i} \n");
                //gibts noch nicht, hier werden dann für jedes $i die neuen Version eingetragen.
                break;
            case 0:
                //alle die attachments, die in lizenz eine nummer drin haben, auf publish_credit auf 1 setzen.
                //Grund: Bisher wurden die credits ausgegeben, wenn eine lizenz-nr. da war, ab sofort erfolgt die Ausgabe, wenn das publish_credit-gesetzt ist.
                //warum der query so kompliziert:  die Abfrage mit met_compare > '' brachte immer alle, da post_type abgefragt wurde..?????
                $args = array(
                    'post_type' => 'attachment',
                    'nopaging' => true,
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => OVM_PO_PICTUREDATA_LIZENZ,
                            'value' => '',
                            'compare' => '>'))

                );
                $post_list = get_posts($args);
                foreach ($post_list as $post) {
                    //check, ob publish_credit gesetzt ist, wenn nicht dann setzen auf 1
                    $x = get_post_meta($post->ID, OVM_PO_PUBLISH_CREDIT);
                    if (count($x) == 0) add_post_meta($post->ID, OVM_PO_PUBLISH_CREDIT, "1");
                }
                update_option('ovm_po_db_version', $i + 1); //updaten der Version auf die eben aktualisierte nächst höhere vom Status her!
                break;
        }

    }


    private function error_message($h)
    {
        return ("<div id=\"message\" class=\"updated notice is-dismissible\">{$h}</div>");
    }


private function get_po_file()
{
    $old_version = (float)$this->po_premium_info["version"];
    $update_get = "http://www.picture-organizer.com/?po_license={$this->po_premium_info['license']}&version={$this->po_premium_info['version']}&email={$this->po_premium_info['email']}";
    $result = wp_remote_fopen($update_get);  //Seiteninhalt steht in Result, das ist entweder die php-datei oder etwas was abgefangen werden muss
    if ($result > '')  //newer version found, steht in result
    {
        $source_code_ok = strpos($result, 'OVM_PO_PREMIUM_SOURCE');  //prüfen, ob es sich um die richtige Datei handelt
        if ($source_code_ok < 1) die(); //Ende, weil kein gültiger sourcecode übergeben wurde, Keyword fehlt

        if ($this->is_development()) {// Achtung, abfangen, dass nicht der lokale Quellcode überschrieben wird
            echo("hier würde upgedated, wenn es nicht die Entwicklungsversion wäre.......");
            $x = "";
        } else {
            $x = @file_put_contents($this->plugins_path . "inc/ovm_po_premium.php", $result);
        }
        if ($x === false) {
            $OVM->error(__("Error during install Premium-Files", PO), true);
        } else
            //!!$OVM->updated(__("Premium-Files successfully installed - Reload Page to see the new Version", PO), true);
            return $this->error_message(__("New Plugin-Code successfull installed", PO));
        return;
    }
}





    /**
     * @return string
     */
    public function update_premium_version()
    {
        global $OVM;
        if ($this->is_premium() === false) {
            return $this->error_message(__("Error: Picture-Organizer-Premium is not active", PO));
        }
        //hier ist $this->premium-version schon 1
        $this->get_po_file();
    }

    /**   Function für die Aktivierung
     *    Die funktion prüft, ob bereits ein Affiliate-key in den options defiiert ist
     *    wenn ja, wird nichts gemacht
     *    wenn nein wird der affiliate-key in den options gesichert für eine Zuordnung des keys beim Nachkauf des Plugins
     *    first wins........
     */
    public function picture_organizer_activate()
    {
        $affiliate_info = maybe_unserialize(get_option(OVM_PO_AFFILIATE_INFO));
        if (false===$affiliate_info) {
            @include($this->plugins_path."inc/affiliate.php");
            if (!isset($this->affiliate_id)) $this->affiliate_id="";
            if (!isset($this->campaignkey)) $this->campaignkey="";
             update_option(OVM_PO_AFFILIATE_INFO, array(
               'ovm_po_affiliate_id' => $this->affiliate_id,
               'ovm_po_affiliate_campaign_key'=>$this->campaignkey,
               'ovm_po_affiliate_date' => current_time('mysql', false)));
            }
    }

    /** Add a link to media categories on the plugins page */
    public function ovm_po_mediacategory_add_plugin_action_links($links)
    {
        return array_merge(
            array(
                'support' => '<a target="_blank" href="' . OVM_PO_SUPPORT_LINK . '">' . __('Support',PO) . '</a>',
            ),
            $links
        );
    }


    /**  Bei Aktivierung des Plugins werden die Default-Einstellungen für die Ausgabe definiert und gespeichert
     *   plugin_init wird auch beim setzen auf die Default-Werte eingesetzt
     *
     */
    public function plugin_init()
    {
        $vars["promotion_text_line"] = __("Simple Management and Output of Picture-Credits with the Picture-Organizer", PO);
        $this->promotion_ausgabe['promotion_text_line'] = $vars["promotion_text_line"];
        $vars["promotion_position"] = 0;
        eval('$ovm_po_css = "' . $this->ovm_get_template('default.css') . '";');
        $vars['ovm_po_css'] = $ovm_po_css;
        update_option(OVM_PO_OUTPUT_OPTIONS_TAB, $vars);
        unset($vars);
        update_option(OVM_PO_OPTIONS_TAB, 0);
    }

    /**  get_po_links()
     *   Checks only in dashboard-mode, attachment-details and in po-settings
     *   Added: Checks the current premium-Version
     */








    public function get_po_links()
    {
        $screen = get_current_screen();
        if ($screen->base == 'dashboard' or $screen->base == "settings_page_picture-organizer-options" or ($screen->base == 'post' and $screen->post_type == 'attachment')) {
            $this->possible_uris = $this->get_uri(OVM_PO_URI . "&premium=" . $this->premium_version);
            if (is_object($this->possible_uris)) {
                $this->dashboard_warning = ($this->possible_uris->dashboard_warning > '') ? $this->possible_uris->dashboard_warning : '';
                $this->dashboard_info = ($this->possible_uris->dashboard_info > '') ? $this->possible_uris->dashboard_info : '';
                $this->commercial = ($this->possible_uris->commercial > '') ? $this->possible_uris->commercial : '';
            }
        }
        return;
    }

    /*  show_dashboard_warning()
     *  shows a warning in the dashboard in case of a link ist omitted via com.picture-organizer.com
     *
     */
    public function show_dashboard_warning()
    {
        if ($this->dashboard_warning > '') {
            $info = $this->get_uri($this->dashboard_warning, false);
            if ($info > '') {
                echo "<div class=\"error\"><p>{$info}</p></div>";
            }
        }
    }


    /*  show_dashboard_box()
     *  shows a dashboard-info-box in the dashboard in case of a link ist omitted via com.picture-organizer.com
     *  only using for important infos, but not warnings.
     */
    public function show_dashboard_box()
    {
        if ($this->dashboard_info > '')
            wp_add_dashboard_widget("ovm_picture_organizer", "OVM Picture-Organizer", array($this, 'picture_organizer_dashboard_widget_content'));
        return;
    }

    /*  picture_organizer_dashboard_widget_content()
     *  Using only for important infos like neccessary update or anything else...
     *  Is only shown if OVM_PO_DASHBOARD_INFO > '', is controlled in _construct width setting action or not
     *
     */
    public function picture_organizer_dashboard_widget_content()
    {
        if ($this->dashboard_info > '') echo($this->get_uri($this->dashboard_info, false));
    }


    /* Definition des Options-Menüs
 *  @since   1.1
     */
    public function my_plugin_menu()
    {
        add_options_page('Picture-Organizer', 'Picture-Organizer', 'manage_options', 'picture-organizer-options.php', array($this, 'picture_organizer_options'));
    }

    /**  Tool for settings
     *
     */
    public function picture_organizer_options()
    {
        global $wpdb, $OVM_Premium, $OVM;
        $active_tab = sanitize_text_field(isset($_GET['tab']) ? $_GET['tab'] : OVM_PO_OUTPUT_OPTIONS_TAB);
        $this->checked = ' checked="checked" ';
//-----------------------speichern der Optionen
        if (count($_POST) > 0) {// Speichern:
            unset($vars);  //init
            check_admin_referer('picture-organizer-options');
            switch ($active_tab) {
                case OVM_PO_BACKUP_TAB:
                    if (@$_POST["delete_backup_file"] > "") {
                        @$OVM_Premium->delete_backup_file($_POST["delete_backup_file"]);
                    }

                    if (@$_POST["submit_backup"] === "submit") {
                        if ($this->is_premium()) {
                            if (method_exists($OVM_Premium, "create_backup")) {
                                $OVM_Premium->create_backup();
                            } else {
                                $backup_headline = __("Backup Picturedata", PO);
                                $backup_legend = __("Processing Picture-Organizer-Backups", PO);
                                $available_backups = __("Available Picturedata-Backups", PO);
                                $backup_details = ""; //Init der Tabellenzeilen
                                eval('$h = "' . $OVM_Picture_organizer->ovm_get_template('backup.html') . '";');
                                echo($h);
                            }
                        }
                    }
                    break;
                case OVM_PO_OPTIONS_TAB:
                     (isset($_POST["uninstall_delete"])) ? $option=1: $option=0;
                     update_option("ovm_po_uninstall_delete", $option);
                    unset($vars);
                    break;
                case OVM_PO_OUTPUT_OPTIONS_TAB:
                    if (@$_POST["submit_restore_css"] > '') {  //button mit Wiederherstellung der Original-CSS angeklickt und des Original-TExtes
                        $this->plugin_init();
                    } else {
                        $vars["ovm_po_css"] = $_POST['ovm_po_css'];

                        if (isset($_POST["promotion_text_line"]))
                            $promotion_text_line = stripslashes($_POST["promotion_text_line"]);
                          else
                            $promotion_text_line="";
                        $vars["promotion_text_line"] =$promotion_text_line;
                        $vars["promotion_position"] = (int)$_POST["promotion_position"];
                        $vars["digistore_id"] = $_POST['digistore_id'];
                        $vars["group_by_owner"] = (int)@$_POST["group_by_owner"];
                        $vars["use_picture_categorizing"] = (int)@$_POST["use_picture_categorizing"];
                    }
                    break;

                case OVM_PO_PREMIUM_TAB:
                    (isset($_POST["update_premium_version"])) ? $start_update=true : $start_update=false;
                    (isset($_POST["remove_premium_version"])) ? $remove_premium_version=true : $remove_premium_version=false;

                    //Zuerst für non-premium: Eingabe der Premium-Daten
                    if (!$this->is_premium()) {
                        $ovm_license = (isset($_POST['ovm_po_license'])) ? $_POST['ovm_po_license'] : '';
                        $email = (isset($_POST['ovm_po_email'])) ? $_POST['ovm_po_email'] : '';
                        $siteurl = get_site_url();
                        $this->premium_uri = "http://www.picture-organizer.com/?ovm_license={$ovm_license}&email={$email}&action=get_license&uri=$siteurl";
                        $lizenz = wp_remote_fopen($this->premium_uri);
                        $lizenz = ($lizenz == 'true') ? true : false;
                        if (false === $lizenz) {
                            $lizenz_error_headline = __("License-Error, possible Reasons");
                            $reasons = array(
                                __("Invalid License-Key"),
                                __("Invalid E-Mail"),
                                __("You own no License"),
                                __("Too much License used")
                            );
                            $h = "<div class=\"error\"><h3>{$lizenz_error_headline}</h3>";
                            $h .= "<ul style=\"list-style-type:circle !important;margin-left:24px\">";
                            foreach ($reasons as $reason) {
                                $h .= "<li>{$reason}</li>";
                            }
                            $h .= "</ul>";
                            $h .= "<strong>" . __("Premium-License for this blog not activated") . "</strong>";
                            $h .= "</div>";
                            echo($h);

                        } else {
                            $vars['ovm_po_license'] = (isset($_POST['ovm_po_license'])) ? $_POST['ovm_po_license'] : '';
                            $vars['ovm_po_email'] = (isset($_POST['ovm_po_email'])) ? $_POST['ovm_po_email'] : '';
                            update_option($active_tab, $vars);
                            $update_result = $this->update_premium_version();
                            @include($this->premium_file);

                            $this->po_premium_info=array(
                                "license"=>$vars['ovm_po_license'],
                                "email"=>$vars['ovm_po_email'],
                                "version"=>OVM_PO_PREMIUM_VERSION,
                                "start_date"=>current_time('mysql')
                            );
                            update_option('po_premium_info',$this->po_premium_info);
                            unset($vars);
                        }
                    } else {  //Hier Action, wenn bereits Premium-Version ist. In diesem FAll wenn clickfeld aktiviert, update des premium-plugins

                        if ($start_update) {
                            $update_result = $this->update_premium_version();
                            echo($update_result);
                        }
                        if ($remove_premium_version) {
                            $this->init_premium_info();   //deletes premium if exists
                            echo("<div class=\"error\">" . __("Premium version removed", PO) . "</div>");

                        };
                    }
                    break;
                //echo($h);

            }
            if (isset($vars)) {
                update_option($active_tab, $vars);
                unset($vars);
            }
        }


//-----------------------Ende speichern der Optionen
        $o = maybe_unserialize(get_option($active_tab));
        if (is_array($o)) extract($o);
        //CSS für die eigenen Inhalte
        ?>
        <style type="text/css">
            #form_div {
                padding-top: 12px;
                display: block;
                float: left;
                width: 700px;
                margin-right: 24px;
            }

            #ovm_po_commercials {
                margin-top: 18px;
                display: block;
                border: 1px solid #aaaaaa;
                width: 320px;
                float: left
            }

            .ovm fieldset {
                border: 1px solid #aaaaaa;;
                padding: 8px;
                width: 100%
            }

            .ovm table th {
                text-align: left;
                width: 200px
            }

            .ovm table th, .ovm table td {
                vertical-align: top
            }

            .ovm textarea {
                width: 100%;
                height: 200px;
                font-size: 10pt
            }
        </style>

        <div class="wrap ovm">
            <h2><?php _e("Picture-Organizer - Settings", PO)?></h2>
            <?php settings_errors(); ?>
            <h2 class="nav-tab-wrapper">
                <a href="?page=picture-organizer-options.php&tab=<?php echo OVM_PO_OUTPUT_OPTIONS_TAB?>"
                   class="nav-tab <?php echo $active_tab == OVM_PO_OUTPUT_OPTIONS_TAB ? 'nav-tab-active' : ''; ?>"><?php _e("output-settings", PO)?></a>
                <a href="?page=picture-organizer-options.php&tab=<?php echo OVM_PO_PREMIUM_TAB?>"
                   class="nav-tab <?php echo $active_tab == OVM_PO_PREMIUM_TAB ? 'nav-tab-active' : ''; ?>"><?php _e("Premium-Settings", PO)?></a>
                <a href="?page=picture-organizer-options.php&tab=<?php echo OVM_PO_SUPPORT_TAB?>"
                   class="nav-tab <?php echo $active_tab == OVM_PO_SUPPORT_TAB ? 'nav-tab-active' : ''; ?>"><?php _e("Support-Infos", PO)?></a>
                <a href="?page=picture-organizer-options.php&tab=<?php echo OVM_PO_OPTIONS_TAB?>"
                   class="nav-tab <?php echo $active_tab == OVM_PO_OPTIONS_TAB ? 'nav-tab-active' : ''; ?>"><?php _e("Uninstall-Settings", PO)?></a>
                <a href="?page=picture-organizer-options.php&tab=<?php echo OVM_PO_BACKUP_TAB?>"
                   class="nav-tab <?php echo $active_tab == OVM_PO_BACKUP_TAB ? 'nav-tab-active' : ''; ?>"><?php _e("Backup", PO)?></a>
            </h2>

            <div id="form_div">
                <form method="post" action="#" id="options_form">
                    <?php
                    $show_submit_button=true; //default
                    wp_nonce_field('picture-organizer-options');
                    switch ($active_tab) {
                        case OVM_PO_BACKUP_TAB:
                            if ($this->is_premium()) {
                                if (method_exists($OVM_Premium, "show_backup_page")) {
                                    echo($OVM_Premium->show_backup_page());
                                }
                            } else {

                                switch($this->blog_language) {
                                    case "de":
                                        $template="no_backup_de.html";
                                        break;
                                    default:
                                        $template = "no_backup_en.html";
                                }
                                eval('$h = "' . $this->ovm_get_template($template).'";');
                                echo($h);
                            }
                            break;

                        case OVM_PO_OUTPUT_OPTIONS_TAB:
                            if (!is_array($o)) {
                                $this->plugin_init();
                                $o = get_option('active_tab');
                                extract($o);
                            }
                            if (!isset($digistore_id)) $digistore_id = "";
                            if (!isset($promotion_position)) $promotion_position = 0;  //Default keine Ausgabe!
                            if (!isset($ovm_po_css)) eval('$ovm_po_css = "' . $this->ovm_get_template('default.css') . '";');
                            if (@$o["group_by_owner"] == 1)
                                $group_by_owner = " checked=\"checked\"";
                            else
                                $group_by_owner = "";

                            if (!isset($promotion_text_line)) $promotion_text_line="";

                            ?>
                            <fieldset>
                                <legend><?php _e("Settings for Image-Credits-Output", PO);?></legend>
                                <table id="ovm_po_credits">
                                    <tr>
                                        <th><?php _e("HTML-Text for output", PO)?></th>
                                        <td><textarea name="promotion_text_line"
                                                      id="promotion_text_line"><?php echo($promotion_text_line)?></textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e("Text-Position-Setting", PO)?><p
                                                class="info"><?php _e("This sample-table shows you the using of the css-classes", PO)?></p>
                                        </th>
                                        <td>
                                            <p><?php _e("Show your visitor, that you have a professional image-organisation", PO)?>
                                                <br><?php _e("Best effect ist the position above the picture-credits", PO)?>
                                                .</p>

                                            <input type="radio" name="promotion_position" id="promotion_position"
                                                   value="0" <?php echo((0 == $promotion_position) ? $this->checked : '') ?>> <?php _e("Don't show", PO)?>
                                            <br>
                                            <input type="radio" name="promotion_position" id="promotion_position"
                                                   value="1" <?php echo((1 == $promotion_position) ? $this->checked : '') ?>> <?php _e("Show above", PO)?>
                                            <br>
                                            <input type="radio" name="promotion_position" id="promotion_position"
                                                   value="2" <?php echo((2 == $promotion_position) ? $this->checked : '') ?>> <?php _e("Show below", PO)?>
                                            <br>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e("CSS-Output-Sample", PO);?></th>
                                        <td>
                                            <?php
                                            $image_title = "";
                                            $image_link = "";
                                            $image_textlink = "";
                                            eval('$sample_table = "' . $this->ovm_get_template('picture_credits.html') . '";');
                                            echo nl2br(htmlentities($sample_table));
                                            ?>
                                            <textarea name="ovm_po_css"
                                                      id="ovm_po_css"><?php echo($ovm_po_css)?></textarea></td>
                                    </tr>
                                    <tr>
                                        <th>
                                            <?php
                                            if ($this->is_premium()) {
                                                $disabled_premium = '';
                                                $disabled_text = "";
                                            } else {
                                                $disabled_premium = 'disabled="disabled"';
                                                $disabled_text = $this->only_premium_info;
                                            }

                                            _e("Output Sort and Group by Owner", PO)?></th>
                                        <td><input name="group_by_owner" value="1" <?php echo $group_by_owner ?>
                                                   type="checkbox" <?php echo $disabled_premium ?>><?php _e("Activate Checkbox if Output should be sorted and grouped by the Picture-Owner", PO)?>
                                            <br><?php echo $disabled_text?></td>
                                    </tr>
                                </table>
                            </fieldset>
                            <fieldset>
                                <legend><?php _e("Earn Money with Picture-Organizer", PO)?></legend>
                                <table>

                                    <tr>
                                        <th><?php _e("Digistore-ID", PO)?><p class="info"></th>
                                        <td><input type="text" name="digistore_id" id="digistore_id"
                                                   value="<?php echo $digistore_id ?>"><?php _e("Enter your digistore-ID to earn money", PO)?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            <a href="http://www.profi-blog.com/pp/partnerprogramm-affiliateprogramm-picture-organizer/"
                                               title="Picture-Organizer Affiliate-Programm" target="_blank"><img
                                                    width="85%"
                                                    src="http://www.picture-organizer.com/wp-content/uploads/2015/08/get-more-infos.jpg"></a>
                                        </th>
                                        <td><?php _e("You like this tool? This tool has no competitors, and it's very cheap. You need not to sell it, you can give it", PO);?>
                                            <br>
                                            <a target="_blank"
                                               href="http://www.profi-blog.com/pp/partnerprogramm-affiliateprogramm-picture-organizer/"><?php _e("More about the easy Affiliate-Program of Picture-Organizer on the Affiliate-Support-Page", PO) ?></a>
                                        </td>

                                    </tr>
                                    </a>
                                </table>
                            </fieldset>
 <!--
                            <fieldset><legend><?php _e("Picture-Categorizing",PO)?></legend>
                                <table>
                                    <tr>
                                        <th><?php _e("Use Picture Categorizing",PO)?></th>
                                        <td><input type="checkbox" value="1" name="use_picture_categorizing" id="use_picture_categorizing" <?php  echo ($use_picture_categorizing==1 ? 'checked="checked"':'')?>></td>
                                    </tr>

                                </table>

                            </fieldset>
 -->                           <fieldset>
                                <legend><?php _e("Restore default values", PO)?></legend>
                                <p><?php _e("Click the following button if you want to restore the default values", PO)?></p>

                                <p style="width:100%;text-align:right;cursor:pointer;font-weight:bold"><input
                                        class="button button-primary" name="submit_restore_css" type="submit"
                                        value="<?php _e("Restore Default-Values", PO)?>"/>
                            </fieldset>
                            <?php
                            break;


                        case OVM_PO_PREMIUM_TAB:
                            if (false === $this->is_premium()) {
                                ?>
                                <table style="width:900px" border="0" table-layout:fixed;>
                                    <tr>
                                        <td style="width:620px">
                                            <fieldset style="width:620px;margin-bottom:24px;background-color:#f9f9f9">
                                                <legend><?php _e("Picture-Organizer Premium", PO) ?></legend>
                                                <h2 style="color:#ff0000;font-weight:bold"><?php _e("Your Advantages with the Premium Version", PO); ?></h2>

                                                <p style="color:#ff0000;font-weight:bold"><?php _e("The Premium Version of Picture-Organizer has a lot of advantiges for your daily work with WordPress", PO) ?></p>
                                                <ul style="list-style-type:square !important;margin-left:24px">
                                                    <li><?php _e("Show the picture-credits on the bottem of the page where the Pictures are shown", PO) ?></li>
                                                    <li><?php _e("Use Categories for your Media", PO) ?></li>
                                                    <li><?php _e("Search and filter the Categories for your Media", PO) ?></li>
                                                    <li><?php _e("Use Tags for your Media", PO) ?></li>
                                                    <li><?php _e("Show the credits marked image on the page/post with the image", PO) ?></li>
                                                    <li><?php _e("Easy Backup your Media-Files and Picture-Credits", PO) ?></li>
                                                    <li><?php _e("Output the Picture-Credits for each Categorie as XML", PO) ?></li>
                                                    <li><?php _e("Show the usage for each Picture (On Picture-Details-Site)", PO) ?></li>
                                                </ul>
                                                <a href="<?php echo  $this->href_order ?>" target="_blank"><div style="font-weight:bold;font-size:12pt"><?php _e("Order Premium Picture-Organizer NOW", PO) ?></div></a>

                                            </fieldset>
                                            <fieldset style="width:620px">
                                                <legend><?php _e("License-key",PO)?></legend>
                                                <p><?php _e("After you have ordered the Premium-Version you will recive an E-Mail with your License-Key", PO) ?></p>
                                                <p><?php _e("Please enter your data you got after buy the license", PO) ?></p>
                                                <table>
                                                    <tr>
                                                        <th style="width:150px;white-space: nowrap"><?php _e("License-Key", PO) ?></th>
                                                        <td><input type="text" name="ovm_po_license" id="ovm_po_license"
                                                                   style="width:440px" value="<?php echo $this->po_premium_info['license'] ?>">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th style="white-space: nowrap"><?php _e("E-Mail-adress", PO) ?></th>
                                                        <td><input type="text" name="ovm_po_email" id="ovm_po_email" style="width:300px" value="<?php echo $this->po_premium_info['email']?>"></td>
                                                    </tr>
                                                </table>
                                            </fieldset>
                                        </td>
                                        <td style="width:250px">
                                            <?php $this->show_premium_version(); ?>
                                        </td>
                                    </tr>
                                </table>
                            <?php
                            }  //end of $this->is_premium()
                            else {//Lizenz vorhanden, Lizenz auslesen
                                echo("<h3>"); _e("You already use the Premium-Version", PO); echo("</h3>");
                                ?>
                                <fieldset>
                                    <legend><?php _e("Check for your intalled Premium-Version", PO); ?></legend>
                                    <table>
                                        <tr>
                                            <th><?php _e("Current Version installed", PO) ?></th>
                                            <td><?php echo $this->po_premium_info['version']?></td>
                                        </tr>
                                        <tr>
                                            <th><?php _e("Check for actual Version", PO); ?></th>
                                            <td><input class="button button-primary" type="submit" value="Premium-Version update starten" name="update_premium_version">
                                                (<?php _e("Click to check for Premium-Updates", PO); ?>).
                                            </td>
                                        </tr>
                                    </table>
                                </fieldset>
                                <fieldset>
                                    <legend><?php _e("Remove Premium-Version", PO) ?></legend>
                                    <? $show_submit_button = false ?>
                                    <table>
                                        <tr>
                                            <th><?php _e("Remove Premium-Version", PO); ?></th>
                                            <td><input class="button button-primary"  type="submit" value="remove_premium_version" name="remove_premium_version"/>
                                                (<?php _e("Removes Premium-Features of Picture-Organizer", PO); ?>).
                                            </td>
                                        </tr>
                                    </table>
                                </fieldset>
                            <?php
                            }
                            break;

                        case OVM_PO_SUPPORT_TAB:
                            $support_content = $this->get_uri(OVM_PO_SUPPORT_LINK, false);
                            echo($support_content);
                            break;

                        case OVM_PO_OPTIONS_TAB:
                            $uninstall_delete = (int)get_option('ovm_po_uninstall_delete');
                            (1 == $uninstall_delete) ? $c = "checked = \"checked\"" : $c = "";
                            ?>
                            <fieldset>
                                <legend><?php _e("Uninstall Settings", PO)?></legend>
                                <table>
                                    <tr>
                                        <th><?php _e("Delete all data during uninstall", PO)?></th>
                                        <td><input type="checkbox" name="uninstall_delete" value="1" <?php echo $c ?>></td>
                                    </tr>
                                </table>
                            </fieldset>
                            <?php
                            break;
                    } // end switch
                    ?>
                    <?php if ($show_submit_button) submit_button();?>
                </form>
            </div>
            <?php
            if ($this->commercial > '') {
                $h = '<div id="ovm_po_commercials">' . $this->get_uri($this->commercial, false) . '</div>';
                echo($h);
            }?>
        </div><!-- /.wrap -->
    <?php
    }


    /**
     * Sortierfunktion für das Ergebnisarray, in dem die auszugebenden picture-credits gespeichert werden.
     * @param $a
     * @param $b
     * @return int
     */
    function sort_credit_array($a,$b) {
        return strcasecmp($a['author'],$b['author']);
    }



    /**
 * @param array $src
 * @return string (alle post/type=attachment
 * Show all credits width activated show_credit-button or license > ''
 */
public function get_picture_credits($src= array(),$all=true) {
    global $wpdb;
    if($all===true) { //Ausgabe für alle Bilder der gesamten Homepage
        $args = array(
            'post_type' => 'attachment',
            'nopaging'=>true,
            'orderby'          => 'date',
            'order'            => 'ASC',
            'meta_query'=>array(
                "relation"=>'OR',
                array(
                    'key' => OVM_PO_PUBLISH_CREDIT,
                    'compare' => '=',
                    'value' => '1')
                )
        );
        $image_credits = get_posts($args);
    }
    else { //Spezielle Auswahl für die Bilder einer Seite
        $siteurl = get_site_url();

        for ($i=0; $i<count($src);$i++){
            $src[$i] = substr($src[$i],0,strlen($src[$i])-4);
            $src[$i] = str_replace($siteurl,'',$src[$i]);
            //$src[$i] = "'".$src[$i]."'";   //add ' to the strings for creating the query
        }
        $guid_string = implode(",",$src);

        //suchen der optimierten guid's in den optimierten matches
        $guid_query = "SELECT distinct ID,post_type
                           FROM $wpdb->posts
                           WHERE instr('{$guid_string}', replace(left(guid,length(guid)-4),'{$siteurl}','')) > 0 and post_type='attachment'";
        $image_credits = $wpdb->get_results($guid_query);

        //jetzt noch die image_credits entfernen, die gar nicht auf einer Seite , sondern nur im Impressum angezeigt werden sollen.
        $output_credits = array();
        foreach($image_credits as $credit) {
            $meta = get_post_meta($credit->ID,OVM_PO_PICTUREDATA);
            if ($meta[0]['show_on_page']==1)
                $output_credits[]=$credit;
        }
        if (count($output_credits)==0) return;  //exit, wenn nichts zum Ausgeben da ist!!!
        $image_credits = $output_credits; //Die credits wieder für die weiterverarbeitung in die richte Variable stellen
        unset($output_credits);
    }

    $promotion_ausgabe = get_option(OVM_PO_OUTPUT_OPTIONS_TAB);

    $gbo = (@$promotion_ausgabe['group_by_owner'] and $this->is_premium()) ? true:false;   //group by owner = $gbo
    if ($promotion_ausgabe===false) { //es wurde noch nichts angelegt
        $this->plugin_init();
        $promotion_ausgabe = get_option(OVM_PO_OUTPUT_OPTIONS_TAB);
    }// Anlege
    $ovm_po_css = @$promotion_ausgabe['ovm_po_css'];  //check wether option is set

    if (!isset($ovm_po_css)) {
        eval('$ovm_po_css = "' . $this->ovm_get_template('default.css') . '";');    //default value if not yet defined
    }

    $h = "\n<style type=\"text/css\">\n{$ovm_po_css}\n</style>";
    $h.="\n<table id=\"ovm_po_image_credits\">\n"; //html zur Ausgabe erzeugen

    if (count($image_credits)>0) {
        $i = 0;
        $p = array();
        foreach ($image_credits as $credit) {
            $credit_data                = get_post_meta($credit->ID,OVM_PO_PICTUREDATA);
            $p[$i]["ID"]                = $credit->ID;
            $p[$i]['bemerkung_online']  = @$credit_data[0]['bemerkung_online'];
            $p[$i]['uri']               = @$credit_data[0]['uri'];
            $p[$i]['author']            = @$credit_data[0]['author'];
            $p[$i]['portal']            = @$credit_data[0]['portal'];
            $p[$i]['kauf']              = @$credit_data[0]['kauf'];
            $p[$i]['src_thumb']          = wp_get_attachment_thumb_url($credit->ID);
            $p[$i]['src_image']          = wp_get_attachment_url($credit->ID);
            $p[$i]['credit_lizenz']     = get_post_meta($credit->ID,OVM_PO_PICTUREDATA_LIZENZ);
            $i++;
        }
        if ($gbo===true) {//gbo = group by owner...
            usort($p, array($this, "sort_credit_array"));   //sortieren in Reihenfolge Autor
            $old_author = chr(1);   //init für den Gruppenwechsel nach Author
        }

        foreach($p as $credit) {
        if ($gbo) {
            if ($old_author != $credit['author']) {
                $h .= "<tr><td colspan=\"2\"><a href=\"{$credit['uri']}\" target=\"_blank\">&copy; {$credit['author']}</a></td></tr>";
                $old_author = $credit['author'];
            }
        }
        $h .= "<tr><td class=\"ovm_po_credit_image\"><a href=\"{$credit["src_image"]}\" target=\"blank\"><img src=\"{$credit['src_thumb']}\"></a></td>";
        $h .= "<td class=\"ovm_po_credit_text\"><a href=\"{$credit['uri']}\" target = \"_blank\">&copy; {$credit['author']} {$credit['portal']} </a><br>{$credit['bemerkung_online']}</td></tr>";
        }

    }   //end von count($image_credits > 0
    else {
        $h .="<tr><td>".__("No Picture-Credits to show",PO)."</td></tr>";
    }

    $h.="</table>\n";//end table width image_credits
    if ($all===true) {
        if (!isset($promotion_ausgabe['promotion_text_line'])) $this->plugin_init();

        $prom_ausgabe = "<div id=\"ovm_po_credits_info\">{$promotion_ausgabe['promotion_text_line']} {$this->promotion_link}</div>";
        $aff_id = $this->affiliate_info['ovm_po_affiliate_id'];
        if (@$promotion_ausgabe["digistore_id"] > '') $aff_id = @$promotion_ausgabe["digistore_id"];
        $prom_ausgabe = str_replace("[aff_id]", $aff_id, $prom_ausgabe);

        switch ($promotion_ausgabe["promotion_position"]) {
            case 1:
                $h = $prom_ausgabe . $h;
                break;
            case 2:
                $h = $h . $prom_ausgabe;
                break;
        }
    }
    $h = '<div id="ovm_po_main_div" style="clear:both;">'.$h.'</div>';
return $h;   //Rückgabe der html-inhalte
}//end function


/*      show_lizenzinformationen($atts)
 *      Holt die Inhalte der Attachments zur Ausgabe über den Shortcode
 *      @since   1.1
 */

 public function show_lizenzinformationen($atts)
    {
        switch($atts[0])
        {
            case "liste":
                $h=$this->get_picture_credits(null,true);
                break;
        }
        return $h;
    }
/*      add_image_attachment_fields_to_edit
 *      Erzeugt das Array mit den zusätzlichen Feldinformatinoen für die Ausgabe der Maske im  Pflegebereich/Dashboard
      *  @since   1.1
 */
    public function add_image_attachment_fields_to_edit($form_fields, $post)
    {
        if (!isset($_GET['action'])) {
            return $form_fields;}
        else {
            $h = "
        <style type=\"text/css\">
            .compat-attachment-fields th, .compat-attachment-fields td
            {vertical-align:top;
                border:1px solid #cccccc}
        </style>";
            $ovm_picturedata = maybe_unserialize(get_post_meta($post->ID, OVM_PO_PICTUREDATA, true));
            $ovm_picturedata_lizenz = get_post_meta($post->ID, OVM_PO_PICTUREDATA_LIZENZ, true);
            $ovm_picturedata_publish_credit = get_post_meta($post->ID, OVM_PO_PUBLISH_CREDIT, true);

            if (!isset($ovm_picturedata['author'])) {
                $ovm_picturedata['author'] = '';
            }
            if (!isset($ovm_picturedata['portal'])) {
                $ovm_picturedata['portal'] = '';
            }
            if (!isset($ovm_picturedata['uri'])) {
                $ovm_picturedata['uri'] = '';
            }
            if (!isset($ovm_picturedata['kauf'])) {
                $ovm_picturedata['kauf'] = '';
            }
            if (!isset($ovm_picturedata['bemerkung'])) {
                $ovm_picturedata['bemerkung'] = '';
            }
            if (!isset($ovm_picturedata['bemerkung_online'])) {
                $ovm_picturedata['bemerkung_online'] = '';
            }
            if (!isset($ovm_picturedata['show_on_page'])) {
                $ovm_picturedata['show_on_page'] = 0;
            }

            if (isset($ovm_picturedata['publish_credit']))
                if ((int)$ovm_picturedata['publish_credit']==1)
                   $checked = ' checked';
                   else
                   $checked ='';
            else
                $checked = "";
            $form_fields['publish_credit'] = array(
                'label' => __('Publish Credit', PO),
                'input' => 'html',
                'value' => 1,
                'html' => "<input type=\"checkbox\" name=\"attachments[{$post->ID}][publish_credit]\" id=\"attachments[{$post->ID}][publish_credit]\"   value=\"1\" {$checked}/><br />");

            $form_fields["author"] = array(
                "label" => __("author", PO),
                "input" => "text", // this is default if "input" is omitted
                "value" => $ovm_picturedata['author'],
                "helps" => __("Photographer", PO),
                'application' => 'image',
                'exclusions' => array('audio', 'video'),
                'required' => false,
                'error_text' => 'Requested, please fill out'
            );

            $form_fields["lizenz"] = array(
                "label" => __("License-Key", PO),
                "input" => "text",
                "value" => $ovm_picturedata_lizenz,
                "helps" => __("License-Key identifies your license - important!", PO)
            );
            $form_fields["portal"] = array(
                "label" => __("Portal", PO),
                "input" => "text",
                "value" => $ovm_picturedata['portal'],
                "helps" => __("Download-Portal (for instance Fotolia)", PO)
            );
            $form_fields["uri"] = array(
                "label" => __("URI", PO),
                "input" => "text",
                "value" => $ovm_picturedata['uri'],
                "helps" => __("(Link to the Authors-Site)", PO)
            );

            $form_fields["kauf"] = array(
                "label" => __("Date of Buy", PO),
                "input" => "text",
                "value" => $ovm_picturedata['kauf'],
                "helps" => __("(The Date, on which you bought the image)", PO)
            );
            $form_fields["bemerkung"] = array(
                "label" => __("Internal Comment", PO),
                "input" => "textarea",
                "value" => $ovm_picturedata['bemerkung'],
                "helps" => __("(Internal Comments because of the rights or other Infos)", PO)
            );

            $form_fields["bemerkung_online"] = array(
                "label" => __("Online Comments", PO),
                "input" => "textarea",
                "value" => $ovm_picturedata['bemerkung_online'],
                "helps" => __("(helpful comments depending on which site the image is published or anything else...)", PO)
            );

            if ($this->is_premium()) {
                $disabled = '';
                $helps = __("Activate, if the Picture-Credit has to be shown on the same site as the Image ", PO);
            } else {
                $disabled = ' disabled="disabled"';
                $helps = $this->only_premium_info;
            }

            if ("1" == $ovm_picturedata['show_on_page']) {
                $ovm_picturedata_checked = ' checked = "checked"';
            } else {
                $ovm_picturedata_checked = '';
            }

            $form_fields["show_on_page"] = array(
                "label" => __("Show Picture Credit on bottom of Page", PO),
                "input" => "html",
                "value" => $ovm_picturedata['show_on_page'],
                "html" => "<input type=\"checkbox\" name=\"attachments[{$post->ID}][show_on_page]\" id=\"attachments[{$post->ID}][show_on_page]\" value=\"1\" {$ovm_picturedata_checked} {$disabled}/>",
                "helps" => $helps
            );
            return $form_fields;
        }
    }

    public function add_image_attachment_fields_to_save($post, $attachment)
    {
        $ovm_picturedata = array();
        $ovm_picturedata['author'] = $attachment['author'];
        $ovm_picturedata['portal'] = $attachment['portal'];
        $ovm_picturedata['uri'] = $attachment['uri'];
        $ovm_picturedata['kauf'] = $attachment['kauf'];
        $ovm_picturedata['bemerkung']=$attachment['bemerkung'];
        $ovm_picturedata['bemerkung_online']=$attachment['bemerkung_online'];
        $ovm_picturedata['show_on_page']=(int)$attachment['show_on_page'];
        $ovm_picturedata['publish_credit']=(int)$attachment['publish_credit'];
        update_post_meta($post['ID'], OVM_PO_PICTUREDATA_LIZENZ, $attachment['lizenz']);
        update_post_meta($post['ID'], OVM_PO_PUBLISH_CREDIT, $attachment['publish_credit']);
        update_post_meta($post['ID'], OVM_PO_PICTUREDATA, $ovm_picturedata);
        return $post;
    }


    /**
     * @param $uri : uri to read
     * @return mixed in json-format
     */
    private function get_uri($uri,$json_encode=true)
    {
        $args = array(
            'timeout' => 5,
            'redirection' => 5,
            'httpversion' => '1.0',
            'user-agent' => 'WordPress/' . '; ' . get_bloginfo('url'),
            'blocking' => true,
            'headers' => array(),
            'cookies' => array(),
            'body' => null,
            'compress' => false,
            'decompress' => true,
            'sslverify' => true,
            'stream' => false,
            'filename' => null
        );
        if (strpos($uri, "?") === false)
            $uri .= "?";
        else
            $uri .= "&";


        if (!function_exists('get_plugin_data')) require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        $this->plugin_data = get_plugin_data(__FILE__);
        $p = ($this->is_premium()) ? "true":"false";

        $uri .= "version=" . $this->plugin_data['Version'] . "&p=" . $this->blogurl."&premium=".$p."&lang=".$this->blog_language;
        $response = wp_remote_get($uri, $args);
        if (is_wp_error($response)) {
            $message = "Fehler bei wp_remote_get, uri: " . $uri . "\n";
            $message .= "\nError-Message:" . $response->errors["http_request_failed"][0];
            mail(PO_EMAIL, "wp_remote_get_error", $message);
            return false;   //error getting Info-Uris - no problem - be silent
        }
        if ($json_encode === true) {
            $retval = json_decode($response['body']);  //return error-free response
        } else {
            $retval = $response['body'];
        }
        return $retval;
    }

    /**  css_for_mediatetails()
     *   Adds CSS-Infos for the mediacenter-detail-pages
     *
     */
    public function css_for_mediadetails(){
        $h = '<style type="text/css" media="screen">.compat-attachment-fields th { text-align:left;vertical-align:top }</style>';
        echo $h;
        return;
    }

    /*  ovm_get_template($template)
     *  shows html-templates with vars by a given Template
     *  only available within the plugin
     *
     */

    public function ovm_get_template($template, $cache = 1) // $cash: bei 1 ins templatecashe, bei 0 NICHT!
    {   global $templatecache, $ovmnl;
        if (! isset ( $templatecache [$template] )) {
            $filename = $this->plugins_path."tpl/".$template;
            if (file_exists($filename)) {
                $templatefile = str_replace ( "\"", "\\\"", implode ( file ( $filename ), '' ) );
            } else {
                $templatefile = '<!-- TEMPLATE NOT FOUND: ' . $filename . ' -->';
                die ( $template . " not found !" );
            }
            $templatefile = preg_replace ( "'<if ([^>]*?)>(.*?)</if>'si", "\".( (\\1) ? \"\\2\" : \"\").\"", $templatefile );
            $retval = $templatefile;
            if ($cache == 1) {
                $templatecache [$template] = $retval;
            }
        } else {
            $retval = $templatecache [$template];
        }
        return $retval;
    }


    public function media_meta_boxen()
    {
        add_meta_box("rate_us", __("Rate Us","picture-organizer"), array($this, 'rate_us'), 'attachment', "side");
        if (!$this->is_premium()) {
            add_meta_box("premium-commercial", "Picture-Organizer-Premium", array($this, 'show_premium_version'), 'attachment', "side");
        }
    }

    public function rate_us() {
        #$rate_link = "http://wordpress.org/support/view/plugin-reviews/picture-organizer";
        $h = "<input type=\"button\" value=\"Rate us\">";
        echo($h);
    }



    public function show_premium_version($show=true) {
        switch ($this->blog_language) {
            case "de":
                $template = "premium_commercial_de.html";
                break;
            default:
                $template = "premium_commercial_en.html";
        }
        eval('$commercial = "' . $this->ovm_get_template($template).'";');
        if ($show==true)
            echo($commercial);
        else
            return ($commercial);
    }
    }//end class

$OVM_Picture_organizer = new OVM_Picture_organizer();

if (true === $OVM_Picture_organizer->is_premium()) {
  $includeresult = @include($OVM_Picture_organizer->plugins_path."inc/ovm_po_premium.php");
}
else {
    //define("OVM_PO_PREMIUM_VERSION","");
}
