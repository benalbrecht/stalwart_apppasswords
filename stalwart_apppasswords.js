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
            if (sel && confirm(rcmail.gettext('confirm_delete', 'stalwart_apppasswords'))) {
                var id = sel.replace('rcmrow', '');
                rcmail.http_post('plugin.stalwart_apppasswords-delete', {_id: id}, rcmail.set_busy(true, 'loading'));
            }
        }, false);

        // ==================== Fallback Mode ====================

        // Delete command (fallback template)
        rcmail.register_command('plugin.stalwart_apppasswords-delete-fallback', function(id) {
            if (confirm(rcmail.gettext('confirm_delete', 'stalwart_apppasswords'))) {
                rcmail.http_post('plugin.stalwart_apppasswords-delete', {_id: id}, rcmail.set_busy(true, 'loading'));
            }
        }, true);

        // ==================== Response Handlers ====================

        // Handle deletion response
        rcmail.addEventListener('plugin.apppassword_deleted', function(response) {
            var row_id = 'rcmrow' + response.id;

            // Custom template mode (Elastic)
            if (rcmail.apppasswords_list) {
                rcmail.apppasswords_list.remove_row(row_id);
                rcmail.enable_command('plugin.stalwart_apppasswords-delete', false);
            }

            var row = document.getElementById(row_id);
            if (row && row.parentNode) {
                row.parentNode.removeChild(row);
                return;
            }

            // Fallback if row wasn't found in the current DOM/list state
            window.location.reload();
        });
    });
}
