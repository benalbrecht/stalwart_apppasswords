<?php
/**
 * Stalwart App Passwords - English (US) localization
 */

$labels = array();

// Menu/Tab
$labels['apppasswords'] = 'App Passwords';

// Form labels
$labels['name'] = 'Name';
$labels['name_placeholder'] = 'e.g., Thunderbird, iPhone Mail';
$labels['usage'] = 'Usage';
$labels['usage_all'] = 'All Access';
$labels['usage_email'] = 'Email';
$labels['usage_calendar'] = 'Calendar';
$labels['usage_contacts'] = 'Contacts';

// Buttons
$labels['create_button'] = 'Create Password';
$labels['delete'] = 'Delete';
$labels['cancel'] = 'Cancel';
$labels['save'] = 'Save';
$labels['done'] = 'Done';
$labels['copy_password'] = 'Copy to Clipboard';

// Section titles
$labels['create_password'] = 'Create App Password';
$labels['new_password_legend'] = 'New App Password';
$labels['new_password_description'] = 'App passwords let you sign in from apps that don\'t support browser-based sign-in. Enter a name for the app and choose what it needs access to.';
$labels['existing_passwords'] = 'Existing App Passwords';
$labels['password_created_title'] = 'Password Created';

// Table columns
$labels['col_name'] = 'Name';
$labels['col_usage'] = 'Usage';
$labels['col_created'] = 'Created';

// Messages
$labels['no_passwords'] = 'No app passwords configured yet.';
$labels['no_passwords_hint'] = 'Click Create to add one.';
$labels['password_created_for'] = 'Your new app password for "%s":';
$labels['password_warning'] = 'Make sure to copy this password now. You won\'t be able to see it again!';
$labels['copied'] = 'Password copied to clipboard!';
$labels['confirm_delete'] = 'Are you sure you want to delete this app password? Any apps using it will no longer be able to access your account.';

// Status messages
$messages = array();
$messages['password_deleted'] = 'App password deleted successfully.';
$messages['error_name_required'] = 'Please enter a name for the app password.';
$messages['error_creating'] = 'Failed to create app password. Please try again.';
$messages['error_deleting'] = 'Failed to delete app password. Please try again.';
$messages['error_no_token'] = 'Authentication error. Please log out and log in again.';
$messages['error_connection'] = 'Could not connect to the mail server. Please try again later.';
$messages['error_api'] = 'An error occurred while communicating with the mail server.';
