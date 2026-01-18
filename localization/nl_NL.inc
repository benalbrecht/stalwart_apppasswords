<?php
/**
 * Stalwart App Passwords - Dutch localization
 */

$labels = array();

// Menu/Tab
$labels['apppasswords'] = 'App-wachtwoorden';

// Form labels
$labels['name'] = 'Naam';
$labels['name_placeholder'] = 'bv. Thunderbird, iPhone Mail';

// Buttons
$labels['create_button'] = 'Wachtwoord aanmaken';
$labels['delete'] = 'Verwijderen';
$labels['cancel'] = 'Annuleren';
$labels['save'] = 'Opslaan';
$labels['done'] = 'Klaar';
$labels['copy_password'] = 'Kopiëren naar klembord';

// Section titles
$labels['create_password'] = 'App-wachtwoord aanmaken';
$labels['new_password_legend'] = 'Nieuw App-wachtwoord';
$labels['new_password_description'] = 'Met app-wachtwoorden kunt u inloggen op uw e-mail vanuit apps die geen tweefactorauthenticatie ondersteunen. Geef een beschrijvende naam op zodat u weet waarvoor dit wachtwoord wordt gebruikt.';
$labels['existing_passwords'] = 'Bestaande App-wachtwoorden';
$labels['password_created_title'] = 'Wachtwoord aangemaakt';

// Table columns
$labels['col_name'] = 'Naam';
$labels['col_created'] = 'Aangemaakt';

// Messages
$labels['no_passwords'] = 'Nog geen app-wachtwoorden geconfigureerd.';
$labels['no_passwords_hint'] = 'Klik op Aanmaken om er een toe te voegen.';
$labels['password_created_for'] = 'Uw nieuwe app-wachtwoord voor "%s":';
$labels['password_warning'] = 'Zorg dat u dit wachtwoord nu kopieert. U kunt het hierna niet meer zien!';
$labels['copied'] = 'Wachtwoord gekopieerd naar klembord!';
$labels['confirm_delete'] = 'Weet u zeker dat u dit app-wachtwoord wilt verwijderen? Apps die dit wachtwoord gebruiken, hebben daarna geen toegang meer tot uw account.';

// Status messages
$messages = array();
$messages['password_deleted'] = 'App-wachtwoord succesvol verwijderd.';
$messages['error_name_required'] = 'Voer een naam in voor het app-wachtwoord.';
$messages['error_creating'] = 'Aanmaken van app-wachtwoord mislukt. Probeer het opnieuw.';
$messages['error_deleting'] = 'Verwijderen van app-wachtwoord mislukt. Probeer het opnieuw.';
$messages['error_no_token'] = 'Authenticatiefout. Log alstublieft uit en opnieuw in.';
$messages['error_connection'] = 'Kan geen verbinding maken met de mailserver. Probeer het later opnieuw.';
$messages['error_api'] = 'Er is een fout opgetreden bij de communicatie met de mailserver.';