(function ($) {
    $.fn.formbuilder = function (options) {
        var defaults = {
            save_url: true,
            load_url: true,
            control_box_target: false,
            useJson: true, // XML as fallback
            serialize_prefix: 'frmb',
            messages: {
                save: "Save",
                add_new_field: "Add New Field...",
                text: "Text Field",
                Email: "Email Field",
                captcha: "Recaptcha",
                title: "Title",
                paragraph: "Paragraph",
                checkboxes: "Checkboxes",
                radio: "Radio",
                select: "Select List",
                text_field: "Text Field",
                label: "Label",
                paragraph_field: "Paragraph Field",
                select_options: "Select Options",
                add: "Add",
                checkbox_group: "Checkbox Group",
                remove_message: "Are you sure you want to remove this element?",
                remove: "Remove",
                radio_group: "Radio Group",
                selections_message: "Allow Multiple Selections",
                hide: "Hide",
                required: "Required",
                show: "Show",
                pagebreak: "Page Break",
                grid: "Grid",
                scale: "Scale"
            }
        };
        var opts = $.extend(defaults, options);
        var frmb_id = 'frmb-' + $('ul[id^=frmb-]').length++;
        return this.each(function () {
            var ul_obj = $(this).append('<ul id="' + frmb_id + '" class="frmb"></ul>').find('ul');
            var field = '';
            var field_type = '';
            var last_id = 1;
            var help;
            // Add a unique class to the current element
            $(ul_obj).addClass(frmb_id);
            // load existing form data
            if (opts.load_url) {
                $.ajax({
                    type: "GET",
                    url: opts.load_url,
                    success: function (data) {
                        if (opts.useJson) {
                            fromJson(data);
                        } else {
                            fromXml(data);
                        }
                    }
                });
            }
            // Create form control select box and add into the editor
            var controlBox = function (target) {
                var select = '';
                var box_content = '';
                var save_button = '';
                var box_id = frmb_id + '-control-box';
                var save_id = frmb_id + '-save-button';
                // Add the available options
                select += '<option value="0">' + opts.messages.add_new_field + '</option>';
                select += '<option value="input_text">' + opts.messages.text + '</option>';
                select += '<option value="input_email">' + opts.messages.Email + '</option>';
                select += '<option value="textarea">' + opts.messages.paragraph + '</option>';
                select += '<option value="checkbox">' + opts.messages.checkboxes + '</option>';
                select += '<option value="radio">' + opts.messages.radio + '</option>';
                select += '<option value="select">' + opts.messages.select + '</option>';
                select += '<option value="recaptcha">Recaptcha</option>';

                box_content = '<select id="' + box_id + '" class="frmb-control">' + select + '</select><br clear="all" />';

                save_button = '<div class="form-actions"><input type="submit" id="' + save_id + '" class="btn btn-primary" value="' + opts.messages.save + '"/></div>';
                // Insert the control box into page
                if (!target) {
                    $(ul_obj).after(box_content);
                } else {
                    $(target).append(box_content);
                }
                // Insert the search button
                $("#tabs-3").after(save_button);
                // Set the form save action
                $('#' + save_id).click(function () {
                    save();
                    return false;
                });
                // Add a callback to the select element
                $('#' + box_id).change(function () {
                    appendNewField($(this).val(), '', '', '', '');
                    $(this).val(0).blur();
                    // This solves the scrollTo dependency
                    $('html, body').animate({
                        scrollTop: $('#frm-' + (last_id - 1) + '-item').offset().top
                    }, 500);
                    return false;
                });
            }(opts.control_box_target);
            // XML parser to build the form builder
            var fromXml = function (xml) {
                var values = '';
                var options = false;
                var required = false;
                $(xml).find('field').each(function () {
                    // checkbox type
                    if ($(this).attr('type') === 'checkbox') {
                        options = [$(this).attr('title')];
                        values = [];
                        $(this).find('checkbox').each(function () {
                            values.push([$(this).text(), $(this).attr('checked')]);
                        });
                    }
                    // radio type
                    else if ($(this).attr('type') === 'radio') {
                        options = [$(this).attr('title')];
                        values = [];
                        $(this).find('radio').each(function () {
                            values.push([$(this).text(), $(this).attr('checked')]);
                        });
                    }
                    // select type
                    else if ($(this).attr('type') === 'select') {
                        options = [$(this).attr('title'), $(this).attr('multiple')];
                        values = [];
                        $(this).find('option').each(function () {
                            values.push([$(this).text(), $(this).attr('checked')]);
                        });
                    }
                    else {
                        values = $(this).text();
                    }
                    appendNewField($(this).attr('type'), values, options, $(this).attr('required'));
                });
            };
            // Json parser to build the form builder
            var fromJson = function (json) {
                var values = '';
                var options = false;
                var required = false;

                json = jQuery.parseJSON(json);
                // Parse json
                $(json).each(function () {
                    // checkbox type
                    if (this.cssClass === 'checkbox') {
                        options = [this.title];
                        values = [];
                        $.each(this.values, function (i, val) {
                            //alert(i+" "+val);
                            values.push([val, '']);
                        });
                    }
                    // radio type
                    else if (this.cssClass === 'radio') {
                        options = [this.title];
                        values = [];
                        $.each(this.values, function (i, val) {
                            values.push([val, this.baseline]);
                        });
                    }
                    // select type
                    else if (this.cssClass === 'select') {
                        options = [this.title, this.multiple];
                        values = [];
                        $.each(this.values, function (i, val) {
                            values.push([val, this.baseline]);
                        });
                    }
                    else {
                        values = [this.title];
                    }
                    appendNewField(this.cssClass, values, options, this.required, this.helping_text);
                });
            };
            // Wrapper for adding a new field
            var appendNewField = function (type, values, options, required, helping_text) {
                field = '';
                field_type = type;
                if (typeof (values) === 'undefined') {
                    values = '';
                }
                switch (type) {
                    case 'input_text':
                        appendTextInput(values, required, helping_text);
                        break;
                    case 'input_email':
                        appendTextEmail(values, required, helping_text);
                        break;
                    case 'textarea':
                        appendTextarea(values, required, helping_text);
                        break;
                    case 'checkbox':
                        appendCheckboxGroup(values, options, required, '', helping_text);
                        break;
                    case 'radio':
                        appendRadioGroup(values, options, required, '', helping_text);
                        break;
                    case 'select':
                        appendSelectList(values, options, required, '', helping_text);
                        break;
                    case 'recaptcha':
                        appendRecaptcha(values, required, '', helping_text);
                        break;
                    case 'page_break':
                        //alert('');
                        appendPageBreak(values, options, required, '', helping_text);
                        break;
                    case 'grid':
                        //alert('');
                        appendGrid(values, options, required, '');
                        break;
                    case 'scale':
                        //alert('');
                        appendScale(values, options, required, '');
                        break;
                }
            };
            // Page Break
            var appendPageBreak = function (values, required) {
                appendFieldLi('Page Break', field, required, help, '');
            };
            // grid
            var appendGrid = function (values, options, required, editval) {
                var title = '';
                if (typeof (options) === 'object') {
                    title = options[0];
                }
                if (editval == '') {
                    field += '<div class="grid_group">';
                    field += '<div class="frm-fld"><label>' + opts.messages.title + '</label>';
                    field += '<input type="text" name="title" value="' + title + '" /></div>';
                    field += '<div class="clear"></div>';
                    field += '<label>Help</label>';
                    field += '<input class="fld-title" id="help-' + last_id + '" type="text" value="' + values + '" />';
                    field += '<div class="clear"></div>';
                    field += '<div id="cut-renew-' + last_id + '">';
                    field += '<div id="col-1-' + last_id + '"><label>Columns Label</label>';
                    field += '<input class="fld-title" id="col-label-1-' + last_id + '" type="text"  />';
                    field += '</div>';
                    field += '<div class="clear"></div>';
                    field += '<div class="false-label">Additional Columns</div>';
                    field += '<div class="fields">';

                    if (typeof (values) === 'object') {
                        for (i = 0; i < values.length; i++) {
                            //alert('one');
                            field += gridFieldHtml(values[i]);
                        }
                    }
                    else {
                        //alert('tw0');
                        field += gridFieldHtml('');
                    }
                    field += '<div class="add-area"><a href="#" class="add add_grid">' + opts.messages.add + '</a></div>';
                    field += '</div>';
                    field += '<div class="clear"></div>';
                    field += '<div class="dashed"></div>';
                    field += '<div class="clear"></div>';
                    field += '<label>Row Label</label>';
                    field += '<input class="fld-title" id="row-label-1-' + last_id + '" type="text"  />';
                    field += '<div class="clear"></div>';
                    field += '<div class="clear"></div>';
                    field += '<div class="false-label">Additional Row Label</div>';
                    field += '<div class="fields">';
                } else {
                    field = '';
                    field += '<div class="clear"></div>';
                    field += '<div id="cut-renew-' + last_id + '">';
                    field += '<div id="col-1-' + last_id + '"><label>Columns Label</label>';
                    field += '<input class="fld-title" id="col-label-1-' + last_id + '" type="text"  />';
                    field += '</div>';
                    field += '<div class="clear"></div>';
                    field += '<div class="false-label">Additional Columns</div>';
                    field += '<div class="fields">';

                    if (typeof (values) === 'object') {
                        for (i = 0; i < values.length; i++) {
                            //alert('one');
                            field += gridFieldHtml(values[i]);
                        }
                    }
                    else {
                        //alert('tw0');
                        field += gridFieldHtml('');
                    }
                    field += '<div class="add-area"><a href="#" class="add add_grid">' + opts.messages.add + '</a></div>';
                    field += '</div>';
                    field += '<div class="clear"></div>';
                    field += '<div class="dashed"></div>';
                    field += '<div class="clear"></div>';
                    field += '<label>Row Label</label>';
                    field += '<input class="fld-title" id="row-label-1-' + last_id + '" type="text"  />';
                    field += '<div class="clear"></div>';
                    field += '<div class="clear"></div>';
                    field += '<div class="false-label">Additional Row Label</div>';
                    field += '<div class="fields">';
                }
                if (typeof (values) === 'object') {
                    for (i = 0; i < values.length; i++) {
                        field += gridFieldHtml(values[i]);
                    }
                }
                else {
                    field += gridFieldHtml('');
                }
                field += '<div class="add-area"><a href="#" class="add add_grid">' + opts.messages.add + '</a></div>';
                field += '</div>';
                if (editval == '') {
                    field += '</div>';
                    field += '<div class="clear"></div>';
                    field += '<div class="margin-top"></div>';
                    field += '<label>Field Type</label>';
                    field += $('#mycustom').html();
                    field += '<div class="clear"></div>';
                    help = 'help';
                } else {
                    field += '<div class="margin-top"></div>';
                }
                field += '</div>';
                help = '';

                appendFieldLi(opts.messages.grid, field, required, help, editval);
            };
            // single line text input-line
            var appendTextInput = function (values, required, helping_text) {
                field += '<label>' + opts.messages.label + '</label>';
                field += '<input class="fld-title" id="title-' + last_id + '" type="text" value="' + values + '" />';
                field += '<div class="clear"></div>';
                field += '<label>Help</label>';
                field += '<input class="fld-help" id="help-' + last_id + '" type="text" value="' + helping_text + '" />';
                field += '<div class="clear"></div>';
                field += '<div id="cut-renew-' + last_id + '"></div>';
                field += '<div class="clear"></div>';
                field += '<div class="clear"></div>';
                field += '<label>Field Type</label>';
                field += $('#mycustom').html();
                field += '<div class="clear"></div>';
                help = 'help';
                appendFieldLi(opts.messages.text, field, required, help, '');


            };


            // single line text input-line
            var appendTextEmail = function (values, required, helping_text) {
                field += '<label>' + opts.messages.label + '</label>';
                field += '<input class="fld-title" id="title-' + last_id + '" type="text" value="' + values + '" />';
                field += '<div class="clear"></div>';
                field += '<label>Help</label>';
                field += '<input class="fld-help" id="help-' + last_id + '" type="text" value="' + helping_text + '" />';
                field += '<div class="clear"></div>';
                field += '<div id="cut-renew-' + last_id + '"></div>';
                field += '<div class="clear"></div>';
                field += '<label>Field Type</label>';
                field += $('#mycustom').html();
                field += '<div class="clear"></div>';
                help = 'help';
                appendFieldLi(opts.messages.Email, field, required, help, '');


            };


            // single line text input-line
            var appendRecaptcha = function (values, required, helping_text) {
                field += '<label>' + opts.messages.label + '</label>';
                field += '<input class="fld-title" id="title-' + last_id + '" type="text" value="' + values + '" />';
                field += '<div class="clear"></div>';
                field += '<label>Help</label>';
                field += '<input class="fld-help" id="help-' + last_id + '" type="text" value="' + helping_text + '" />';
                field += '<div class="clear"></div>';
                field += '<div id="cut-renew-' + last_id + '"></div>';
                field += '<div class="clear"></div>';
                field += '<label>Field Type</label>';
                field += $('#mycustom').html();
                field += '<div class="clear"></div>';
                help = 'help';
                appendFieldLi(opts.messages.captcha, field, required, help, '');


            };


            // multi-line textarea
            var appendTextarea = function (values, required, helping_text) {
                field += '<label>' + opts.messages.label + '</label>';
                field += '<input type="text" value="' + values + '" />';
                field += '<div class="clear"></div>';
                field += '<label>Help</label>';
                field += '<input class="fld-title" id="help-' + last_id + '" type="text" value="' + helping_text + '" />';
                field += '<div id="cut-renew-' + last_id + '"></div>';
                field += '<div class="clear"></div>';
                field += '<div class="clear"></div>';
                field += '<label>Field Type</label>';
                field += $('#mycustom').html();
                field += '<div class="clear"></div>';
                help = 'help';
                appendFieldLi(opts.messages.paragraph_field, field, required, help, '');
            };
            // adds a checkbox element
            var appendCheckboxGroup = function (values, options, required, editval, helping_text) {
                var title = '';
                if (typeof (options) === 'object') {
                    title = options[0];
                }
                if (editval == '') {
                    field += '<div class="chk_group">';
                    field += '<div class="frm-fld"><label>' + opts.messages.title + '</label>';
                    field += '<input type="text" name="title" value="' + title + '" /></div>';
                    field += '<div class="clear"></div>';
                    field += '<label>Help</label>';
                    field += '<input class="fld-title" id="help-' + last_id + '" type="text" value="' + helping_text + '" />';
                    field += '<div id="cut-renew-' + last_id + '">';
                    field += '<div class="clear"></div>';
                    field += '<div class="false-label">' + opts.messages.select_options + '</div>';
                    field += '<div class="fields">';
                } else {
                    field = '';
                    field += '<div class="clear"></div>';
                    field += '<div class="false-label">' + opts.messages.select_options + '</div>';
                    field += '<div class="fields">';
                }
                if (typeof (values) === 'object') {
                    for (i = 0; i < values.length; i++) {
                        field += checkboxFieldHtml(values[i]);
                    }
                }
                else {
                    field += checkboxFieldHtml('');
                }
                field += '<div class="add-area"><a href="#" class="add add_ck">' + opts.messages.add + '</a></div>';
                field += '</div>';
                if (editval == '') {
                    field += '</div>';
                    field += '<div class="clear"></div>';
                    field += '<div class="clear"></div>';
                    field += '<label>Field Type</label>';
                    field += $('#mycustom').html();
                    field += '<div class="clear"></div>';
                    help = 'help';
                }
                field += '</div>';
                help = '';

                appendFieldLi(opts.messages.checkbox_group, field, required, help, editval);
            };
            // scale items
            var appendScale = function (values, options, required, editval) {
                var title = '';
                if (typeof (options) === 'object') {
                    title = options[0];
                }
                if (editval == '') {
                    field += '<div class="chk_group">';
                    field += '<div class="frm-fld"><label>' + opts.messages.title + '</label>';
                    field += '<input type="text" name="title" value="' + title + '" /></div>';
                    field += '<div class="clear"></div>';
                    field += '<label>Help</label>';
                    field += '<input class="fld-title" id="help-' + last_id + '" type="text" value="' + values + '" />';
                    field += '<div class="clear"></div>';
                    field += '<div id="cut-renew-' + last_id + '">';
                    field += '<h4>Scale Options:</h4>';
                    field += '<label>From:</label>';
                    field += '<input class="fld-title" id="from-scale-' + last_id + '" type="text" value="' + values + '" />';
                    field += '<div class="clear"></div>';
                    field += '<label>To:</label>';
                    field += '<input class="fld-title" id="to-scale-' + last_id + '" type="text" value="' + values + '" />';
                    field += '<div class="clear"></div>';
                    field += '<label>From Label:</label>';
                    field += '<input class="fld-title" id="from-label-' + last_id + '" type="text" value="' + values + '" />';
                    field += '<div class="clear"></div>';
                    field += '<label>To Label:</label>';
                    field += '<input class="fld-title" id="to-label-' + last_id + '" type="text" value="' + values + '" />';
                    field += '</div>';
                    field += '<div class="clear"></div>';
                    field += '<div id="cut-renew-' + last_id + '">';
                    field += '<div class="clear"></div>';
                } else {
                    field = '';
                    field += '<div class="clear"></div>';
                    field += '<div id="cut-renew-' + last_id + '">';
                    field += '<h4>Scale options:</h4>';
                    field += '<label>From:</label>';
                    field += '<input class="fld-title" id="from-scale-' + last_id + '" type="text" value="' + values + '" />';
                    field += '<div class="clear"></div>';
                    field += '<label>To:</label>';
                    field += '<input class="fld-title" id="to-scale-' + last_id + '" type="text" value="' + values + '" />';
                    field += '<div class="clear"></div>';
                    field += '<label>From Label:</label>';
                    field += '<input class="fld-title" id="from-label-' + last_id + '" type="text" value="' + values + '" />';
                    field += '<div class="clear"></div>';
                    field += '<label>To Label:</label>';
                    field += '<input class="fld-title" id="to-label-' + last_id + '" type="text" value="' + values + '" />';
                    field += '</div>';
                    field += '<div class="clear"></div>';
                }
                if (editval == '') {
                    field += '</div>';
                    field += '<div class="clear"></div>';
                    field += '<div class="clear"></div>';
                    field += '<label>Field Type</label>';
                    field += $('#mycustom').html();
                    field += '<div class="clear"></div>';
                    help = 'help';
                }
                field += '</div>';
                help = '';

                appendFieldLi(opts.messages.scale, field, required, help, editval);
            };
            // Checkbox field html, since there may be multiple
            var checkboxFieldHtml = function (values) {
                var checked = false;
                var value = '';
                if (typeof (values) === 'object') {
                    value = values[0];
                    checked = false;
                }
                field = '';
                field += '<div>';
                field += '<input type="checkbox"' + (checked ? ' checked="checked"' : '') + ' />';
                field += '<input type="text" value="' + value + '" />';
                field += '<a href="#" class="remove" title="' + opts.messages.remove_message + '">' + opts.messages.remove + '</a>';
                field += '</div>';
                return field;
            };
            // adds a grid element
            var gridFieldHtml = function (values) {
                var checked = false;
                var value = '';
                if (typeof (values) === 'object') {
                    value = values[0];
                    checked = ( values[1] === 'false' || values[1] === 'undefined' ) ? false : true;
                }
                field = '';
                field += '<div>';
                field += '<input type="text" value="' + value + '" />';
                field += '<a href="#" class="remove" title="' + opts.messages.remove_message + '">' + opts.messages.remove + '</a>';
                field += '</div>';
                return field;
            };
            // adds a radio element
            var appendRadioGroup = function (values, options, required, editval, helping_text) {
                var title = '';
                if (typeof (options) === 'object') {
                    title = options[0];
                }
                if (editval == '') {
                    field += '<div class="rd_group">';
                    field += '<div class="frm-fld"><label>' + opts.messages.title + '</label>';
                    field += '<input type="text" name="title" value="' + title + '" /></div>';
                    field += '<div class="clear"></div>';
                    field += '<label>Help</label>';
                    field += '<input class="fld-title" id="help-' + last_id + '" type="text" value="' + helping_text + '" />';
                    field += '<div id="cut-renew-' + last_id + '">';
                    field += '<div class="clear"></div>';
                    field += '<div class="false-label">' + opts.messages.select_options + '</div>';
                    field += '<div class="fields">';
                } else {
                    field = '';
                    field += '<div class="clear"></div>';
                    field += '<div class="false-label">' + opts.messages.select_options + '</div>';
                    field += '<div class="fields">';
                }
                if (typeof (values) === 'object') {
                    for (i = 0; i < values.length; i++) {
                        field += radioFieldHtml(values[i], 'frm-' + last_id + '-fld');
                    }
                }
                else {
                    field += radioFieldHtml('', 'frm-' + last_id + '-fld');
                }
                field += '<div class="add-area"><a href="#" class="add add_rd">' + opts.messages.add + '</a></div>';
                field += '</div>';
                if (editval == '') {
                    field += '</div>';
                    field += '<div class="clear"></div>';
                    field += '<div class="clear"></div>';
                    field += '<label>Field Type</label>';
                    field += $('#mycustom').html();
                    field += '<div class="clear"></div>';
                    help = 'help';
                }
                field += '</div>';
                help = '';
                appendFieldLi(opts.messages.radio_group, field, required, help, editval);
            };
            // Radio field html, since there may be multiple
            var radioFieldHtml = function (values, name) {
                var checked = false;
                var value = '';
                if (typeof (values) === 'object') {
                    value = values[0];
                    checked = ( values[1] === 'false' || values[1] === 'undefined' ) ? false : true;
                }
                field = '';
                field += '<div>';
                field += '<input type="radio"' + (checked ? ' checked="checked"' : '') + ' name="radio_' + name + '" />';
                field += '<input type="text" value="' + value + '" />';
                field += '<a href="#" class="remove" title="' + opts.messages.remove_message + '">' + opts.messages.remove + '</a>';
                field += '</div>';
                return field;
            };
            // adds a select/option element
            var appendSelectList = function (values, options, required, editval, helping_text) {
                var multiple = false;
                var title = '';
                if (typeof (options) === 'object') {
                    title = options[0];
                    multiple = options[1] === 'true' ? true : false;
                }
                if (editval == '') {
                    field += '<div class="opt_group">';
                    field += '<div class="frm-fld"><label>' + opts.messages.title + '</label>';
                    field += '<input type="text" name="title" value="' + title + '" /></div>';
                    field += '<div class="clear"></div>';
                    field += '<label>Help</label>';
                    field += '<input class="fld-title" id="help-' + last_id + '" type="text" value="' + helping_text + '" />';
                    field += '<div id="cut-renew-' + last_id + '">';
                    field += '<div class="clear"></div>';
                    field += '<div class="false-label">' + opts.messages.select_options + '</div>';
                    field += '<div class="fields">';
                    //field += '<input type="checkbox" name="multiple"' + (multiple ? 'checked="checked"' : '') + '>';
                    //field += '<label class="auto">' + opts.messages.selections_message + '</label>';
                } else {
                    field = '';
                    field += '<div class="clear"></div>';
                    field += '<div class="false-label">' + opts.messages.select_options + '</div>';
                    field += '<div class="fields">';
                    field += '<input type="checkbox" name="multiple"' + (multiple ? 'checked="checked"' : '') + '>';
                    field += '<label class="auto">' + opts.messages.selections_message + '</label>';
                }
                if (typeof (values) === 'object') {
                    for (i = 0; i < values.length; i++) {
                        field += selectFieldHtml(values[i], multiple);
                    }
                }
                else {
                    field += selectFieldHtml('', multiple);
                }
                field += '<div class="add-area"><a href="#" class="add add_opt">' + opts.messages.add + '</a></div>';
                field += '</div>';
                if (editval == '') {
                    field += '</div>';
                    field += '<div class="clear"></div>';
                    //field +='<div class="clear"></div>';
                    //field += '<label>Select Box</label>';
                    //field += $('#mycustom').html();
                    //field += '<div class="clear"></div>';
                    help = 'help';
                }
                field += '</div>';
                help = '';
                appendFieldLi(opts.messages.select, field, required, help, editval);
            };
            // Select field html, since there may be multiple
            var selectFieldHtml = function (values, multiple) {
                if (multiple) {
                    return checkboxFieldHtml(values);
                }
                else {
                    return radioFieldHtml(values);
                }
            };
            // Appends the new field markup to the editor
            var appendFieldLi = function (title, field_html, required, help, editval) {

                if (required == 1) {
                    required = true;
                } else {
                    required = false;
                }
                //alert(title);
                var li = '';
                // if(editval==''){
                if (editval == '') {

                    li += '<li id="frm-' + last_id + '-item" class="' + field_type + '">';
                    li += '<div class="legend">';
                    li += '<a id="frm-' + last_id + '" class="toggle-form" href="#">' + opts.messages.hide + '</a> ';

                    li += '<a id="del_' + last_id + '" class="del-button delete-confirm" href="#" title="' + opts.messages.remove_message + '"><span>' + opts.messages.remove + '</span></a>';
                    //}
                    li += '<strong id="txt-title-' + last_id + '">' + title + '</strong></div>';
                    li += '<div id="frm-' + last_id + '-fld" class="frm-holder">';
                    li += '<div class="frm-elements">';
                    if (title != "Recaptcha") {
                        li += '<div class="frm-fld"><label for="required-' + last_id + '">' + opts.messages.required + '</label>';
                        li += '<input class="required" type="checkbox" value="1" name="required-' + last_id + '" id="required-' + last_id + '"' + (required ? ' checked="checked"' : '') + ' /></div>';
                    }
                    li += field;
                    li += '</div>';
                    li += '</div>';
                    li += '</li>';
                } else {
                    li += '<div id="frm-' + last_id + '-fld" class="frm-holder">';
                    li += field;
                    li += '</div>';
                }
                if (editval != '') {
                    $('#cut-renew-' + editval).html(li);
                    $('#cut-renew-' + editval).slideDown('slow');

                } else {
                    $(ul_obj).append(li);
                    $('#frm-' + last_id + '-item').hide();
                    $('#frm-' + last_id + '-item').animate({
                        opacity: 'show',
                        height: 'show'
                    }, 'slow');
                    $('#frm-' + last_id + '-item .mycust-field').attr('id', 'mycust-' + last_id);
                    //alert(title);
                    if (title == 'Select List') {
                        $('#frm-' + last_id + '-item .mycust-field').html('<div class="mycust-field-input"><input type="checkbox" checked="checked"><span>Multiple</span><br><input type="checkbox" checked="checked"><span>Select List</span></div>');

                    } else if (title == 'Radio Group') {
                        $('#frm-' + last_id + '-item .mycust-field').html('<div class="mycust-field-input"><input type="radio" checked="checked"><span>Radio Box</span></div>');


                    } else if (title == 'Checkbox Group') {
                        $('#frm-' + last_id + '-item .mycust-field').html('<div class="mycust-field-input"><input type="checkbox" checked="checked"><span>Check Box</span></div>');


                    } else if (title == 'Text Field') {
                        $('#frm-' + last_id + '-item .mycust-field').html('<input type="text" disabled >');


                    } else if (title == 'Email Field') {
                        $('#frm-' + last_id + '-item .mycust-field').html('<input type="text" disabled >');
                    }
                    else if (title == 'Paragraph Field') {
                        $('#frm-' + last_id + '-item .mycust-field').html('<textarea  disabled class="mycust-field-input" >Text Area</textarea>');
                    } else if (title == 'Grid') {
                        $('#frm-' + last_id + '-item .mycust-field').html('<div class="grid-view"><div class="grid-row"></div><div class="grid-row">One</div><div class="grid-row">Two</div><div class="grid-row">Three</div><div class="grid-row">Four</div><div class="grid-row">One</div><div class="grid-row"><input type="radio" checked></div><div class="grid-row"><input type="radio" disabled></div><div class="grid-row"><input type="radio" disabled></div><div class="grid-row"><input type="radio" disabled></div><div class="grid-row">Two</div><div class="grid-row"><input type="radio" disabled></div><div class="grid-row"><input type="radio" checked></div><div class="grid-row"><input type="radio" disabled></div><div class="grid-row"><input type="radio" disabled></div><div>');

                    } else if (title == 'Scale') {
                        $('#frm-' + last_id + '-item .mycust-field').html('<div class="scale-view"><div class="scale-row"></div><div class="scale-row">One</div><div class="scale-row">Two</div><div class="scale-row">Three</div><div class="scale-row">Four</div><div class="scale-row"></div><div class="scale-row">From</div><div class="scale-row"><input type="radio" checked></div><div class="scale-row"><input type="radio" disabled></div><div class="scale-row"><input type="radio" disabled></div><div class="scale-row"><input type="radio" disabled></div><div class="scale-row">To</div><div>');
                    }

                    $('#mycust-' + last_id).children().addClass('mycust-field-input');
                    if (title != 'Grid' && title != 'Scale') {
                        $('#mycust-' + last_id).children().css('width', '12%');
                    }

                    $('#mycust-' + last_id).children().val(title);
                    var mytitles = '.' + title.replace(' ', '_');
                    var mytitlclass = mytitles.toLowerCase();
                    $('#frm-' + last_id + '-item .frmb-control-new ' + mytitlclass).attr('selected', true);

                    var myids = last_id;
                    $('#frm-' + myids + '-item .frmb-control-new').change(function () {
                        var myidds = $(this).parent().parent().parent().attr('id');
                        changemystyle(myidds.replace('frm-', '').replace('-item', '').replace('-fld', ''), $(this).val());
                    });
                    myids = '';
                    //alert($('#mycust-'+last_id).html());
                    last_id++;
                }
            };
            // handle edit inplace  links
            var changemystyle = function (ids, values) {
                if (values == 'Text Field') {
                    create_input(ids, values);
                } else if (values == 'Email Field') {
                    create_email(ids, values);
                } else if (values == 'Recaptcha') {
                    create_captcha(ids, values);
                } else if (values == 'Paragraph') {
                    create_textarea(ids, values);
                } else if (values == 'Checkboxes') {
                    create_checkbox(ids, values);
                } else if (values == 'Radio') {
                    create_radio(ids, values);
                } else if (values == 'Select List') {
                    create_select(ids, values);
                } else if (values == 'Grid') {
                    create_grid(ids, values);
                } else if (values == 'Scale') {
                    create_scale(ids, values);
                }
                ;
            };
            // handle edit inplace  input text inline
            var create_input = function (ids, values) {
                $('#cut-renew-' + ids).slideUp('slow');
                $('#cut-renew-' + ids).html('');
                $('#mycust-' + ids).html('<input type="text" value="' + values + '" disabled class="mycust-field-input" style="width:12%">');
                $('#txt-title-' + ids).html(values);
                $('#frm-' + ids + '-item').removeClass($('#frm-' + ids + '-item').attr('class'));
                $('#frm-' + ids + '-item').addClass('input_text');
            };
            // handle edit inplace  input email inline
            var create_email = function (ids, values) {
                $('#cut-renew-' + ids).slideUp('slow');
                $('#cut-renew-' + ids).html('');
                $('#mycust-' + ids).html('<input type="text" value="' + values + '" disabled class="mycust-field-input" style="width:12%">');
                $('#txt-title-' + ids).html(values);
                $('#frm-' + ids + '-item').removeClass($('#frm-' + ids + '-item').attr('class'));
                $('#frm-' + ids + '-item').addClass('input_email');
            };
            // handle edit inplace  captacha inline
            var create_captcha = function (ids, values) {
                $('#cut-renew-' + ids).slideUp('slow');
                $('#cut-renew-' + ids).html('');
                //$('#mycust-'+ids).html('<input type="text" value="'+values+'" disabled class="mycust-field-input" style="width:12%">');
                $('#txt-title-' + ids).html(values);
                $('#frm-' + ids + '-item').removeClass($('#frm-' + ids + '-item').attr('class'));
                $('#frm-' + ids + '-item').addClass('recaptcha');
            };
            // handle edit inplace  input textarea
            var create_textarea = function (ids, values) {
                $('#cut-renew-' + ids).slideUp('slow');
                $('#cut-renew-' + ids).html('');
                $('#mycust-' + ids).html('<textarea  disabled class="mycust-field-input" >' + values + '</textarea>');
                $('#txt-title-' + ids).html(values);
                $('#frm-' + ids + '-item').removeClass($('#frm-' + ids + '-item').attr('class'));
                $('#frm-' + ids + '-item').addClass('textarea');
            };
            // handle edit inplace checkbox
            var create_checkbox = function (ids, values) {
                $('#cut-renew-' + ids).slideUp('slow');
                $('#cut-renew-' + ids).html('');
                //alert('');
                appendCheckboxGroup('', '', '', ids);
                $('#mycust-' + ids).html('<div class="mycust-field-input"><input type="checkbox" checked="checked"><span>' + values + '</span></div>');
                $('#txt-title-' + ids).html(values);
                $('#frm-' + ids + '-item').removeClass($('#frm-' + ids + '-item').attr('class'));
                $('#frm-' + ids + '-item').addClass('checkbox');
            };
            // handle edit inplace grid
            var create_grid = function (ids, values) {
                $('#cut-renew-' + ids).slideUp('slow');
                $('#cut-renew-' + ids).html('');
                appendGrid('', '', '', ids);
                $('#mycust-' + ids).html('<div class="grid-view mycust-field-input"><div class="grid-row"></div><div class="grid-row">One</div><div class="grid-row">Two</div><div class="grid-row">Three</div><div class="grid-row">Four</div><div class="grid-row">One</div><div class="grid-row"><input type="radio" checked></div><div class="grid-row"><input type="radio" disabled></div><div class="grid-row"><input type="radio" disabled></div><div class="grid-row"><input type="radio" disabled></div><div class="grid-row">Two</div><div class="grid-row"><input type="radio" disabled></div><div class="grid-row"><input type="radio" checked></div><div class="grid-row"><input type="radio" disabled></div><div class="grid-row"><input type="radio" disabled></div><div>');
                $('#txt-title-' + ids).html(values);
                $('#frm-' + ids + '-item').removeClass($('#frm-' + ids + '-item').attr('class'));
                $('#frm-' + ids + '-item').addClass('grid');
            };
            // handle edit inplace scale
            var create_scale = function (ids, values) {
                $('#cut-renew-' + ids).slideUp('slow');
                $('#cut-renew-' + ids).html('');
                appendScale('', '', '', ids);
                $('#mycust-' + ids).html('<div class="scale-view mycust-field-input"><div class="scale-row"></div><div class="scale-row">One</div><div class="scale-row">Two</div><div class="scale-row">Three</div><div class="scale-row">Four</div><div class="scale-row"></div><div class="scale-row">From</div><div class="scale-row"><input type="radio" checked></div><div class="scale-row"><input type="radio" disabled></div><div class="scale-row"><input type="radio" disabled></div><div class="scale-row"><input type="radio" disabled></div><div class="scale-row">To</div><div>');

                $('#txt-title-' + ids).html(values);
                $('#frm-' + ids + '-item').removeClass($('#frm-' + ids + '-item').attr('class'));
                $('#frm-' + ids + '-item').addClass('scale');
            };
            // handle edit inplace radio
            var create_radio = function (ids, values) {
                $('#cut-renew-' + ids).slideUp('slow');
                $('#cut-renew-' + ids).html('');
                appendRadioGroup('', '', '', ids);
                $('#mycust-' + ids).html('<div class="mycust-field-input"><input type="radio" checked="checked"><span>' + values + '</span></div>');
                $('#txt-title-' + ids).html(values);
                $('#frm-' + ids + '-item').removeClass($('#frm-' + ids + '-item').attr('class'));
                $('#frm-' + ids + '-item').addClass('radio');
            };
            // handle edit inplace select list
            var create_select = function (ids, values) {
                $('#cut-renew-' + ids).slideUp('slow');
                $('#cut-renew-' + ids).html('');
                appendSelectList('', '', '', ids);
                $('#mycust-' + ids).html('<div class="mycust-field-input"><input type="checkbox" checked="checked"><span>Multiple</span><br><input type="checkbox" checked="checked"><span>' + values + '</span></div>');
                $('#txt-title-' + ids).html(values);
                $('#frm-' + ids + '-item').removeClass($('#frm-' + ids + '-item').attr('class'));
                $('#frm-' + ids + '-item').addClass('select');
            };
            // handle field delete links
            $('.remove').live('click', function () {
                $(this).parent('div').animate({
                    opacity: 'hide',
                    height: 'hide',
                    marginBottom: '0px'
                }, 'fast', function () {
                    $(this).remove();
                });
                return false;
            });
            // handle field display/hide
            $('.toggle-form').live('click', function () {
                var target = $(this).attr("id");
                if ($(this).html() === opts.messages.hide) {
                    $(this).removeClass('open').addClass('closed').html(opts.messages.show);
                    $('#' + target + '-fld').animate({
                        opacity: 'hide',
                        height: 'hide'
                    }, 'slow');
                    return false;
                }
                if ($(this).html() === opts.messages.show) {
                    $(this).removeClass('closed').addClass('open').html(opts.messages.hide);
                    $('#' + target + '-fld').animate({
                        opacity: 'show',
                        height: 'show'
                    }, 'slow');
                    return false;
                }
                return false;
            });
            // handle delete confirmation
            $('.delete-confirm').live('click', function () {
                var delete_id = $(this).attr("id").replace(/del_/, '');
                if (confirm($(this).attr('title'))) {
                    $('#frm-' + delete_id + '-item').animate({
                        opacity: 'hide',
                        height: 'hide',
                        marginBottom: '0px'
                    }, 'slow', function () {
                        $(this).remove();
                    });
                }
                return false;
            });
            // Attach a callback to add new checkboxes
            $('.add_ck').live('click', function () {
                $(this).parent().before(checkboxFieldHtml());
                return false;
            });
            // Attach a callback to add new grid
            $('.add_grid').live('click', function () {
                $(this).parent().before(gridFieldHtml());
                return false;
            });
            // Attach a callback to add new options
            $('.add_opt').live('click', function () {
                $(this).parent().before(selectFieldHtml('', false));
                return false;
            });
            // Attach a callback to add new radio fields
            $('.add_rd').live('click', function () {
                $(this).parent().before(radioFieldHtml(false, $(this).parents('.frm-holder').attr('id')));
                return false;
            });
            // saves the serialized data to the server
            var save = function () {

                var serialData = $(ul_obj).serializeFormList();
                if (serialData) {
                    if (opts.save_url) {
                        $.ajax({
                            type: "POST",
                            url: opts.save_url,
                            data: serialData,
                            success: function (xml) {

                                if (xml != '') {
                                    $(".alert-success").css('display', 'none');
                                    $(".alert-danger").css('display', 'block');
                                    $(".alert-danger").html(xml);
                                    $('html,body').animate({
                                            scrollTop: $(".alert").offset().top
                                        },
                                        'slow');
                                } else {
                                    $(".alert-danger").css('display', 'none');
                                    $(".alert-success").css('display', 'block');
                                    $(".alert-success").html('Form has been Created Successfully');
                                    $('html,body').animate({
                                            scrollTop: $(".alert").offset().top
                                        },
                                        'slow');
                                    window.location = 'backend.php?r=forms';
                                }
                            }
                        });
                    }
                } else {
                    //alert('Error Found');
                }

            };
        });
    };
})(jQuery);
(function ($) {
    $.fn.serializeFormList = function (options) {
        // Extend the configuration options with user-provided

        var title = $('#title').val();
        var slug = $('#slug').val();
        var contact_email = $('#contact_email').val();
        var error_string = '';
        if (title == '') {
            error_string = 'Please Enter Form Title<br>';
        }
        if (slug == '') {
            error_string += 'Please Enter Form Slug<br>';
        }
        if (contact_email == '') {
            error_string += 'Please Enter Form Contact Email<br>';
        }

        if (error_string == '') {
            var defaults = {
                prepend: 'ul',
                is_child: false,
                attributes: ['class']
            };
            var opts = $.extend(defaults, options);
            if (!opts.is_child) {
                opts.prepend = '&' + opts.prepend;
            }
            var serialStr = '';
            // Begin the core plugin
            this.each(function () {
                var ul_obj = this;
                var li_count = 0;
                var c = 1;
                $(this).children().each(function () {
                    for (att = 0; att < opts.attributes.length; att++) {
                        var key = (opts.attributes[att] === 'class' ? 'cssClass' : opts.attributes[att]);
                        serialStr += opts.prepend + '[' + li_count + '][' + key + ']=' + encodeURIComponent($(this).attr(opts.attributes[att]));
                        // append the form field values
                        if (opts.attributes[att] === 'class') {
                            serialStr += opts.prepend + '[' + li_count + '][required]=' + encodeURIComponent($('#' + $(this).attr('id') + ' input.required').attr('checked'));

                            switch ($(this).attr(opts.attributes[att])) {
                                case 'input_text':
                                    c = 1;
                                    $('#' + $(this).attr('id') + ' input[type=text]').each(function () {
                                        if ($(this).attr('name') === 'title') {
                                            serialStr += opts.prepend + '[' + li_count + '][title]=' + encodeURIComponent($(this).val());
                                        }
                                        else {
                                            serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][value]=' + encodeURIComponent($(this).val());
                                        }
                                        c++;
                                    });
                                    break;
                                case 'input_email':
                                    c = 1;
                                    $('#' + $(this).attr('id') + ' input[type=text]').each(function () {
                                        if ($(this).attr('name') === 'title') {
                                            serialStr += opts.prepend + '[' + li_count + '][title]=' + encodeURIComponent($(this).val());
                                        }
                                        else {
                                            serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][value]=' + encodeURIComponent($(this).val());
                                        }
                                        c++;
                                    });
                                    break;

                                case 'recaptcha':
                                    c = 1;
                                    $('#' + $(this).attr('id') + ' input[type=text]').each(function () {
                                        if ($(this).attr('name') === 'title') {
                                            serialStr += opts.prepend + '[' + li_count + '][title]=' + encodeURIComponent($(this).val());
                                        }
                                        else {
                                            serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][value]=' + encodeURIComponent($(this).val());
                                            //serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][baseline]=' + $(this).prev().attr('checked');
                                        }
                                        c++;
                                    });
                                    break;

                                case 'textarea':
                                    c = 1;
                                    $('#' + $(this).attr('id') + ' input[type=text]').each(function () {
                                        if ($(this).attr('name') === 'title') {
                                            serialStr += opts.prepend + '[' + li_count + '][title]=' + encodeURIComponent($(this).val());
                                        }
                                        else {
                                            serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][value]=' + encodeURIComponent($(this).val());
                                        }
                                        c++;
                                    });
                                    break;
                                case 'checkbox':
                                    c = 1;
                                    $('#' + $(this).attr('id') + ' input[type=text]').each(function () {
                                        if ($(this).attr('name') === 'title') {
                                            serialStr += opts.prepend + '[' + li_count + '][title]=' + encodeURIComponent($(this).val());
                                        }
                                        else {
                                            serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][value]=' + encodeURIComponent($(this).val());
                                            serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][baseline]=' + $(this).prev().attr('checked');
                                        }
                                        c++;
                                    });
                                    break;
                                case 'radio':
                                    c = 1;
                                    $('#' + $(this).attr('id') + ' input[type=text]').each(function () {
                                        if ($(this).attr('name') === 'title') {
                                            serialStr += opts.prepend + '[' + li_count + '][title]=' + encodeURIComponent($(this).val());
                                        }
                                        else {
                                            serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][value]=' + encodeURIComponent($(this).val());
                                            serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][baseline]=' + $(this).prev().attr('checked');
                                        }
                                        c++;
                                    });
                                    break;
                                case 'select':
                                    c = 1;
                                    serialStr += opts.prepend + '[' + li_count + '][multiple]=' + $('#' + $(this).attr('id') + ' input[name=multiple]').attr('checked');
                                    $('#' + $(this).attr('id') + ' input[type=text]').each(function () {
                                        if ($(this).attr('name') === 'title') {
                                            serialStr += opts.prepend + '[' + li_count + '][title]=' + encodeURIComponent($(this).val());
                                        }
                                        else {
                                            serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][value]=' + encodeURIComponent($(this).val());
                                        }
                                        c++;
                                    });
                                    break;
                                case 'grid':

                                    c = 1;
                                    serialStr += opts.prepend + '[' + li_count + '][multiple]=' + $('#' + $(this).attr('id') + ' input[name=multiple]').attr('checked');
                                    $('#' + $(this).attr('id') + ' input[type=text]').each(function () {
                                        if ($(this).attr('name') === 'title') {
                                            serialStr += opts.prepend + '[' + li_count + '][title]=' + encodeURIComponent($(this).val());
                                        }
                                        else {
                                            serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][value]=' + encodeURIComponent($(this).val());
                                        }
                                        c++;
                                    });
                                    break;
                                case 'scale':

                                    c = 1;
                                    serialStr += opts.prepend + '[' + li_count + '][multiple]=' + $('#' + $(this).attr('id') + ' input[name=multiple]').attr('checked');
                                    $('#' + $(this).attr('id') + ' input[type=text]').each(function () {
                                        if ($(this).attr('name') === 'title') {
                                            serialStr += opts.prepend + '[' + li_count + '][title]=' + encodeURIComponent($(this).val());
                                        }
                                        else {
                                            serialStr += opts.prepend + '[' + li_count + '][values][' + c + '][value]=' + encodeURIComponent($(this).val());
                                        }
                                        c++;
                                    });
                                    break;
                            }
                        }
                    }
                    li_count++;
                });
            });
            serialStr += "&YII_CSRF_TOKEN=" + $("#YII_CSRF_TOKEN").val();
            serialStr += "&form_title=" + $("#title").val();
            serialStr += "&slug=" + $("#slug").val();
            serialStr += "&contact_email=" + $("#contact_email").val();
            serialStr += "&thankyou_text=" + $("#form-thanks").val();
            serialStr += "&description=" + $("#form-descp").val();
            serialStr += "&auto_reply=" + CKEDITOR.instances['auto_reply'].getData();
            return (serialStr);
        } else {


            $(".alert-danger").css('display', 'block');
            $(".alert-danger").html(error_string);

            $('html,body').animate({
                    scrollTop: $(".alert").offset().top
                },
                'slow');

            return false;
        }

    };
})(jQuery);