/**
 * Stalwart App Passwords - Client-side JavaScript
 */
if (window.rcmail) {
    rcmail.addEventListener('init', function() {

        // ==================== Custom Template Mode (Elastic) ====================

        if (rcmail.gui_objects.apppasswordslist) {
            rcmail.apppasswords_list = new rcube_list_widget(rcmail.gui_objects.apppasswordslist, {
                multiselect: false,
                keyboard: true
            });

            rcmail.apppasswords_list.addEventListener('select', function(list) {
                var selected = list.get_single_selection();
                rcmail.enable_command('plugin.stalwart_apppasswords-delete', selected != null);
            });

            rcmail.apppasswords_list.init();
        }

        // Add command (works for both modes)
        rcmail.register_command('plugin.stalwart_apppasswords-add', function() {
            var frame = rcmail.get_frame_window(rcmail.env.contentframe);
            if (frame) {
                rcmail.location_href({_action: 'plugin.stalwart_apppasswords-add'}, frame);
            } else {
                rcmail.goto_url('plugin.stalwart_apppasswords-add');
            }
        }, true);

        // Delete command (custom template / Elastic)
        rcmail.register_command('plugin.stalwart_apppasswords-delete', function() {
            if (!rcmail.apppasswords_list) return;

            var sel = rcmail.apppasswords_list.get_single_selection();
            if (sel && confirm(rcmail.gettext('stalwart_apppasswords.confirm_delete'))) {
                var idx = sel.replace('rcmrow', '');
                rcmail.http_post('plugin.stalwart_apppasswords-delete', {_idx: idx}, rcmail.set_busy(true, 'loading'));
            }
        }, false);

        // ==================== Fallback Mode ====================

        // Delete command (fallback template)
        rcmail.register_command('plugin.stalwart_apppasswords-delete-fallback', function(idx) {
            if (confirm(rcmail.gettext('stalwart_apppasswords.confirm_delete'))) {
                rcmail.http_post('plugin.stalwart_apppasswords-delete', {_idx: idx}, rcmail.set_busy(true, 'loading'));
            }
        }, true); // <-- Changed to 'true' to enable the command

        // ==================== Response Handlers ====================

        // Handle deletion response
        rcmail.addEventListener('plugin.apppassword_deleted', function(response) {
            // Custom template mode (Elastic)
            if (rcmail.apppasswords_list) {
                rcmail.apppasswords_list.remove_row('rcmrow' + response.idx);
                rcmail.enable_command('plugin.stalwart_apppasswords-delete', false);
            } else {
                // Fallback mode - reload page to refresh the list
                window.location.reload();
            }
        });
    });
}