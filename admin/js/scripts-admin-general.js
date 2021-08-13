jQuery(function ($) {

  $(document).ready(function () {
    display_sub_sections();
  });

  $(document).on('click', '#echo_main_col_connect_echo_api_token_show', function () {
    let api_token_input_ele = $('#echo_main_col_connect_echo_api_token');
    let api_token_show_ele = $('#echo_main_col_connect_echo_api_token_show');

    if (api_token_show_ele.is(':checked')) {
      api_token_input_ele.attr('type', 'text');
    } else {
      api_token_input_ele.attr('type', 'password');
    }
  });

  $(document).on('click', '#echo_main_col_available_echo_convo_options_select_add', function () {
    echo_convo_option_add();
  });

  $(document).on('click', '.echo-main-col-supported-echo-convo-options-table-row-remove-but', function (e) {
    echo_convo_option_remove(e);
  });

  $(document).on('click', '#echo_main_col_available_echo_convo_referrers_select_add', function () {
    echo_convo_referrer_add();
  });

  $(document).on('click', '.echo-main-col-supported-echo-convo-referrers-table-row-remove-but', function (e) {
    echo_convo_referrer_remove(e);
  });

  $(document).on('click', '#echo_main_col_supported_dt_seeker_path_options_select_ele_add', function () {
    dt_option_add(
      'echo_main_col_supported_dt_seeker_path_options_select_ele',
      'echo_main_col_supported_dt_seeker_path_options_table',
      'echo_main_col_supported_dt_seeker_path_options_form',
      'echo_main_col_supported_dt_seeker_path_options_hidden'
    );
  });

  $(document).on('click', '#echo_main_col_supported_dt_seeker_path_options_update_but', function () {
    dt_option_update(
      'echo_main_col_supported_dt_seeker_path_options_table',
      'echo_main_col_supported_dt_seeker_path_options_form',
      'echo_main_col_supported_dt_seeker_path_options_hidden'
    );
  });

  $(document).on('click', '.echo-main-col-supported-dt-seeker-path-options-table-row-remove-but', function (e) {
    dt_option_remove(e);
  });

  function display_sub_sections() {

    // Sub-sections to be displayed once a valid Echo api token has been detected.

    let available_echo_convo_options_table_section = $('#echo_main_col_available_echo_convo_options_table_section');
    let supported_echo_convo_options_table_section = $('#echo_main_col_supported_echo_convo_options_table_section');
    let available_echo_convo_referrers_table_section = $('#echo_main_col_available_echo_convo_referrers_table_section');
    let supported_echo_convo_referrers_table_section = $('#echo_main_col_supported_echo_convo_referrers_table_section');
    let supported_dt_seeker_path_options_table_section = $('#echo_main_col_supported_dt_seeker_path_options_table_section');

    if (!$('#echo_main_col_connect_echo_api_token').val().trim()) {
      available_echo_convo_options_table_section.fadeOut('fast');
      supported_echo_convo_options_table_section.fadeOut('fast');
      available_echo_convo_referrers_table_section.fadeOut('fast');
      supported_echo_convo_referrers_table_section.fadeOut('fast');
      supported_dt_seeker_path_options_table_section.fadeOut('fast');
    } else {
      available_echo_convo_options_table_section.fadeIn('fast');
      supported_echo_convo_options_table_section.fadeIn('fast');
      available_echo_convo_referrers_table_section.fadeIn('fast');
      supported_echo_convo_referrers_table_section.fadeIn('fast');
      supported_dt_seeker_path_options_table_section.fadeIn('fast');
    }
  }

  function echo_convo_option_add() {
    let selected_convo_option_id = $('#echo_main_col_available_echo_convo_options_select').val();
    let selected_convo_option_name = $('#echo_main_col_available_echo_convo_options_select option:selected').text();

    // Only proceed if we have a valid id
    if (!selected_convo_option_id) {
      return;
    }

    // Set hidden form values and post
    $('#echo_main_col_available_echo_convo_options_selected_id').val(selected_convo_option_id);
    $('#echo_main_col_available_echo_convo_options_selected_name').val(selected_convo_option_name);
    $('#echo_main_col_available_echo_convo_options_form').submit();
  }

  function echo_convo_option_remove(evt) {
    let selected_convo_option_id = evt.currentTarget.parentNode.parentNode.querySelector('#echo_main_col_supported_echo_convo_options_table_row_remove_hidden_id').getAttribute('value');

    // Remove from hidden current convo options array
    echo_hidden_convo_option_remove(selected_convo_option_id);

    // Save removal updates
    $('#echo_main_col_supported_echo_convo_options_form').submit();
  }

  function echo_hidden_convo_option_remove(id) {
    let current_convo_options = echo_hidden_convo_options_load();

    if (current_convo_options) {
      delete current_convo_options[id];
      echo_hidden_convo_options_save(current_convo_options);
    }
  }

  function echo_hidden_convo_options_load() {
    return JSON.parse($('#echo_main_col_supported_echo_convo_options_hidden_current_convo_options').val())
  }

  function echo_hidden_convo_options_save(updated_convo_options) {
    $('#echo_main_col_supported_echo_convo_options_hidden_current_convo_options').val(JSON.stringify(updated_convo_options));
  }

  function echo_convo_referrer_add() {
    let selected_convo_referrer_id = $('#echo_main_col_available_echo_convo_referrers_select').val();
    let selected_convo_referrer_name = $('#echo_main_col_available_echo_convo_referrers_select option:selected').text();

    // Only proceed if we have a valid id
    if (!selected_convo_referrer_id) {
      return;
    }

    // Set hidden form values and post
    $('#echo_main_col_available_echo_convo_referrers_selected_id').val(selected_convo_referrer_id);
    $('#echo_main_col_available_echo_convo_referrers_selected_name').val(selected_convo_referrer_name);
    $('#echo_main_col_available_echo_convo_referrers_form').submit();
  }

  function echo_convo_referrer_remove(evt) {
    let selected_convo_referrer_id = evt.currentTarget.parentNode.parentNode.querySelector('#echo_main_col_supported_echo_convo_referrers_table_row_remove_hidden_id').getAttribute('value');

    // Remove from hidden current convo referrers array
    echo_hidden_convo_referrer_remove(selected_convo_referrer_id)

    // Save removal updates
    $('#echo_main_col_supported_echo_convo_referrers_form').submit();
  }

  function echo_hidden_convo_referrer_remove(id) {
    let current_convo_referrers = echo_hidden_convo_referrers_load();

    if (current_convo_referrers) {

      let updated_convo_referrers = [];
      current_convo_referrers.forEach(function (item) {

        if (String(item['id'].trim().toLowerCase()).valueOf() !== String(id.trim().toLowerCase()).valueOf()) {
          updated_convo_referrers.push(item);
        }
      });

      echo_hidden_convo_referrers_save(updated_convo_referrers);
    }
  }

  function echo_hidden_convo_referrers_load() {
    return JSON.parse($('#echo_main_col_supported_echo_convo_referrers_hidden_current_convo_referrers').val())
  }

  function echo_hidden_convo_referrers_save(updated_convo_referrers) {
    $('#echo_main_col_supported_echo_convo_referrers_hidden_current_convo_referrers').val(JSON.stringify(updated_convo_referrers));
  }

  function dt_option_add(dt_option_select_ele, dt_option_table, dt_option_form, dt_option_hidden) {
    let selected_option_id = $('#' + dt_option_select_ele).val();
    let selected_option_name = $('#' + dt_option_select_ele + ' option:selected').text();

    // Ignore empty values
    if (!selected_option_id) {
      return;
    }

    // Only proceed if option has not already been assigned
    if (dt_option_add_already_assigned(selected_option_id, dt_option_table)) {
      return;
    }

    // Append selected option to main table
    dt_option_table_append(selected_option_id, selected_option_name, dt_option_table, dt_option_form, dt_option_hidden);
  }

  function dt_option_add_already_assigned(selected_option_id, dt_option_table) {
    let assigned = false;

    $('#' + dt_option_table + ' > tbody > tr').each(function (idx, tr) {
      let dt_option_id = $(tr).find('#echo_main_col_supported_dt_option_id_hidden').val();
      if (dt_option_id && dt_option_id === selected_option_id) {
        assigned = true;
      }
    });

    return assigned;
  }

  function dt_option_table_append(selected_option_id, selected_option_name, dt_option_table, dt_option_form, dt_option_hidden) {

    let html = '<tr>';
    html += '<input type="hidden" id="echo_main_col_supported_dt_option_table_hidden" value="' + dt_option_table + '" />';
    html += '<input type="hidden" id="echo_main_col_supported_dt_option_form_hidden" value="' + dt_option_form + '" />';
    html += '<input type="hidden" id="echo_main_col_supported_dt_option_values_hidden" value="' + dt_option_hidden + '" />';
    html += '<input type="hidden" id="echo_main_col_supported_dt_option_id_hidden" value="' + selected_option_id + '" />';
    html += '<input type="hidden" id="echo_main_col_supported_dt_option_name_hidden" value="' + selected_option_name + '" />';
    html += '<td style="vertical-align: middle; text-align: center;">';
    html += selected_option_name;
    html += '</td>';
    html += '<td style="vertical-align: middle; text-align: center;">';
    html += '<select style="max-width: 300px;" id="echo_main_col_supported_dt_echo_option_select">';

    let echo_convo_options = JSON.parse($('#echo_main_col_supported_dt_seeker_path_options_supported_echo_convo_options_hidden').val());
    if (echo_convo_options) {
      $.each(echo_convo_options, function (idx, option) {
        html += '<option value="' + option['id'] + '">' + option['name'] + '</option>';
      });
    }

    html += '</select>';
    html += '</td>';
    html += '<td>';
    html += '<span style="float:right;"><a class="button float-right echo-main-col-supported-dt-seeker-path-options-table-row-remove-but">Remove</a></span>';
    html += '</td>';
    html += '</tr>';

    // Append row...!
    $('#' + dt_option_table + ' > tbody:last-child').append(html);
  }

  function dt_option_update(dt_option_table, dt_option_form, dt_option_hidden) {
    let options = [];

    // Iterate and package already existing options
    $('#' + dt_option_table + ' > tbody > tr').each(function (idx, tr) {
      let dt_option_id = $(tr).find('#echo_main_col_supported_dt_option_id_hidden').val();
      let dt_option_name = $(tr).find('#echo_main_col_supported_dt_option_name_hidden').val();

      let echo_option_id = $(tr).find('#echo_main_col_supported_dt_echo_option_select').val();
      let echo_option_name = $(tr).find('#echo_main_col_supported_dt_echo_option_select option:selected').text();

      options.push({
        "dt_id": dt_option_id,
        "dt_name": dt_option_name,
        "echo_id": echo_option_id,
        "echo_name": echo_option_name
      });
    });

    // Save updated options ready for posting
    $('#' + dt_option_hidden).val(JSON.stringify(options));

    // Trigger form post..!
    $('#' + dt_option_form).submit();
  }

  function dt_option_remove(evt) {

    // Obtain handle onto deleted row
    let row = evt.currentTarget.parentNode.parentNode.parentNode;

    // Remove row from parent table
    row.parentNode.removeChild(row);

    // Obtain hidden values and persist/save changes
    let dt_option_table = $(row).find('#echo_main_col_supported_dt_option_table_hidden').val();
    let dt_option_form = $(row).find('#echo_main_col_supported_dt_option_form_hidden').val();
    let dt_option_hidden = $(row).find('#echo_main_col_supported_dt_option_values_hidden').val();

    // Auto-Update...!
    dt_option_update(dt_option_table, dt_option_form, dt_option_hidden);
  }

});
