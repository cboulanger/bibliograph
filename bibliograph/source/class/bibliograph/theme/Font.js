/* ************************************************************************

   Copyright:

   License:

   Authors:

************************************************************************ */
qx.Theme.define("bibliograph.theme.Font",
{
  extend : qx.theme.modern.Font,
  fonts : {
    "title" :
    {
      size : ((qx.core.Environment.get("os.name") === "win" && qx.core.Environment.get("os.version") === "vista") || (qx.core.Environment.get("os.name") === "win" && qx.core.Environment.get("os.version") === "7")) ? 14 : 12,
      lineHeight : 1.4,
      family : (qx.core.Environment.get("os.name") === "osx") ? ["Lucida Grande"] : ((qx.core.Environment.get("os.name") === "win" && qx.core.Environment.get("os.version") === "vista") || (qx.core.Environment.get("os.name") === "win" && qx.core.Environment.get("os.version") === "7")) ? ["Segoe UI", "Candara"] : ["Tahoma", "Liberation Sans", "Arial", "sans-serif"],
      bold : true
    }
  }
});
