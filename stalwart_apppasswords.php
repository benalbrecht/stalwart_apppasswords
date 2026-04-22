<?php
/**
 * Stalwart App Passwords
 *
 * Plugin to manage Stalwart Mail Server application passwords from Roundcube.
 * Uses OAuth token from session to authenticate with Stalwart API.
 *
 * @version 1.0.0
 * @license GPL-3.0-or-later
 * @author Lauris van Rijn
 */
class stalwart_apppasswords extends rcube_plugin
{
    public $task = 'settings';

    private $rcmail;

    function init()
    {
        $this->rcmail = rcmail::get_instance();
        $this->load_config();
        $this->add_texts('localization/');
        $this->include_script('stalwart_apppasswords.js');
        $this->include_stylesheet($this->local_skin_path() . '/styles.css');  // Add this line

        // Add to settings actions (tabs)
        $this->add_hook('settings_actions', array($this, 'settings_actions'));

        // Register handlers
        $this->register_action('plugin.stalwart_apppasswords', array($this, 'action_list'));
        $this->register_action('plugin.stalwart_apppasswords-add', array($this, 'action_add'));
        $this->register_action('plugin.stalwart_apppasswords-save', array($this, 'action_save'));
        $this->register_action('plugin.stalwart_apppasswords-delete', array($this, 'action_delete'));
        $this->register_action('plugin.stalwart_apppasswords-created', array($this, 'action_created'));
    }

    /**
     * Add App Passwords to settings tabs
     */
    function settings_actions($args)
    {
        $args['actions'][] = array(
            'action' => 'plugin.stalwart_apppasswords',
            'class'  => 'apppasswords',
            'label'  => 'apppasswords',
            'domain' => 'stalwart_apppasswords',
            'title'  => 'apppasswords',
        );
        return $args;
    }

    /**
     * Check if custom template exists for current skin
     */
    private function has_custom_template($template)
    {
        $skin = $this->rcmail->config->get('skin', 'elastic');
        $path = $this->home . "/skins/{$skin}/templates/{$template}.html";
        return file_exists($path);
    }

    /**
     * List app passwords
     */
    function action_list()
    {
        $this->rcmail->output->set_pagetitle($this->gettext('apppasswords'));
        $this->rcmail->output->include_script('list.js');

        $this->rcmail->output->add_handler('apppasswordslist', array($this, 'render_passwords_list'));
        $this->rcmail->output->set_env('contentframe', 'apppasswords-frame');

        if ($this->has_custom_template('apppasswords')) {
            $this->rcmail->output->send('stalwart_apppasswords.apppasswords');
        } else {
            $this->register_handler('plugin.body', array($this, 'render_fallback_list'));
            $this->rcmail->output->send('plugin');
        }
    }

    /**
     * Show add form
     */
    function action_add()
    {
        $this->rcmail->output->set_pagetitle($this->gettext('create_password'));
        $this->rcmail->output->add_handler('apppasswordform', array($this, 'render_password_form'));

        if ($this->has_custom_template('apppasswordedit')) {
            $this->rcmail->output->send('stalwart_apppasswords.apppasswordedit');
        } else {
            $this->register_handler('plugin.body', array($this, 'render_fallback_add'));
            $this->rcmail->output->send('plugin');
        }
    }

    /**
     * Save new app password
     */
    function action_save()
    {
        $name = trim(rcube_utils::get_input_value('_name', rcube_utils::INPUT_POST));

        if (empty($name)) {
            $this->rcmail->output->show_message($this->gettext('error_name_required'), 'error');
            $this->action_add();
            return;
        }

        // Sanitize name
        $name = preg_replace('/[^\w\s\-_.@]/', '', $name);
        $name = mb_substr($name, 0, 64);

        $usage = rcube_utils::get_input_value('_usage', rcube_utils::INPUT_POST) ?: 'all';
        $result = $this->jmap_create_app_password($name, $usage);

        if ($result['success']) {
            $_SESSION['stalwart_apppassword'] = $result['secret'];
            $_SESSION['stalwart_apppassword_name'] = $name;
            $this->rcmail->output->redirect(array('_action' => 'plugin.stalwart_apppasswords-created'));
        } else {
            $this->rcmail->output->show_message($result['error'] ?? $this->gettext('error_creating'), 'error');
            $this->action_add();
        }
    }

    /**
     * Show created password
     */
    function action_created()
    {
        $this->rcmail->output->set_pagetitle($this->gettext('password_created_title'));
        $this->rcmail->output->add_handler('apppasswordresult', array($this, 'render_password_result'));

        if ($this->has_custom_template('apppasswordcreated')) {
            $this->rcmail->output->send('stalwart_apppasswords.apppasswordcreated');
        } else {
            $this->register_handler('plugin.body', array($this, 'render_fallback_created'));
            $this->rcmail->output->send('plugin');
        }
    }

    /**
     * Delete app password (AJAX)
     */
    function action_delete()
    {
        $id = rcube_utils::get_input_value('_id', rcube_utils::INPUT_POST);

        if (!empty($id)) {
            $result = $this->jmap_delete_app_password($id);
            if ($result['success']) {
                $this->rcmail->output->show_message($this->gettext('password_deleted'), 'confirmation');
                $this->rcmail->output->command('plugin.apppassword_deleted', array('id' => $id));
            } else {
                $this->rcmail->output->show_message($result['error'] ?? $this->gettext('error_deleting'), 'error');
            }
        } else {
            $this->rcmail->output->show_message($this->gettext('error_deleting'), 'error');
        }

        $this->rcmail->output->send();
    }

    // ==================== RENDERERS (for custom templates) ====================

    /**
     * Render passwords list table (used by custom template)
     */
    function render_passwords_list($attrib)
    {
        $attrib += array('id' => 'apppasswords-table');
        $passwords = $this->jmap_get_app_passwords();

        $table = new html_table(array(
            'id' => $attrib['id'],
            'class' => 'listing iconized',
            'role' => 'listbox',
        ));

        if (is_array($passwords) && !empty($passwords)) {
            foreach ($passwords as $pw) {
                $table->add_row(array('id' => 'rcmrow' . $pw['id']));
                $table->add(array('class' => 'name'), rcube::Q($pw['description']));
                $table->add(array('class' => 'usage'), rcube::Q($this->get_usage_label($pw)));
            }
        }

        $this->rcmail->output->set_env('apppasswords', $passwords ?: array());
        $this->rcmail->output->add_gui_object('apppasswordslist', $attrib['id']);

        return $table->show();
    }

    /**
     * Render password form (used by custom template)
     */
    function render_password_form($attrib)
    {
        $attrib += array('id' => 'apppassword-form');

        $table = new html_table(array('cols' => 2, 'class' => 'propform'));

        $input = new html_inputfield(array(
            'name' => '_name',
            'id' => 'apppassword-name',
            'size' => 40,
            'class' => 'form-control',
            'placeholder' => $this->gettext('name_placeholder'),
        ));

        $table->add('title', html::label('apppassword-name', rcube::Q($this->gettext('name'))));
        $table->add(null, $input->show());

        $table->add('title', html::label('apppassword-usage', rcube::Q($this->gettext('usage'))));
        $table->add(null, $this->render_usage_select()->show('email'));

        $out = $this->rcmail->output->form_tag(array(
            'id' => $attrib['id'],
            'method' => 'post',
            'action' => './?_task=settings&_action=plugin.stalwart_apppasswords-save',
        ), $table->show());

        $this->rcmail->output->add_gui_object('editform', $attrib['id']);

        return $out;
    }

    /**
     * Render password result (used by custom template)
     */
    function render_password_result($attrib)
    {
        $password = $_SESSION['stalwart_apppassword'] ?? '';
        $name = $_SESSION['stalwart_apppassword_name'] ?? '';
        unset($_SESSION['stalwart_apppassword'], $_SESSION['stalwart_apppassword_name']);

        $out = html::div('boxwarning', rcube::Q($this->gettext('password_warning')));

        $out .= html::p(null, sprintf(
            rcube::Q($this->gettext('password_created_for')),
            rcube::Q($name)
        ));

        $out .= html::div(array('style' => 'text-align:center;margin:20px 0'),
            html::tag('code', array(
                'id' => 'apppassword-value',
                'style' => 'font-size:1.4em;letter-spacing:2px;padding:15px 20px;background:#f5f5f5;display:inline-block;border-radius:4px;user-select:all;'
            ), $password)
        );

        $out .= html::div(array('style' => 'text-align:center'),
            html::tag('button', array(
                'type' => 'button',
                'class' => 'btn btn-secondary button',
                'onclick' => 'navigator.clipboard.writeText(document.getElementById("apppassword-value").textContent).then(function(){rcmail.display_message("' . rcube::JQ($this->gettext('copied')) . '","confirmation")})'
            ), rcube::Q($this->gettext('copy_password')))
        );

        return $out;
    }

    // ==================== FALLBACK RENDERERS (for plugin.html) ====================

    /**
     * Fallback: render full list page
     */
    function render_fallback_list()
    {
        $out = '';

        // --- SECTION 1: CREATE NEW PASSWORD ---
        
        $out .= html::tag('h2', 'boxtitle', rcube::Q($this->gettext('new_password_legend')));
        
        $form_content = html::p('hint', rcube::Q($this->gettext('new_password_description')));

        $table = new html_table(array('cols' => 2, 'class' => 'propform'));

        $input = new html_inputfield(array(
            'name' => '_name',
            'id' => 'apppassword-name-fallback',
            'size' => 40,
            'placeholder' => $this->gettext('name_placeholder'),
        ));

        $table->add('title', html::label('apppassword-name-fallback', rcube::Q($this->gettext('name'))));
        $table->add(null, $input->show());

        $table->add('title', html::label('apppassword-usage-fallback', rcube::Q($this->gettext('usage'))));
        $table->add(null, $this->render_usage_select('apppassword-usage-fallback')->show('email'));

        // Button row
        $table->add(null, '&nbsp;');
        $table->add(null, html::tag('input', array(
            'type' => 'submit',
            'class' => 'button mainaction', // 'mainaction' makes it the primary (blue) button
            'value' => $this->gettext('create_button'),
        )));

        $form_content .= $table->show();

        $out .= $this->rcmail->output->form_tag(array(
            'id' => 'apppassword-create-form',
            'method' => 'post',
            'action' => './?_task=settings&_action=plugin.stalwart_apppasswords-save',
        ), 
        html::div('boxcontent', $form_content)
        );

        // --- SECTION 2: EXISTING PASSWORDS ---

        $out .= html::tag('h2', 'boxtitle', rcube::Q($this->gettext('existing_passwords')));

        $passwords = $this->jmap_get_app_passwords();

        $table = new html_table(array(
            'class' => 'records-table listing',
            'id' => 'apppasswords-table-fallback',
            'cellspacing' => '0',
            'cols' => 4,
        ));

        $table->add_header('name', rcube::Q($this->gettext('col_name')));
        $table->add_header('usage', rcube::Q($this->gettext('col_usage')));
        $table->add_header('created', rcube::Q($this->gettext('col_created')));
        $table->add_header('action', '&nbsp;');

        if (empty($passwords)) {
            $table->add(array('colspan' => 4, 'class' => 'hint'), rcube::Q($this->gettext('no_passwords')));
        } else {
            foreach ($passwords as $pw) {
                $table->add_row(array('id' => 'rcmrow' . $pw['id']));

                $delete_btn = html::tag('input', array(
                    'type' => 'button',
                    'class' => 'button',
                    'onclick' => 'return rcmail.command("plugin.stalwart_apppasswords-delete-fallback", "' . rcube::JQ($pw['id']) . '", this)',
                    'value' => $this->gettext('delete'),
                    'title' => $this->gettext('delete')
                ));

                $created = '';
                if (!empty($pw['createdAt'])) {
                    try {
                        $date = new DateTime($pw['createdAt']);
                        $created = $date->format('Y-m-d H:i');
                    } catch (Exception $e) {
                        $created = $pw['createdAt'];
                    }
                }

                $table->add('name', rcube::Q($pw['description']));
                $table->add('usage', rcube::Q($this->get_usage_label($pw)));
                $table->add('created', $created);
                $table->add('action', $delete_btn);
            }
        }

        // Store passwords for JS
        $this->rcmail->output->set_env('apppasswords', $passwords ?: array());

        $out .= html::div('boxcontent', $table->show());

        return $out;
    }

    /**
     * Fallback: render add form page
     */
    function render_fallback_add()
    {
        $out = '';

        $out .= html::tag('h2', 'boxtitle', rcube::Q($this->gettext('create_password')));

        $table = new html_table(array('class' => 'propform', 'cols' => 2));

        $input = new html_inputfield(array(
            'name' => '_name',
            'id' => 'apppassword-name',
            'size' => 40,
            'class' => 'form-control',
            'placeholder' => $this->gettext('name_placeholder'),
        ));

        $table->add('title', html::label('apppassword-name', rcube::Q($this->gettext('name'))));
        $table->add(null, $input->show());

        $table->add('title', html::label('apppassword-usage', rcube::Q($this->gettext('usage'))));
        $table->add(null, $this->render_usage_select()->show('email'));

        $form = $this->rcmail->output->form_tag(array(
            'id' => 'apppassword-form',
            'method' => 'post',
            'action' => './?_task=settings&_action=plugin.stalwart_apppasswords-save',
        ), $table->show());

        $this->rcmail->output->add_gui_object('editform', 'apppassword-form');

        $out .= html::tag('fieldset', null,
            html::tag('legend', null, rcube::Q($this->gettext('new_password_legend'))) .
            html::p('hint', rcube::Q($this->gettext('new_password_description'))) .
            $form
        );

        $out .= html::div('formbuttons',
            html::tag('button', array(
                'type' => 'button',
                'class' => 'button btn btn-secondary',
                'onclick' => 'history.back()',
            ), rcube::Q($this->gettext('cancel'))) .
            ' ' .
            html::tag('input', array(
                'type' => 'submit',
                'form' => 'apppassword-form',
                'class' => 'button mainaction btn btn-primary',
                'value' => $this->gettext('save'),
            ))
        );

        return html::div('boxcontent formcontent', $out);
    }

    /**
     * Fallback: render created password page
     */
    function render_fallback_created()
    {
        $password = $_SESSION['stalwart_apppassword'] ?? '';
        $name = $_SESSION['stalwart_apppassword_name'] ?? '';
        unset($_SESSION['stalwart_apppassword'], $_SESSION['stalwart_apppassword_name']);

        $out = '';

        $out .= html::tag('h2', 'boxtitle', rcube::Q($this->gettext('password_created_title')));

        $content = html::div('boxwarning', rcube::Q($this->gettext('password_warning')));

        $content .= html::p(null, sprintf(
            rcube::Q($this->gettext('password_created_for')),
            rcube::Q($name)
        ));

        $content .= html::div(array('style' => 'text-align:center;margin:20px 0'),
            html::tag('code', array(
                'id' => 'apppassword-value',
                'style' => 'font-size:1.4em;letter-spacing:2px;padding:15px 20px;background:#f5f5f5;display:inline-block;border-radius:4px;user-select:all;'
            ), $password)
        );

        $content .= html::div(array('style' => 'text-align:center'),
            html::tag('button', array(
                'type' => 'button',
                'class' => 'btn btn-secondary button',
                'onclick' => 'navigator.clipboard.writeText(document.getElementById("apppassword-value").textContent).then(function(){rcmail.display_message("' . rcube::JQ($this->gettext('copied')) . '","confirmation")})'
            ), rcube::Q($this->gettext('copy_password')))
        );

        $out .= html::tag('fieldset', null,
            html::tag('legend', null, rcube::Q($this->gettext('password_created_title'))) .
            $content
        );

        $out .= html::div('formbuttons',
            html::tag('a', array(
                'href' => './?_task=settings&_action=plugin.stalwart_apppasswords',
                'class' => 'button btn btn-primary',
            ), rcube::Q($this->gettext('done')))
        );

        return html::div('boxcontent formcontent', $out);
    }

    /**
     * Map a password's permissions object back to a usage label
     */
    private function get_usage_label($pw)
    {
        if (!is_array($pw)) {
            return $this->gettext('usage_all');
        }

        $perms = $pw['permissions'] ?? null;
        if (!$perms || ($perms['@type'] ?? '') === 'Inherit') {
            return $this->gettext('usage_all');
        }

        $list = $perms['permissions'] ?? array();
        if (array_keys($list) !== range(0, count($list) - 1)) {
            $list = array_keys(array_filter($list));
        }
        $list = array_map('strtolower', $list);

        // Check email first: the email preset includes contacts permissions
        // (for autocomplete), so we must distinguish it before the contacts check.
        $has_email = in_array('imapauthenticate', $list);
        $has_calendar = in_array('jmapcalendarget', $list);
        $has_contacts = in_array('jmapcontactcardget', $list);

        if ($has_email) {
            return $this->gettext('usage_email');
        } elseif ($has_calendar) {
            return $this->gettext('usage_calendar');
        } elseif ($has_contacts) {
            return $this->gettext('usage_contacts');
        }

        return $this->gettext('usage_all');
    }

    // ==================== PERMISSION PRESETS ====================

    /**
     * Build the usage type select element
     */
    private function render_usage_select($id = 'apppassword-usage')
    {
        $select = new html_select(array(
            'name' => '_usage',
            'id' => $id,
            'class' => 'form-control',
        ));

        $select->add($this->gettext('usage_all'), 'all');
        $select->add($this->gettext('usage_email'), 'email');
        $select->add($this->gettext('usage_calendar'), 'calendar');
        $select->add($this->gettext('usage_contacts'), 'contacts');

        return $select;
    }

    /**
     * Common permissions shared by all restricted presets
     */
    private static function common_permissions()
    {
        return array(
            'Authenticate',
            'AuthenticateWithAlias',
            'JmapPushSubscriptionGet',
            'JmapPushSubscriptionCreate',
            'JmapPushSubscriptionUpdate',
            'JmapPushSubscriptionDestroy',
            'JmapBlobGet',
            'JmapBlobCopy',
            'JmapBlobLookup',
            'JmapBlobUpload',
            'JmapQuotaGet',
            'JmapQuotaChanges',
            'JmapQuotaQuery',
            'JmapQuotaQueryChanges',
            'JmapPrincipalGet',
            'JmapPrincipalQuery',
            'JmapPrincipalChanges',
            'JmapPrincipalQueryChanges',
            'JmapPrincipalGetAvailability',
            'JmapShareNotificationGet',
            'JmapShareNotificationChanges',
            'JmapShareNotificationQuery',
            'JmapShareNotificationQueryChanges',
            'JmapShareNotificationCreate',
            'JmapShareNotificationUpdate',
            'JmapShareNotificationDestroy',
            'DavSyncCollection',
            'DavExpandProperty',
            'DavPrincipalList',
            'DavPrincipalMatch',
            'DavPrincipalSearch',
            'DavPrincipalSearchPropSet',
            'DavPrincipalAcl',
            'JmapCoreEcho',
        );
    }

    private static function email_permissions()
    {
        return array(
            'EmailSend',
            'EmailReceive',
            // IMAP
            'ImapAuthenticate', 'ImapAclGet', 'ImapAclSet', 'ImapMyRights', 'ImapListRights',
            'ImapAppend', 'ImapCapability', 'ImapId', 'ImapCopy', 'ImapMove',
            'ImapCreate', 'ImapDelete', 'ImapEnable', 'ImapExpunge', 'ImapFetch',
            'ImapIdle', 'ImapList', 'ImapLsub', 'ImapNamespace', 'ImapRename',
            'ImapSearch', 'ImapSort', 'ImapSelect', 'ImapExamine', 'ImapStatus',
            'ImapStore', 'ImapSubscribe', 'ImapThread',
            // POP3
            'Pop3Authenticate', 'Pop3List', 'Pop3Uidl', 'Pop3Stat', 'Pop3Retr', 'Pop3Dele',
            // JMAP mail
            'JmapMailboxGet', 'JmapMailboxChanges', 'JmapMailboxQuery', 'JmapMailboxQueryChanges',
            'JmapMailboxCreate', 'JmapMailboxUpdate', 'JmapMailboxDestroy',
            'JmapThreadGet', 'JmapThreadChanges',
            'JmapEmailGet', 'JmapEmailChanges', 'JmapEmailQuery', 'JmapEmailQueryChanges',
            'JmapEmailCreate', 'JmapEmailUpdate', 'JmapEmailDestroy',
            'JmapEmailCopy', 'JmapEmailImport', 'JmapEmailParse',
            'JmapSearchSnippetGet',
            'JmapIdentityGet', 'JmapIdentityChanges', 'JmapIdentityCreate',
            'JmapIdentityUpdate', 'JmapIdentityDestroy',
            'JmapEmailSubmissionGet', 'JmapEmailSubmissionChanges',
            'JmapEmailSubmissionQuery', 'JmapEmailSubmissionQueryChanges',
            'JmapEmailSubmissionCreate', 'JmapEmailSubmissionUpdate', 'JmapEmailSubmissionDestroy',
            'JmapVacationResponseGet', 'JmapVacationResponseCreate',
            'JmapVacationResponseUpdate', 'JmapVacationResponseDestroy',
            // Sieve
            'JmapSieveScriptGet', 'JmapSieveScriptQuery', 'JmapSieveScriptValidate',
            'JmapSieveScriptCreate', 'JmapSieveScriptUpdate', 'JmapSieveScriptDestroy',
            'SieveAuthenticate', 'SieveListScripts', 'SieveSetActive',
            'SieveGetScript', 'SievePutScript', 'SieveDeleteScript',
            'SieveRenameScript', 'SieveCheckScript', 'SieveHaveSpace',
        );
    }

    private static function contacts_permissions()
    {
        return array(
            'JmapAddressBookGet', 'JmapAddressBookChanges',
            'JmapAddressBookCreate', 'JmapAddressBookUpdate', 'JmapAddressBookDestroy',
            'JmapContactCardGet', 'JmapContactCardChanges',
            'JmapContactCardQuery', 'JmapContactCardQueryChanges',
            'JmapContactCardCreate', 'JmapContactCardUpdate', 'JmapContactCardDestroy',
            'JmapContactCardCopy', 'JmapContactCardParse',
            'DavCardPropFind', 'DavCardPropPatch', 'DavCardGet', 'DavCardMkCol',
            'DavCardDelete', 'DavCardPut', 'DavCardCopy', 'DavCardMove',
            'DavCardLock', 'DavCardAcl', 'DavCardQuery', 'DavCardMultiGet',
        );
    }

    private static function calendar_permissions()
    {
        return array(
            'CalendarAlarmsSend',
            'CalendarSchedulingSend',
            'CalendarSchedulingReceive',
            'JmapCalendarGet', 'JmapCalendarChanges',
            'JmapCalendarCreate', 'JmapCalendarUpdate', 'JmapCalendarDestroy',
            'JmapCalendarEventGet', 'JmapCalendarEventChanges',
            'JmapCalendarEventQuery', 'JmapCalendarEventQueryChanges',
            'JmapCalendarEventCreate', 'JmapCalendarEventUpdate', 'JmapCalendarEventDestroy',
            'JmapCalendarEventCopy', 'JmapCalendarEventParse',
            'JmapCalendarEventNotificationGet', 'JmapCalendarEventNotificationChanges',
            'JmapCalendarEventNotificationQuery', 'JmapCalendarEventNotificationQueryChanges',
            'JmapCalendarEventNotificationCreate', 'JmapCalendarEventNotificationUpdate',
            'JmapCalendarEventNotificationDestroy',
            'JmapParticipantIdentityGet', 'JmapParticipantIdentityChanges',
            'JmapParticipantIdentityCreate', 'JmapParticipantIdentityUpdate',
            'JmapParticipantIdentityDestroy',
            'DavCalPropFind', 'DavCalPropPatch', 'DavCalGet', 'DavCalMkCol',
            'DavCalDelete', 'DavCalPut', 'DavCalCopy', 'DavCalMove',
            'DavCalLock', 'DavCalAcl', 'DavCalQuery', 'DavCalMultiGet',
            'DavCalFreeBusyQuery',
        );
    }

    /**
     * Get the permissions object for a given usage preset
     */
    private function get_permissions_for_usage($usage)
    {
        $normalize = function ($permissions) {
            $names = array_values(array_unique(array_map(function ($permission) {
                return is_string($permission) ? lcfirst($permission) : $permission;
            }, $permissions)));

            $map = array();
            foreach ($names as $name) {
                $map[$name] = true;
            }

            return $map;
        };

        switch ($usage) {
            case 'email':
                return array(
                    '@type' => 'Replace',
                    'permissions' => $normalize(array_merge(
                        self::common_permissions(),
                        self::email_permissions(),
                        self::contacts_permissions()
                    )),
                );
            case 'calendar':
                return array(
                    '@type' => 'Replace',
                    'permissions' => $normalize(array_merge(
                        self::common_permissions(),
                        self::calendar_permissions()
                    )),
                );
            case 'contacts':
                return array(
                    '@type' => 'Replace',
                    'permissions' => $normalize(array_merge(
                        self::common_permissions(),
                        self::contacts_permissions()
                    )),
                );
            default:
                return array('@type' => 'Inherit');
        }
    }

    // ==================== JMAP API METHODS ====================

    /**
     * Get OAuth token from session.
     * Since Roundcube 1.7, the access_token is removed from $_SESSION['oauth_token']
     * and stored encrypted in $_SESSION['password'] as a "Bearer <token>" string.
     */
    private function get_oauth_token()
    {
        // Roundcube 1.7+: access_token stored encrypted in $_SESSION['password']
        if (isset($_SESSION['oauth_token']) && isset($_SESSION['password'])) {
            $authorization = $this->rcmail->decrypt($_SESSION['password']);
            if ($authorization && stripos($authorization, 'Bearer ') === 0) {
                return substr($authorization, 7);
            }
            return $authorization ?: null;
        }

        // Roundcube 1.6 fallback
        if (isset($_SESSION['oauth_token']['access_token'])) {
            return $_SESSION['oauth_token']['access_token'];
        }

        return null;
    }

    /**
     * Get the JMAP account ID for the authenticated user.
     * Fetches from /jmap/session and caches in the PHP session.
     */
    private function get_account_id()
    {
        if (!empty($_SESSION['stalwart_account_id'])) {
            return $_SESSION['stalwart_account_id'];
        }

        $base_url = $this->rcmail->config->get('stalwart_url', 'http://localhost:8080');
        $token = $this->get_oauth_token();

        if (!$token) {
            return null;
        }

        $url = rtrim($base_url, '/') . '/jmap/session';

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
            ),
        ));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            return null;
        }

        $session = json_decode($response, true);
        $account_id = $session['primaryAccounts']['urn:stalwart:jmap'] ?? null;

        if ($account_id) {
            $_SESSION['stalwart_account_id'] = $account_id;
        }

        return $account_id;
    }

    /**
     * Make a JMAP request to Stalwart
     */
    private function jmap_request($method, $args)
    {
        $base_url = $this->rcmail->config->get('stalwart_url', 'http://localhost:8080');
        $token = $this->get_oauth_token();

        if (!$token) {
            rcube::raise_error(array(
                'code' => 500,
                'message' => 'Stalwart App Passwords: No OAuth token available'
            ), true, false);
            return array('success' => false, 'error' => $this->gettext('error_no_token'));
        }

        $account_id = $this->get_account_id();
        if (!$account_id) {
            return array('success' => false, 'error' => $this->gettext('error_api'));
        }

        $args['accountId'] = $account_id;

        $payload = array(
            'using' => array(
                'urn:ietf:params:jmap:core',
                'urn:stalwart:jmap',
            ),
            'methodCalls' => array(
                array($method, $args, '0'),
            ),
        );

        $url = rtrim($base_url, '/') . '/jmap';

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json',
            ),
        ));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            rcube::raise_error(array(
                'code' => 500,
                'message' => 'Stalwart JMAP curl error: ' . $curl_error
            ), true, false);
            return array('success' => false, 'error' => $this->gettext('error_connection'));
        }

        $decoded = json_decode($response, true);

        if ($http_code >= 200 && $http_code < 300 && !empty($decoded['methodResponses'])) {
            $method_response = $decoded['methodResponses'][0];
            if ($method_response[0] === 'error') {
                $error = $method_response[1]['description'] ?? $method_response[1]['type'] ?? $this->gettext('error_api');
                return array('success' => false, 'error' => $error);
            }
            return array(
                'success' => true,
                'method' => $method_response[0],
                'data' => $method_response[1],
            );
        }

        $error = $decoded['detail'] ?? $decoded['error'] ?? $this->gettext('error_api');
        return array('success' => false, 'error' => $error, 'http_code' => $http_code);
    }

    /**
     * Get list of app passwords via JMAP x:AppPassword/get
     */
    private function jmap_get_app_passwords()
    {
        $result = $this->jmap_request('x:AppPassword/get', array());

        if (!$result['success']) {
            return array();
        }

        return $result['data']['list'] ?? array();
    }

    /**
     * Create a new app password via JMAP x:AppPassword/set.
     * The server generates the secret and returns it once.
     */
    private function jmap_create_app_password($description, $usage = 'all')
    {
        $result = $this->jmap_request('x:AppPassword/set', array(
            'create' => array(
                'new1' => array(
                    'description' => $description,
                    'permissions' => $this->get_permissions_for_usage($usage),
                ),
            ),
        ));

        if (!$result['success']) {
            return $result;
        }

        $created = $result['data']['created']['new1'] ?? null;
        if ($created && !empty($created['secret'])) {
            return array('success' => true, 'secret' => $created['secret']);
        }

        $not_created = $result['data']['notCreated']['new1'] ?? null;
        $error = $not_created['description'] ?? $this->gettext('error_creating');
        return array('success' => false, 'error' => $error);
    }

    /**
     * Delete an app password via JMAP x:AppPassword/set
     */
    private function jmap_delete_app_password($credential_id)
    {
        $result = $this->jmap_request('x:AppPassword/set', array(
            'destroy' => array($credential_id),
        ));

        if (!$result['success']) {
            return $result;
        }

        $destroyed = $result['data']['destroyed'] ?? array();
        if (in_array($credential_id, $destroyed)) {
            return array('success' => true);
        }

        $not_destroyed = $result['data']['notDestroyed'][$credential_id] ?? null;
        $error = $not_destroyed['description'] ?? $this->gettext('error_deleting');
        return array('success' => false, 'error' => $error);
    }
}