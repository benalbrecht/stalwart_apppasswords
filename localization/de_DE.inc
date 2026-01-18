<?php
/**
 * Stalwart App Passwords - German localization
 */

$labels = array();

// Menu/Tab
$labels['apppasswords'] = 'App-Passwörter';

// Form labels
$labels['name'] = 'Name';
$labels['name_placeholder'] = 'z.B. Thunderbird, iPhone Mail';

// Buttons
$labels['create_button'] = 'Passwort erstellen';
$labels['delete'] = 'Löschen';
$labels['cancel'] = 'Abbrechen';
$labels['save'] = 'Speichern';
$labels['done'] = 'Fertig';
$labels['copy_password'] = 'In die Zwischenablage kopieren';

// Section titles
$labels['create_password'] = 'App-Passwort erstellen';
$labels['new_password_legend'] = 'Neues App-Passwort';
$labels['new_password_description'] = 'Mit App-Passwörtern können Sie sich von Apps aus anmelden, die keine Zwei-Faktor-Authentifizierung unterstützen. Geben Sie einen beschreibenden Namen ein, um sich daran zu erinnern, wo dieses Passwort verwendet wird.';
$labels['existing_passwords'] = 'Vorhandene App-Passwörter';
$labels['password_created_title'] = 'Passwort erstellt';

// Table columns
$labels['col_name'] = 'Name';
$labels['col_created'] = 'Erstellt';

// Messages
$labels['no_passwords'] = 'Noch keine App-Passwörter konfiguriert.';
$labels['no_passwords_hint'] = 'Klicken Sie auf Erstellen, um eines hinzuzufügen.';
$labels['password_created_for'] = 'Ihr neues App-Passwort für "%s":';
$labels['password_warning'] = 'Kopieren Sie dieses Passwort jetzt. Es wird später nicht mehr angezeigt!';
$labels['copied'] = 'Passwort kopiert!';
$labels['confirm_delete'] = 'Sind Sie sicher, dass Sie dieses App-Passwort löschen möchten? Apps, die es verwenden, haben danach keinen Zugriff mehr auf Ihr Konto.';

// Status messages
$messages = array();
$messages['password_deleted'] = 'App-Passwort erfolgreich gelöscht.';
$messages['error_name_required'] = 'Bitte geben Sie einen Namen für das App-Passwort ein.';
$messages['error_creating'] = 'Fehler beim Erstellen des App-Passworts. Bitte versuchen Sie es erneut.';
$messages['error_deleting'] = 'Fehler beim Löschen des App-Passworts. Bitte versuchen Sie es erneut.';
$messages['error_no_token'] = 'Authentifizierungsfehler. Bitte melden Sie sich ab und wieder an.';
$messages['error_connection'] = 'Verbindung zum Mailserver fehlgeschlagen. Bitte versuchen Sie es später erneut.';
$messages['error_api'] = 'Bei der Kommunikation mit dem Mailserver ist ein Fehler aufgetreten.';