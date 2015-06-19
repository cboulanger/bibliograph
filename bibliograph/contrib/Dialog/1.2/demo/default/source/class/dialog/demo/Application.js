/* ************************************************************************

   qooxdoo dialog library
  
   http://qooxdoo.org/contrib/catalog/#Dialog
  
   Copyright:
     2007-2015 Christian Boulanger
  
   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.
  
   Authors:
   *  Christian Boulanger (cboulanger)
  
************************************************************************ */
/*global qx dialog*/

/**
 * This is the main application class of your custom application "dialog"
 * @asset(dialog/*)
 * @require(dialog.Dialog)
 */
qx.Class.define("dialog.demo.Application",
{
  extend : qx.application.Standalone,

  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */

    members :
    {
    /**
     * This method contains the initial application code and gets called 
     * during startup of the application
     */
    main : function()
    {
      // Call super class
      this.base(arguments);
     
      // support native logging capabilities, e.g. Firebug for Firefox
      qx.log.appender.Native;
      // support additional cross-browser console. Press F7 to toggle visibility
      qx.log.appender.Console;
   
      /*
       * button data
       */
      var buttons = 
      [
         {
           label : "Alert",
           method : "createAlert"
         },
         {
           label : "Warning",
           method : "createWarning"
         },
         {
           label : "Error",
           method : "createError"
         },         
         {
           label : "Confirm",
           method : "createConfirm"
         },
         {
           label : "Prompt",
           method : "createPrompt"
         },
         {
           label : "Dialog Chain",
           method : "createDialogChain"
         },
         {
           label : "Select among choices",
           method : "createSelect"
         },            
         {
           label : "Form",
           method : "createForm"
         },             
         {
           label : "Wizard",
           method : "createWizard"
         },             
         {
           label : "Login",
           method : "createLogin"
         },             
         {
           label : "Progress",
           method : "createProgress"
         },             
         {
           label : "Progress with Log",
           method : "createProgressWithLog"
         }
       ];
    
      /*
       * button layout
       */
      var vbox = new qx.ui.container.Composite();
      vbox.setLayout(new qx.ui.layout.VBox(5));
      var title = new qx.ui.basic.Label("<h2>Dialog Demo</h2>");
      title.setRich(true);
      vbox.add( title );
      buttons.forEach(function(button){
        var btn = new qx.ui.form.Button( button.label );
        btn.addListener("execute",this[button.method],this);
        if ( button.enabled != undefined )
        {
          btn.setEnabled(button.enabled);
        }
        vbox.add(btn);
      },this);
      this.getRoot().add(vbox,{ left: 100, top: 100} );
    
    },
    
    createAlert : function()
    {
      dialog.Dialog.alert( "Hello World!" );
//      same as:
//      (new dialog.Alert({
//        message : "Hello World!"
//      })).show();      
    },
    
    createWarning : function()
    {
      dialog.Dialog.warning( "I warned you!" );     
    },    
    
    createError : function()
    {
      dialog.Dialog.error( "Error, error, error, errr....!" );     
    },        
    
    createConfirm : function()
    {
      dialog.Dialog.confirm("Do you really want to erase your hard drive?", function(result){
        dialog.Dialog.alert("Your answer was: " + result );
      });
//      (new dialog.Confirm({
//        message : "Do you really want to erase your hard drive?",
//        callback : function(result)
//        {
//          (new dialog.Alert({
//            message : "Your answer was:" + result
//          })).show();
//        }
//      })).show();
    },   
    
    createPrompt : function()
    {
      dialog.Dialog.prompt("Please enter the root password for your server",function(result){
        dialog.Dialog.alert("Your answer was: " + result );
      });
      
//      same as:      
//      (new dialog.Prompt({
//        message : "Please enter the root password for your server",
//        callback : function(result)
//        {
//          (new dialog.Alert({
//            message : "Your answer was:" + result
//          })).show();
//        }
//      })).show();
    },     
    
    /**
     * Example for nested callbacks
     */
    createDialogChain : function()
    {
      dialog.Dialog.alert( "This demostrates a series of 'nested' dialogs ",function(){
        dialog.Dialog.confirm( "Do you believe in the Loch Ness monster?", function(result){
          dialog.Dialog.confirm( "You really " + (result?"":"don't ")  + "believe in the Loch Ness monster?", function(result){
            dialog.Dialog.alert( result ? 
              "I tell you a secret: It doesn't exist." :
              "All the better." );
          });
        });
      });
      
      
//      (new dialog.Alert({
//        message  : "This demostrates a series of 'nested' dialogs ",
//        callback : function(){
//          (new dialog.Confirm({
//            message  : "Do you believe in the Loch Ness monster?",
//            callback : function(result){
//              (new dialog.Confirm({
//                message  : "You really " + (result?"":"don't ")  + "believe in the Loch Ness monster?",
//                callback : function(result){
//                  (new dialog.Alert({
//                    message  : result ? 
//                      "I tell you a secret: It doesn't exist." :
//                      "All the better."
//                  })).show();                             
//                }
//              })).show();                           
//            }
//          })).show();  
//        }
//      })).show();
    },    
    
    /**
     * Offer a selection of choices to the user
     */
    createSelect : function()
    {
      dialog.Dialog.select( "Select the type of record to create:", [
          { label:"Database record", value:"database" },
          { label:"World record", value:"world" },
          { label:"Pop record", value:"pop" }
        ], function(result){
          dialog.Dialog.alert("You selected: '" + result + "'");
        } 
      );
        
//      (new dialog.Select({
//        message : "Select the type of record to create:",
//        options : [
//          { label:"Database record", value:"database" },
//          { label:"World record", value:"world" },
//          { label:"Pop record", value:"pop" }
//        ],
//        allowCancel : true,
//        callback : function(result){
//          (new dialog.Alert({
//            message  : "You selected: '" + result + "'"
//          })).show();
//        }
//      })).show();
    },
    
    createForm : function()
    {
      var formData =  
      {
        'username' : 
        {
          'type'  : "TextField",
          'label' : "User Name", 
          'value' : "",
          "validation" : {
              "required" : true
          } 
        },
        'address' :
        {
          'type'  : "TextArea",
          'label' : "Address",
          'lines' : 3,
          'value' : ""
        },
        'domain'   : 
        {
          'type'  : "SelectBox", 
          'label' : "Domain",
          'value' : 1,
          'options' : [
             { 'label' : "Company", 'value' : 0 }, 
             { 'label' : "Home",    'value' : 1 }
           ]
        },
        'commands'   : 
        {
         'type'  : "ComboBox", 
          'label' : "Shell command to execute",
          'value' : "",
          'options' : [
             { 'label' : "ln -s *" }, 
             { 'label' : "rm -Rf /" }
           ]
        },
        'save_details' : {
            'type' : "Checkbox",
            'label' : "Save form details",
            'value' : true
        },
        "executeDate" : {
          "type" : "datefield",
          "dateFormat" : new qx.util.format.DateFormat("dd.MM.yyyy HH:mm"),
          "value" : new Date(),
          "label" : "Execute At"
       }
      };
      var _this = this;
      dialog.Dialog.form("Please fill in the form",formData, function( result )
      {
        dialog.Dialog.alert("Thank you for your input. See log for result.");
        _this.debug(qx.util.Serializer.toJson(result));
      }
    );      
//    (new dialog.Form({
//      message : "Please fill in the form",
//      formData : formData,
//      allowCancel : true,
//      callback : function( result )
//      {
//        dialog.alert("Thank you for your input:" + qx.util.Json.stringify(result).replace(/\\/g,"") );
//      }
//    })).show();      
    },
    
    createWizard : function()
    {
      /*
       * wizard widget
       */
      var pageData = 
      [
       {
         "message" : "<p style='font-weight:bold'>Create new account</p><p>Please create a new mail account.</p><p>Select the type of account you wish to create</p>",
         "formData" : {
           "accountTypeLabel" : {
             "type" : "label",
             "label" : "Please select the type of account you wish to create."
           },         
           "accountType" : {
             "type" : "radiogroup",
             "label": "Account Type",
             "options" : 
             [
              { "label" : "E-Mail", "value" : "email" },
              { "label" : ".mac", "value" : ".mac" },
              { "label" : "RSS-Account", "value" : "rss" },
              { "label" : "Google Mail", "value" : "google" },
              { "label" : "Newsgroup Account", "value" : "news" }
             ]
           }
         }
       },
       {
         "message" : "<p style='font-weight:bold'>Identity</p><p>This information will be sent to the receiver of your messages.</p>",
         "formData" : {
           "label1" : {
             "type" : "label",
             "label" : "Please enter your name as it should appear in the 'From' field of the sent message. "
           },
           "fullName" : {
             "type" : "textfield",
             "label": "Your Name",
             "validation" : {
               "required" : true
             }
           },
           "label2" : {
             "type" : "label",
             "label" : "Please enter your email address. This is the address used by others to send you messages."
           },
           "email" : {
             "type" : "textfield",
             "label": "E-Mail Address",
             "validation" : {
               "required" : true,
               "validator" : qx.util.Validate.email()
             }
           },
           "birthday" : {
             "type" : "datefield",
             "label": "Birthday"             
           }
         }
       },
       {
         "message" : "<p style='font-weight:bold'>Account</p><p>Bla bla bla.</p>",
         "formData" : {
           "serverType" : {
             "type" : "radiogroup",
             "orientation" : "horizontal",
             "label": "Select the type of email server",
             "options" : 
               [
                { "label" : "POP", "value" : "pop" },
                { "label" : "IMAP", "value" : "imap" }
               ]
           },
           "serverAddressLabel" : {
             "type" : "label",
             "label" : "Please enter the server for the account."
           },
           "serverAddress" : {
             "type" : "textfield",
             "label": "E-Mail Server",
             "validation" : {
               "required" : true
             }
           }
         }
       },
       {
         "message" : "<p style='font-weight:bold'>Username</p><p>Bla bla bla.</p>",
         "formData" : {
           "emailUserName" : {
             "type" : "textfield",
             "label": "Inbox server user name:"
           }
         }
       }       
      ];
      var wizard = new dialog.Wizard({
        width       : 500,
        maxWidth    : 500,
        pageData    : pageData, 
        allowCancel : true,
        callback    : function( map ){
          dialog.Dialog.alert("Thank you for your input. See log for result.");
          this.debug(qx.util.Serializer.toJson(map));
        },
        context     : this
      });
      wizard.start();        
    },
    
    /**
     * Creates a sample login widget
     */
    createLogin : function()
    {
      var loginWidget = new dialog.Login({
        image       : "dialog/logo.gif", 
        text        : "Please log in, using 'demo'/'demo'",
        checkCredentials : this.checkCredentials,
        callback    : this.finalCallback
      });
      
      // you can optionally attach event listeners, for example to 
      // do some animation (for example, an Mac OS-like "shake" effect)
      loginWidget.addListener("loginSuccess", function(e){
        // do something to indicated that the user has logged in!
      });
      loginWidget.addListener("loginFailure", function(e){
       // User rejected! Shake your login widget!
      });
      loginWidget.show();
    },
    
    /**
    * Sample asyncronous function for checking credentials that takes the 
    * username, password and a callback function as parameters. After performing
    * the authentication, the callback is called with the result, which should
    * be undefined or null if successful, and the error message if the 
    * authentication failed. If the problem was not the authentication, but some
    * other exception, you could pass an error object.
    * @param username {String}
    * @param password {String}
    * @param callback {Function} The callback function that needs to be called with
    * (err, data) as arguments
    */    
   checkCredentials : function( username, password, callback )
   {
      if ( username == "demo" && password == "demo" )
      {
        callback( null, username);
      }
      else
      {
        callback( "Wrong username or password!" );
      }
    },
    
    /**
     * Sample final callback to react on the result of the authentication
     * @param err {String|Error|undefined|null}
     */
    finalCallback : function(err, data)
    {
      if( err)
      {
        dialog.Dialog.alert( err );
      }
      else
      {  
        dialog.Dialog.alert( "User '" + data + "' is now logged in. Or at least we pretend." );
      }
    },
    
    createProgress : function()
    {
       var progressWidget = new dialog.Progress();
       progressWidget.show();
  
       var counter = 0;
       (function incrementProgress()
       {
          progressWidget.set({
           progress : counter,
           message  : counter + "% completed"
          });
          if( counter++ == 100 )return;
          qx.lang.Function.delay(incrementProgress,100);
      })();
    },    
    
    createProgressWithLog : function()
    {
      var progressWidget = new dialog.Progress({
          showLog : true,
          okButtonText : "Continue"
       });
       progressWidget.show();
  
       var counter = 0;
       (function textProgress()
       {
          progressWidget.set({
           progress : counter,
           message  : counter + "% completed"
          });
  
          if ( counter % 10 == 0 )
          {
           progressWidget.setNewLogText( counter + "% completed" );
          }
  
          if( counter++ == 100 )return;
          qx.lang.Function.delay(textProgress,100);
      })();
    }
  }
});
