<?php
defined( 'ABSPATH' ) || exit;
global $wpdb;
/*  Uninstall-Routine des Plugin
 *  Wenn in den Optionen so definiert, werden beim Uninstall ALLE Daten, auch die Bewegungs-Lizenzdaten der einzelnen
 *  Attachments gelöscht
 *  @since   4.2.2
 */

    if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        exit();
    }

    $delete_all = get_option("ovm_po_uninstall_delete");  //ermittlung der Einstellung, ob gelöscht werden soll..
    if ((int)$delete_all==1)
    {//delete all license-data form wpdb->postmeta, and options from wp_options
        $wpdb->query("delete from $wpdb->postmeta where meta_key like 'ovm_picturedata%'");
        $wpdb->query("delete from $wpdb->options where option_name like 'ovm_po%'");
    }
