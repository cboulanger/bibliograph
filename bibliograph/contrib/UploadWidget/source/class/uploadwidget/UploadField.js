/* ************************************************************************

   qooxdoo - the new era of web development

   http://qooxdoo.org

   Copyright:
     2007 Visionet GmbH, http://www.visionet.de

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Dietrich Streifert (level420)
   
   Contributors:
     * Petr Kobalicek (e666e)     
     * Tobi Oetiker (oetiker)

************************************************************************ */

/**
 * UploadField: A textfield which holds the filename of the file which
 * should be uploaded and a button which allows selecting the file via the native
 * file selector, looking somewhat similar to what people are used to
 * from web browsers.
 *
 * @childControl button {uploadwidget.UploadButton} upload button.
 * @childControl textfield {qx.ui.form.TextField} read-only textfield next to the upload button
 *
 */
qx.Class.define("uploadwidget.UploadField",
{
  extend : qx.ui.core.Widget,

  // --------------------------------------------------------------------------
  // [Constructor]
  // --------------------------------------------------------------------------

  /**
   * @param fieldName {String} upload field name
   * @param label {String} button label
   * @param icon {String} icon path
   */
  construct : function(fieldName, label, icon, command)
  {
    this.base(arguments);
    this._setLayout(new qx.ui.layout.Grid(2,2)
                    .setColumnFlex(0,1)
                    .setColumnAlign(0,'left','middle')
                    .setColumnAlign(1,'left','middle')
    );
    this.getChildControl('textfield');
    if (fieldName) {
        this.setFieldName(fieldName);
    }
    if (label) {
        this.setLabel(label);
    }
    if (icon) {
        this.setIcon(icon);
    }
    if (command) {
        this.getChildControl('button').setCommand(command);
    }
  },

  // --------------------------------------------------------------------------
  // [Properties]
  // --------------------------------------------------------------------------

  properties :
  {
    /**
     * The name which is assigned to the form
     */
    fieldName :
    {
      check : "String",
      init  : "",
      apply : "_applyFieldName"
    },

    /**
     * The value which is assigned to the form
     */
    fileName :
    {
      check : "String",
      init : "",
      apply : "_applyFileName",
      event : "changeFileName",
      nullable: true
    },

    /**
     * The value which is assigned to the form
     */
    fileSize :
    {
      check : "Integer",
      init : "",
      nullable: true
    },

    /**
     * The value which is assigned to the form
     */
    label :
    {
      check : "String",
      init : "",
      apply : "_applyLabel",
      event : "changeLabel"
    },
    /**
     * The icon on the upload button
     */
    icon :
    {
      check : "String",
      init : "",
      apply : "_applyIcon",
      event : "changeIcon"
    }
  }, 

  // --------------------------------------------------------------------------
  // [Members]
  // --------------------------------------------------------------------------

  members :
  {

    // ------------------------------------------------------------------------
    // [Modifiers]
    // ------------------------------------------------------------------------
    
    /**
     * Value modifier. Sets the value of both the text field and
     * the UploadButton. The setValue modifier of UploadButton
     * throws an exception if the value is not an empty string.
     *
     * @type member
     * @param value {var} Current value
     * @param old {var} Previous value
     */
    _applyFileName : function(value, old)
    {
      
      // the value of the file can not be changed,
      // so do not try
      // this.getChildControl('button').setFileName(value);
      this.getChildControl('textfield').setValue(value);
    },


    /**
     * Upload parameter value modifier. Sets the name attribute of the
     * the hidden input type=file element in UploadButton which should.
     * This name is the form submission parameter name.
     *
     * @type member
     * @param value {var} Current value
     * @param old {var} Previous value
     */
    _applyFieldName : function(value, old)
    {
        this.getChildControl('button').setFieldName(value);
    },
    /**
     * Upload butotn label modifier.
     *
     * @type member
     * @param value {var} Current value
     * @param old {var} Previous value
     */
    _applyLabel : function(value, old)
    {
        this.getChildControl('button').setLabel(value);
    },
    /**
     * Upload button icon modifier.
     *
     * @type member
     * @param value {var} Current value
     * @param old {var} Previous value
     */
    _applyIcon : function(value, old)
    {
        this.getChildControl('button').setIcon(value);
    },

    // ------------------------------------------------------------------------
    // [Setters / Getters]
    // ------------------------------------------------------------------------
   
    /**
     * Returns component text field widget.
     * @deprecated Use getChildControl('textfield');
     */
    getTextField: function()
    {
      return this.getChildControl('textfield');
    },

    /**
     * Returns component button widget. 
     * @deprecated Use getChildControl('textfield')
     */
    getButton: function()
    {
      return this.getChildControl('button');
    },
    
    // ------------------------------------------------------------------------
    // [Event Handlers]
    // ------------------------------------------------------------------------
    
    /**
     * If the user select a file by clicking the button, the value of
     * the input type=file tag of the UploadButton changes and
     * the text field is set with the value of the selected filename.
     *
     * @type member
     * @param e {Event} change value event data
     * @return {void}
     */
    _onChangeFileName : function(e)
    {
      var value = e.getData();
      this.setFileName(value);
      this.setFileSize(this.getChildControl('button').getFileSize());
    },

    // ------------------------------------------------------------------------
    // [Child Controls]
    // ------------------------------------------------------------------------

    /**
     * Create the widget child controls.
     */

    _createChildControlImpl: function(id) {
      var control;
      switch(id) {
      case "button":
        control = new uploadwidget.UploadButton(this.getFieldName(), this.getLabel(), this.getIcon());
        this._add(control,{column: 1,row:0});
        control.addListener("changeFileName", this._onChangeFileName, this);        
        break;
      case "textfield":        
        control = new qx.ui.form.TextField();
        control.set({
            readOnly: true
        });
        this._add(control,{column: 0,row:0});
        break;
      }
      return control || this.base(arguments, id);
    }
  }
});
