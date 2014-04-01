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
 * An upload widget implementation capable of holding multiple upload buttons
 * and upload fields.
 * 
 * Each upload form creates an iframe which is used as a target for form submit.
 * 
 *
 */
qx.Class.define("uploadwidget.UploadForm",
{
  extend : qx.ui.container.Composite,

  // --------------------------------------------------------------------------
  // [Constructor]
  // --------------------------------------------------------------------------

  /**
   * @param name {String} form name ({@link #name}).
   * @param url {String} url for form submission ({@link #url}).
   * @param encoding {String} encoding for from submission. This is an instantiation only parameter and defaults to multipart/form-data
   */
  construct: function(name, url, encoding)
  {
    this.base(arguments);
    
    // Apply initial values
    if (name) {
        this.setName(name);
    }
    if (url) {
        this.setUrl(url);
    }

    //this.setHtmlProperty("encoding", encoding || "multipart/form-data");
    var el = this.getContentElement();
    el.setAttributes({
      enctype: encoding || "multipart/form-data",
      method: "POST"
    });
    if (qx.core.Environment.get('browser.name') == 'ie' && qx.core.Environment.get('browser.version') < 8 ) {
        el.setAttributes({
            encoding: encoding || "multipart/form-data"
        })
    }
    el.include();
    // Initialize Variables
    this.__parameters = {};
    this.__hidden = {};
    
    // create a hidden iframe which is used as form submission target
    this._createIFrameTarget();
  },

  // --------------------------------------------------------------------------
  // [Destructor]
  // --------------------------------------------------------------------------

  destruct: function()
  {
    if (this.__iframeNode)
    {
      try
      {
        document.body.removeChild(this.__iframeNode);
        this.__iframeNode.onreadystatechange = null;
        this.__iframeNode.onload = null;
        this.__iframeNode = null;
      }
      catch (exc)
      {
        this.warn("can't remove iframe node from the DOM tree.");
      }
    }
  
    this.__parameters = null;
  
    for (var id in this.__hidden)
    {
      if(this.__hidden[id] && this.__hidden[id].parentNode)
      {
        this.__hidden[id].parentNode.removeChild(this.__hidden[id]);
      }
    }
    
    this.__hidden = null;
  },

  // --------------------------------------------------------------------------
  // [Events]
  // --------------------------------------------------------------------------

  events:
  {
    "sending"    : "qx.event.type.Event",
    "completed"  : "qx.event.type.Event"
  },

  // --------------------------------------------------------------------------
  // [Properties]
  // --------------------------------------------------------------------------

  properties:
  {
    /**
     * The name which is assigned to the form
     */
    name:
    {
      check    : "String",
      init     : "",
      apply    : "_applyName"
    },

    /**
     * The url which is used for form submission.
     */
    url:
    {
      check    : "String",
      init     : "",
      apply    : "_applyUrl"
    },

    /**
     * The target which is used for form submission.
     */
    target:
    {
      check    : "String",
      init     : "",
      apply    : "_applyTarget"
    }
  },
  
  // --------------------------------------------------------------------------
  // [Members]
  // --------------------------------------------------------------------------

  members:
  {
    __iframeNode: null,
    __parameters: null,
    __hidden: null,
    __isSent: null,

    // ------------------------------------------------------------------------
    // [Modifiers]
    // ------------------------------------------------------------------------

    _applyName: function(value, old)
    {
      this.getContentElement().setAttribute("name", value);
    },
    
    _applyUrl: function(value, old)
    {
      this.getContentElement().setAttribute("action", value);
    },

    _applyTarget: function(value, old)
    {
      this.getContentElement().setAttribute("target", value);
    },
    
    // ------------------------------------------------------------------------
    // [Utilities]
    // ------------------------------------------------------------------------

    /**
     * Create a hidden iframe which is used as target for the form submission.
     * Don't need a src attribute, if it was set to javascript:void we get an insecure
     * objects error in IE.
     *
     * @type member
     * @return {void}
     */
    _createIFrameTarget: function()
    {
      var frameName = "frame_" + (new Date).valueOf();

     if (qx.core.Environment.get('browser.name') == 'ie' && qx.core.Environment.get('browser.version') < 8 ) {
        this.__iframeNode = document.createElement('<iframe name="' + frameName + '"></iframe>');
      }
      else
      {
        this.__iframeNode = document.createElement("iframe");
      }

      this.__iframeNode.id = (this.__iframeNode.name = frameName);
      this.__iframeNode.style.display = "none";
      this.setTarget(frameName);

      document.body.appendChild(this.__iframeNode);

      this.__iframeNode.onload = qx.lang.Function.bind(this._onLoad, this);
      this.__iframeNode.onreadystatechange = qx.lang.Function.bind(this._onReadyStateChange, this);
    },

    /**
     * replace the widgets content element with a form element so that we can then send the stuff away.
     */    
    _createContentElement: function()
    {
      var el = new qx.html.Element("form",{
        overflow: 'hidden'
      });
      return el;
    },

    /**
     * Add parameters as hidden fields to the form.
     *
     * @type member
     * @return {object}
     */
    _addFormParameters: function()
    {
      var form = this.getContentElement().getDomElement();
      var parameters = this.getParameters();
      var firstChild = form.firstChild;

      // Parameters must be first element so that we can parse them before the file
      for (var id in parameters) {
    	form.insertBefore(this.__hidden[id], firstChild);
      }
    },


    /**
     * Create an input element of type hidden with the 
     * name ({@link #name}) and value ({@link #value})
     *
     * @type member
     * @param name {String} name attribute of the created element ({@link #name}).
     * @param value {String} value attribute of the created element ({@link #value}).
     * @return {void}
     */
    _createHiddenFormField: function(name,value)
    {
      var hvalue = document.createElement("input");
      hvalue.type = "hidden";
      hvalue.name = name;
      hvalue.value = value;
    
      return hvalue;
    },

    // ------------------------------------------------------------------------
    // [Parameters Setters / Getters]
    // ------------------------------------------------------------------------

    /**
     * Set a request parameter which is stored as an input type=hidden.
     * 
     * @param id String identifier of the parameter to add.
     * @param value String Value of parameter.
     * @return {void}
     */
    setParameter: function(id, value)
    {
      this.__parameters[id] = value;
      if(this.__hidden[id] && this.__hidden[id].name) {
        this.__hidden[id].value = value;
      }
      else {
        this.__hidden[id] = this._createHiddenFormField(id, value);
      }
    },

    /**
     * Remove a parameter from the request.
     * 
     * @param id String identifier of the parameter to remove.
     * @return {void}
     */
    removeParameter: function(id)
    {
      delete this.__parameters[id];
      if(this.__hidden[id] && this.__hidden[id].parentNode) {
        this.__hidden[id].parentNode.removeChild(this.__hidden[id]);
      }
      delete this.__hidden[id];
    },

    /**
     * Get a parameter in the request.
     * 
     * @param id String identifier of the parameter to get.
     * @return {String}
     */
    getParameter: function(id)
    {
      return this.__parameters[id] || null;
    },
    
    /**
     * Returns the array containg all parameters for the request.
     * 
     * @return {Array}
     */
    getParameters: function()
    {
      return this.__parameters;
    },

    // ------------------------------------------------------------------------
    // [Send]
    // ------------------------------------------------------------------------

    /**
     * Send the form via the submit method. Target defaults to the
     * self created iframe.
     * 
     * @return {void}
     */
    send: function()
    {
      var form = this.getContentElement().getDomElement();

      if (form)
      {
        this._addFormParameters();

        form.submit();

        this.__isSent = true;
        this.fireEvent("sending");
      }
      else
      {
        throw new Error("Form element not created! Unable to call form submit!");
      }
    },


    /**
     * clear the form
     * 
     * @return {void}
     */
    clear: function()
    {
      var form = this.getContentElement().getDomElement();

      if (form) {
        form.reset();
      }
      else {
        throw new Error("Form element not created! Unable to call form reset!");
      }
    },


    // ------------------------------------------------------------------------
    // [Iframe]
    // ------------------------------------------------------------------------

    /**
     * Get the DOM window object of the target iframe.
     *
     * @type member
     * @return {DOMWindow} The DOM window object of the iframe.
     */
    getIframeWindow: function()
    {
      return qx.bom.Iframe.getWindow(this.__iframeNode);
    },
    
    /**
     * Get the DOM document object of the target iframe.
     *
     * @type member
     * @return {DOMDocument} The DOM document object of the iframe.
     */
    getIframeDocument: function()
    {
      return qx.bom.Iframe.getDocument(this.__iframeNode);
    },

    /**
     * Get the HTML body element of the target iframe.
     *
     * @type member
     * @return {Element} The DOM node of the <code>body</code> element of the iframe.
     */
    getIframeBody: function()
    {
      return qx.bom.Iframe.getBody(this.__iframeNode);
    },
    
    /**
     * Get the target iframe Element.
     *
     * @type member
     * @return {Element} The DOM element of the iframe.
     */
    getIframeNode: function()
    {
      return this.__iframeNode;
    },

    // ------------------------------------------------------------------------
    // [Response Data Support]
    // ------------------------------------------------------------------------
    
    /**
     * Get the text content of the target iframe. 
     *
     * @type member
     * @return {String} The text response of the submit.
     */
    getIframeTextContent: function()
    {
      var vBody = this.getIframeBody();
    
      if (!vBody) {
        return null;
      }
    
      // Mshtml returns the content inside a PRE
      // element if we use plain text
      if (vBody.firstChild && (vBody.firstChild.tagName == "PRE" || vBody.firstChild.tagName == "pre"))
      {
        return vBody.firstChild.innerHTML;
      }
      else
      {
        return vBody.innerHTML;
      }
    },
    

    /**
     * Get the HTML content of the target iframe. 
     *
     * @type member
     * @return {String} The html response of the submit.
     */
    getIframeHtmlContent: function()
    {
      var vBody = this.getIframeBody();
      return vBody ? vBody.innerHTML : null;
    },
    

    /**
     * Get the XML content of the target iframe. 
     * 
     * This is a hack for now because I didn't find a way
     * to send XML via the iframe response.
     * 
     * In the resulting text all occurences of the &lt;
     * and &gt; entities are replaces by < and > and
     * the Text is then parsed into a XML-Document instance.
     *
     * @type member
     * @return {Document} The XML response of the submit.
     */
    getIframeXmlContent: function()
    {
      var responsetext = this.getIframeTextContent();
    
      if(!responsetext || responsetext.length == 0) {
        return null;
      }
    
      var xmlContent = null;
      var newText = responsetext.replace(/&lt;/g,"<");
      newText = newText.replace(/&gt;/g, ">");
    
      try {
        xmlContent = qx.xml.Document.fromString(newText);
      }
      catch(ex) {};
    
      return xmlContent;
    },

    // ------------------------------------------------------------------------
    // [Event Handlers]
    // ------------------------------------------------------------------------
    
    /**
     * Catch the onreadystatechange event of the target iframe.
     *
     * @type member
     * @param e {Event}
     * @return {void}
     */
    _onReadyStateChange : function(e)
    {
      if (this.getIframeNode().readyState == "complete" && this.__isSent)
      {
        this.fireEvent("completed");
        delete this.__isSent;
      }
    },

    
    /**
     * Catch the onload event of the target iframe
     *
     * @type member
     * @param e {Event}
     * @return {void}
     */
    _onLoad : function(e)
    {
      if(this.__isSent)
      {
        this.fireEvent("completed");
        delete this.__isSent;
      }
    }
  }
});
