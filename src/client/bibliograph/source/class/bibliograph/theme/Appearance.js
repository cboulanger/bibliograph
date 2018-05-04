/* ************************************************************************

   Copyright: 2018 Christian Boulanger

   License: MIT license

   Authors: Christian Boulanger (cboulanger) info@bibliograph.org

************************************************************************ */

qx.Theme.define("bibliograph.theme.Appearance",
{
  extend : qx.theme.modern.Appearance,

  appearances :
  {
    // TokenField contrib
    'token' : 'combobox',
    'tokenitem' :
      {
        include : 'listitem',
        style : function(states, styles) {
          return {
            decorator : 'group',
            textColor : states.hovered ? '#314a6e' : '#000000',
            backgroundColor: states.head ? '#4d94ff' : undefined,
            height : 18,
            padding : [1, 6, 1, 6],
            margin : 1,
            icon : states.hovered ? "decoration/window/close-active.png" : "decoration/window/close-inactive.png"
          };
        }
      }
  }
});