CKEDITOR.plugins.add('shortcode',
    {
        init: function (editor) {
            var htmlData;
            $.get('/backend.php?r=page/default/shortcodes', function (data) {
                htmlData = data;
            });

            editor.addCommand('shortcodeDialog', new CKEDITOR.dialogCommand('shortcodeDialog'));
            editor.ui.addButton('ShortCode',
                {
                    label: 'Insert ShortCode',
                    command: 'shortcodeDialog',
                    icon: this.path + 'images/shortcode.gif'
                });
            CKEDITOR.dialog.add('shortcodeDialog', function (editor) {
                return {
                    title: 'Insert Short Code',
                    minWidth: 400,
                    minHeight: 200,
                    contents: [
                        {
                            id: 'tab1',
                            label: 'Basic Settings',
                            elements: [
                                {
                                    type: "html",
                                    html: htmlData,
                                },
                            ]
                        },
                    ],
                    onOk: function () {
                        var dialog = this;

                        var selectedVal = "";
                        var selected = $("input[type='radio']:checked");
                        if (selected.length > 0) {
                            selectedValue = selected.val();
                            editor.insertHtml(selectedValue);
                        } else {
                            //alert('Please select any code');
                        }
                    }
                };
            });
        }
    });