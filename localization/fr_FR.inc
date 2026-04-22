<?php
/**
 * Stalwart App Passwords - French localization
 */

$labels = array();

// Menu/Tab
$labels['apppasswords'] = 'Mots de passe d\'application';

// Form labels
$labels['name'] = 'Nom';
$labels['name_placeholder'] = 'ex: Thunderbird, iPhone Mail';
$labels['usage'] = 'Utilisation';
$labels['usage_all'] = 'Accès complet';
$labels['usage_email'] = 'E-mail';
$labels['usage_calendar'] = 'Calendrier';
$labels['usage_contacts'] = 'Contacts';

// Buttons
$labels['create_button'] = 'Créer un mot de passe';
$labels['delete'] = 'Supprimer';
$labels['cancel'] = 'Annuler';
$labels['save'] = 'Enregistrer';
$labels['done'] = 'Terminé';
$labels['copy_password'] = 'Copier dans le presse-papier';

// Section titles
$labels['create_password'] = 'Créer un mot de passe d\'application';
$labels['new_password_legend'] = 'Nouveau mot de passe d\'application';
$labels['new_password_description'] = 'Les mots de passe d\'application vous permettent de vous connecter depuis des applications ne prenant pas en charge la connexion via le navigateur. Saisissez un nom pour l\'application et choisissez à quoi elle doit avoir accès.';
$labels['existing_passwords'] = 'Mots de passe existants';
$labels['password_created_title'] = 'Mot de passe créé';

// Table columns
$labels['col_name'] = 'Nom';
$labels['col_usage'] = 'Utilisation';
$labels['col_created'] = 'Créé le';

// Messages
$labels['no_passwords'] = 'Aucun mot de passe d\'application configuré.';
$labels['no_passwords_hint'] = 'Cliquez sur Créer pour en ajouter un.';
$labels['password_created_for'] = 'Votre nouveau mot de passe pour "%s" :';
$labels['password_warning'] = 'Assurez-vous de copier ce mot de passe maintenant. Vous ne pourrez plus le voir ensuite !';
$labels['copied'] = 'Mot de passe copié !';
$labels['confirm_delete'] = 'Êtes-vous sûr de vouloir supprimer ce mot de passe ? Les applications l\'utilisant ne pourront plus accéder à votre compte.';

// Status messages
$messages = array();
$messages['password_deleted'] = 'Mot de passe supprimé avec succès.';
$messages['error_name_required'] = 'Veuillez saisir un nom pour le mot de passe.';
$messages['error_creating'] = 'Échec de la création du mot de passe. Veuillez réessayer.';
$messages['error_deleting'] = 'Échec de la suppression du mot de passe. Veuillez réessayer.';
$messages['error_no_token'] = 'Erreur d\'authentification. Veuillez vous déconnecter et vous reconnecter.';
$messages['error_connection'] = 'Impossible de se connecter au serveur de messagerie. Veuillez réessayer plus tard.';
$messages['error_api'] = 'Une erreur est survenue lors de la communication avec le serveur.';
