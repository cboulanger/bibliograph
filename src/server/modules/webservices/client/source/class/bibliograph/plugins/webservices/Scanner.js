/* ************************************************************************

  Bibliograph: Online Collaborative Reference Management

   Copyright:
     2007-2018 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Christian Boulanger (cboulanger)

************************************************************************ */

/*global qx qcl dialog*/

/**
 * see https://github.com/serratus/quaggaJS/blob/1.0/example/scan-to-input/index.js
 *
 */
qx.Class.define("bibliograph.plugins.webservices.Scanner",
{
  extend: qx.core.Object,
  properties : {
    button: {
      check: "qx.ui.form.Button"
    },
    result: {
      check: "String",
      nullable: true,
      event: "changeResult"
    }
  },
  
  /**
   * Constructor. Takes a button which activates the scanner
   * @param qx.ui.form.Button button
   */
  construct: function(button){
    this.setButton(button);
    this.attachListeners();
  },
  members: {
    
    _scanner: null,
    
    /**
     * Called when the scanner Button is pressed
     */
    activateScanner: function() {
      let scanner = this.configureScanner('.quagga_overlay_content');
      let onDetected = (result) => {
        this.setResult(result.codeResult.code);
        stop();
      };
      let stop = () => {
        scanner.stop();  // should also clear all event-listeners?
        scanner.removeEventListener('detected', onDetected);
        this.hideOverlay();
        this.attachListeners();
      };
      this.showOverlay(stop);
      scanner
        .addEventListener('detected', onDetected)
        .start();
    },
    
    /**
     * (Re-)attaches the listeners
     */
    attachListeners: function() {
      this.getButton().addListenerOnce("execute",()=>{
        this.activateScanner();
      });
    },
    
    /**
     * Shows the overlay with the videao stream
     * @param cancelCb {Function}
     */
    showOverlay: function(cancelCb) {
      if (!this._overlay) {
        let content = document.createElement('div');
        let closeButton = document.createElement('div');
        closeButton.appendChild(document.createTextNode('X'));
        content.className = 'quagga_overlay_content';
        closeButton.className = 'quagga_overlay_close';
        this._overlay = document.createElement('div');
        this._overlay.className = 'quagga_overlay';
        this._overlay.appendChild(content);
        content.appendChild(closeButton);
        closeButton.addEventListener('click', function closeClick() {
          closeButton.removeEventListener('click', closeClick);
          cancelCb();
        });
        document.body.appendChild(this._overlay);
      } else {
        let closeButton = document.querySelector('.quagga_overlay_close');
        closeButton.addEventListener('click', function closeClick() {
          closeButton.removeEventListener('click', closeClick);
          cancelCb();
        });
      }
      this._overlay.style.display = "block";
    },
  
    /**
     * Hides the overlay
     */
    hideOverlay: function() {
      if (this._overlay) {
        this._overlay.style.display = "none";
      }
    },
  
    /**
     * Configures the Quagga object
     * @param selector
     * @return {null}
     */
    configureScanner: function(selector) {
      if (!this._scanner) {
        this._scanner = window.Quagga
          .decoder({readers: ['ean_reader']})
          .locator({patchSize: 'medium'})
          .fromSource({
            target: selector,
            constraints: {
              width: 800,
              height: 600,
              facingMode: "environment"
            }
          });
      }
      return this._scanner;
    }
  }
});