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

        $password = $this->generate_password();
        $result = $this->api_create_app_password($name, $password);

        if ($result['success']) {
            $_SESSION['stalwart_apppassword'] = $password;
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
        $idx = intval(rcube_utils::get_input_value('_idx', rcube_utils::INPUT_POST));

        $passwords = $this->api_get_app_passwords();
        if (isset($passwords[$idx])) {
            $result = $this->api_delete_app_password($passwords[$idx]['encoded_name']);
            if ($result['success']) {
                $this->rcmail->output->show_message($this->gettext('password_deleted'), 'confirmation');
                $this->rcmail->output->command('plugin.apppassword_deleted', array('idx' => $idx));
            } else {
                $this->rcmail->output->show_message($this->gettext('error_deleting'), 'error');
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
        $passwords = $this->api_get_app_passwords();

        $table = new html_table(array(
            'id' => $attrib['id'],
            'class' => 'listing iconized',
            'role' => 'listbox',
        ));

        if (is_array($passwords) && !empty($passwords)) {
            foreach ($passwords as $idx => $pw) {
                $table->add_row(array('id' => 'rcmrow' . $idx));
                $table->add(array('class' => 'name'), rcube::Q($pw['name']));
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

        $passwords = $this->api_get_app_passwords();

        $table = new html_table(array(
            'class' => 'records-table listing',
            'id' => 'apppasswords-table-fallback',
            'cellspacing' => '0',
            'cols' => 3,
        ));

        $table->add_header('name', rcube::Q($this->gettext('col_name')));
        $table->add_header('created', rcube::Q($this->gettext('col_created')));
        $table->add_header('action', '&nbsp;');

        if (empty($passwords)) {
            $table->add(array('colspan' => 3, 'class' => 'hint'), rcube::Q($this->gettext('no_passwords')));
        } else {
            foreach ($passwords as $idx => $pw) {
                // Changed from <a> to <input type="button"> to guarantee button appearance
                $delete_btn = html::tag('input', array(
                    'type' => 'button',
                    'class' => 'button', // Standard button style
                    'onclick' => 'return rcmail.command("plugin.stalwart_apppasswords-delete-fallback", ' . $idx . ', this)',
                    'value' => $this->gettext('delete'),
                    'title' => $this->gettext('delete')
                ));

                $created = '';
                if (!empty($pw['created'])) {
                    try {
                        $date = new DateTime($pw['created']);
                        $created = $date->format('Y-m-d H:i');
                    } catch (Exception $e) {
                        $created = $pw['created'];
                    }
                }

                $table->add('name', rcube::Q($pw['name']));
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

    // ==================== API METHODS ====================

    /**
     * Get OAuth token from session
     */
    private function get_oauth_token()
    {
        if (isset($_SESSION['oauth_token']['access_token'])) {
            return $_SESSION['oauth_token']['access_token'];
        }
        return null;
    }

    /**
     * Make API request to Stalwart
     */
    private function api_request($endpoint, $method = 'GET', $data = null)
    {
        $api_url = $this->rcmail->config->get('stalwart_api_url', 'http://localhost:8080/api');
        $token = $this->get_oauth_token();

        if (!$token) {
            rcube::raise_error(array(
                'code' => 500,
                'message' => 'Stalwart App Passwords: No OAuth token available'
            ), true, false);
            return array('success' => false, 'error' => $this->gettext('error_no_token'));
        }

        $url = rtrim($api_url, '/') . $endpoint;

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json',
            ),
        ));

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            rcube::raise_error(array(
                'code' => 500,
                'message' => 'Stalwart API curl error: ' . $curl_error
            ), true, false);
            return array('success' => false, 'error' => $this->gettext('error_connection'));
        }

        $decoded = json_decode($response, true);

        if ($http_code >= 200 && $http_code < 300) {
            return array('success' => true, 'data' => $decoded['data'] ?? $decoded);
        }

        return array(
            'success' => false,
            'error' => $decoded['detail'] ?? $decoded['error'] ?? $this->gettext('error_api'),
            'http_code' => $http_code,
        );
    }

    /**
     * Get list of app passwords
     */
    private function api_get_app_passwords()
    {
        $result = $this->api_request('/account/auth');

        if (!$result['success']) {
            return array();
        }

        $passwords = array();
        $app_passwords = $result['data']['appPasswords'] ?? array();

        foreach ($app_passwords as $encoded_name) {
            $decoded = base64_decode($encoded_name, true);
            $name = $encoded_name;
            $created = null;

            if ($decoded !== false && strpos($decoded, '$') !== false) {
                $parts = explode('$', $decoded, 2);
                $name = $parts[0];
                $created = $parts[1] ?? null;
            }

            $passwords[] = array(
                'name' => $name,
                'encoded_name' => $encoded_name,
                'created' => $created,
            );
        }

        return $passwords;
    }

    /**
     * Create a new app password
     */
    private function api_create_app_password($name, $password)
    {
        // Generate salt for SHA-512 crypt
        $salt_chars = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $salt = '';
        for ($i = 0; $i < 16; $i++) {
            $salt .= $salt_chars[random_int(0, 63)];
        }

        // Hash password
        $hashed = crypt($password, '$6$' . $salt . '$');

        // Encode name with timestamp
        $name_with_timestamp = $name . '$' . date('c');
        $encoded_name = base64_encode($name_with_timestamp);

        $data = array(array(
            'type' => 'addAppPassword',
            'name' => $encoded_name,
            'password' => $hashed,
        ));

        return $this->api_request('/account/auth', 'POST', $data);
    }

    /**
     * Delete an app password
     */
    private function api_delete_app_password($encoded_name)
    {
        $data = array(array(
            'type' => 'removeAppPassword',
            'name' => $encoded_name,
        ));

        return $this->api_request('/account/auth', 'POST', $data);
    }

    /**
     * Generate a random password
     */
    private function generate_password($length = 16)
    {
        // Exclude confusing characters (0, O, l, 1, I)
        $chars = 'abcdefghjkmnpqrstuvwxyz23456789';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }
}